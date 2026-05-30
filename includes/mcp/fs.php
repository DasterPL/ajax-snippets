<?php

defined('ABSPATH') || exit;

/**
 * Safe filesystem operations for the MCP bridge.
 *
 * RELIABILITY layer (NOT a security boundary): the raw `execute_snippet` eval
 * remains and can touch any file the PHP process can — by design. These tools
 * exist so the LLM does not have to hand-write ad-hoc PHP for routine file
 * edits (theme functions.php, plugins, new files). On top of plain reads/writes
 * they add: a root allowlist, PHP syntax lint before write, timestamped backup,
 * atomic write (tmp + rename), and a canary HTTP request with auto-rollback for
 * critical files.
 *
 * Style mirrors snippet-core.php: pure-ish static methods + helper functions
 * that take plain arguments and return arrays, or throw \Throwable. They NEVER
 * call wp_send_json / WP_REST_Response — the REST routes wrap them with
 * ajax_snippets_mcp_with_runner() for audit + envelope shaping.
 *
 * PATH INPUT CONTRACT (resolve_path):
 *   - A path starting with '/' or a Windows drive letter ("C:\", "C:/") is
 *     treated as ABSOLUTE on the server.
 *   - Anything else is treated as RELATIVE to WP_CONTENT_DIR.
 *   - ASYMMETRIC scope:
 *       * READS (read_file / list_dir / grep) may target the whole WordPress
 *         install (ABSPATH) — including wp-config.php and core. There is nothing
 *         to hide here: execute_snippet can already read those and dump their
 *         constants, so a read block would only add friction.
 *       * WRITES (write_file / edit_file) are confined to wp-content only.
 *         wp-config.php and core stay non-writable — a botched write there is a
 *         footgun; use execute_snippet if you really must touch them.
 *   - Paths outside the relevant root set are rejected (and reads never escape
 *     ABSPATH to the wider server filesystem).
 *   - Missing parent directories are created on write (mkdir -p), as long as the
 *     final path stays inside wp-content.
 */

if (!class_exists('Ajax_Snippets_FS')) {
    class Ajax_Snippets_FS
    {
        /** Read cap: return at most this many bytes, flag truncated. */
        const READ_LIMIT = 2097152; // 2 MiB

        /** Write cap: reject content larger than this. */
        const WRITE_LIMIT = 5242880; // 5 MiB

        /** Backup retention: drop .bak files older than this (best-effort). */
        const BACKUP_RETENTION = 2592000; // 30 days

        /** grep: hard caps so a search can never blow the result size / run away. */
        const GREP_MAX_RESULTS   = 500;
        const GREP_MAX_FILES     = 5000;
        const GREP_MAX_FILE_SIZE = 2097152; // 2 MiB — skip larger files
        const GREP_LINE_CAP      = 400;     // truncate long matching lines

        /** grep: directories never descended into (noise / huge / not source). */
        const GREP_SKIP_DIRS = ['node_modules', '.git', 'ajax-snippets-fs-backups'];

        /**
         * WRITE roots (each passed through realpath). Writes/edits are confined
         * to the wp-content tree: themes, plugins, mu-plugins, uploads, cache,
         * languages, upgrade and any custom subdirectories. wp-config.php and the
         * WordPress core (in ABSPATH, one level up) are NOT writable — a botched
         * write there is a footgun, and editing them is left to execute_snippet.
         *
         * @return list<string> canonical root paths (no trailing separator)
         */
        public static function write_roots()
        {
            $roots = [];

            if (defined('WP_CONTENT_DIR')) {
                $roots[] = WP_CONTENT_DIR;
            }
            // Also include these explicitly in case a site relocates them
            // OUTSIDE wp-content (some setups move uploads or the plugins dir).
            // Duplicates with WP_CONTENT_DIR are de-duped below.
            if (function_exists('get_theme_root')) {
                $roots[] = get_theme_root();
            }
            if (defined('WP_PLUGIN_DIR')) {
                $roots[] = WP_PLUGIN_DIR;
            }
            if (defined('WPMU_PLUGIN_DIR')) {
                $roots[] = WPMU_PLUGIN_DIR;
            }
            if (function_exists('wp_upload_dir')) {
                $upload = wp_upload_dir(null, false);
                if (is_array($upload) && !empty($upload['basedir'])) {
                    $roots[] = $upload['basedir'];
                }
            }

            return self::canonical_roots($roots);
        }

        /**
         * READ roots: the whole WordPress install (ABSPATH) plus the write roots
         * (covers wp-content even if relocated outside ABSPATH). Reads/list/grep
         * are deliberately broader than writes — there is no confidentiality to
         * protect here (execute_snippet can already read wp-config.php and dump
         * its constants), so restricting reads only adds friction. This still
         * keeps FS within the install, not the entire server filesystem.
         *
         * @return list<string> canonical root paths (no trailing separator)
         */
        public static function read_roots()
        {
            $roots = self::write_roots();
            if (defined('ABSPATH')) {
                array_unshift($roots, ABSPATH);
            }
            return self::canonical_roots($roots);
        }

        /**
         * De-duplicate + realpath a list of candidate roots.
         *
         * @param list<string> $roots
         * @return list<string> canonical root paths (no trailing separator)
         */
        private static function canonical_roots(array $roots)
        {
            $canonical = [];
            foreach ($roots as $root) {
                if (!is_string($root) || $root === '') {
                    continue;
                }
                $real = realpath($root);
                if ($real !== false) {
                    $canonical[rtrim($real, '\\/')] = true;
                }
            }
            return array_keys($canonical);
        }

        /**
         * Canonicalise + validate a caller-supplied path against the allowlist.
         *
         * @param string $input      Absolute server path or path relative to WP_CONTENT_DIR.
         * @param bool   $must_exist When true the path must already exist (realpath);
         *                           when false missing parents are allowed (mkdir -p later).
         * @param bool   $for_write  When true validate against the WRITE roots
         *                           (wp-content only); when false against the READ
         *                           roots (the whole install).
         * @return string Canonical absolute path inside the relevant root set.
         * @throws \RuntimeException on empty/traversal/outside-root.
         */
        public static function resolve_path($input, $must_exist, $for_write = false)
        {
            $input = (string) $input;
            if (trim($input) === '') {
                throw new \RuntimeException('Path is required.');
            }

            // Normalise to an absolute server path.
            if (self::is_absolute($input)) {
                $absolute = $input;
            } else {
                if (!defined('WP_CONTENT_DIR')) {
                    throw new \RuntimeException('WP_CONTENT_DIR is not defined; cannot resolve a relative path.');
                }
                $absolute = rtrim(WP_CONTENT_DIR, '\\/') . DIRECTORY_SEPARATOR . ltrim($input, '\\/');
            }

            if ($must_exist) {
                $canonical = realpath($absolute);
                if ($canonical === false) {
                    throw new \RuntimeException('Path does not exist: ' . $input);
                }
            } else {
                // New file/dir: the full chain need not exist. Canonicalise
                // against the deepest EXISTING ancestor; missing dirs are created
                // later by write_atomic (mkdir -p).
                $canonical = self::canonicalize_nonexistent($absolute);
            }

            self::assert_inside_roots($canonical, $for_write);
            return $canonical;
        }

        /**
         * Canonicalise a path that may not fully exist yet. Resolves the deepest
         * EXISTING ancestor with realpath() (so symlinks and '..' in the existing
         * portion are collapsed), then re-appends the remaining segments after
         * rejecting traversal ('.', '..', empty). The caller still runs
         * assert_inside_roots() on the result, so an escape via the existing
         * portion is caught there.
         *
         * @throws \RuntimeException on a traversal segment or no existing ancestor.
         */
        private static function canonicalize_nonexistent($absolute)
        {
            $absolute = rtrim($absolute, '\\/');
            if ($absolute === '') {
                throw new \RuntimeException('Invalid path.');
            }
            $real = realpath($absolute);
            if ($real !== false) {
                return rtrim($real, '\\/');
            }

            $tail    = [];
            $current = $absolute;
            while (true) {
                $parent = dirname($current);
                $base   = basename($current);
                if ($base === '' || $base === '.' || $base === '..') {
                    throw new \RuntimeException('Invalid path segment in: ' . $absolute);
                }
                array_unshift($tail, $base);

                $realParent = realpath($parent);
                if ($realParent !== false) {
                    return rtrim($realParent, '\\/') . DIRECTORY_SEPARATOR
                        . implode(DIRECTORY_SEPARATOR, $tail);
                }
                if ($parent === $current) {
                    throw new \RuntimeException('No existing ancestor directory for: ' . $absolute);
                }
                $current = $parent;
            }
        }

        /**
         * True if $path is absolute: starts with '/' (POSIX), a backslash, or a
         * Windows drive letter ("C:\", "C:/").
         */
        private static function is_absolute($path)
        {
            if ($path === '') {
                return false;
            }
            if ($path[0] === '/' || $path[0] === '\\') {
                return true;
            }
            return (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);
        }

        /**
         * @param bool $for_write Validate against WRITE roots (wp-content) when
         *                        true, otherwise READ roots (the whole install).
         * @throws \RuntimeException if $canonical is not within the relevant roots.
         */
        private static function assert_inside_roots($canonical, $for_write = false)
        {
            $roots = $for_write ? self::write_roots() : self::read_roots();
            foreach ($roots as $root) {
                if ($canonical === $root) {
                    return;
                }
                $prefix = $root . DIRECTORY_SEPARATOR;
                if (strncmp($canonical, $prefix, strlen($prefix)) === 0) {
                    return;
                }
            }
            if ($for_write) {
                throw new \RuntimeException(
                    'Path is outside wp-content; writes to wp-config.php / WordPress core are not allowed '
                    . '(use execute_snippet for those): ' . $canonical
                );
            }
            throw new \RuntimeException(
                'Path is outside the WordPress install (ABSPATH): ' . $canonical
            );
        }

        /**
         * Lint PHP source in-process via token_get_all(..., TOKEN_PARSE). No exec.
         * Only call for .php files.
         *
         * @throws \RuntimeException on a syntax error (message + line when known).
         */
        public static function lint_php($content)
        {
            try {
                token_get_all((string) $content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                throw new \RuntimeException(
                    'PHP syntax error: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')'
                );
            } catch (\Throwable $e) {
                throw new \RuntimeException('PHP syntax error: ' . $e->getMessage());
            }
        }

        /** Case-insensitive .php extension check. */
        private static function is_php_path($path)
        {
            return strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'php';
        }

        /**
         * Directory holding backups, hardened against public serving. Created on
         * demand. Also opportunistically prunes stale backups.
         *
         * @return string canonical backup dir path.
         * @throws \RuntimeException if it cannot be created.
         */
        private static function backup_dir()
        {
            $upload = wp_upload_dir(null, false);
            if (!is_array($upload) || empty($upload['basedir'])) {
                throw new \RuntimeException('Uploads base directory is unavailable; cannot store backup.');
            }
            $dir = rtrim($upload['basedir'], '\\/') . DIRECTORY_SEPARATOR . 'ajax-snippets-fs-backups';
            if (!is_dir($dir)) {
                if (!wp_mkdir_p($dir)) {
                    throw new \RuntimeException('Could not create backup directory: ' . $dir);
                }
            }
            // Deny public access + directory listing (best-effort).
            $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
            if (!file_exists($htaccess)) {
                @file_put_contents($htaccess, "Deny from all\n");
            }
            $index = $dir . DIRECTORY_SEPARATOR . 'index.php';
            if (!file_exists($index)) {
                @file_put_contents($index, "<?php\n// Silence is golden.\n");
            }
            self::gc_backups($dir);
            return $dir;
        }

        /** Best-effort deletion of backups older than BACKUP_RETENTION. */
        private static function gc_backups($dir)
        {
            $cutoff = time() - self::BACKUP_RETENTION;
            $files  = glob($dir . DIRECTORY_SEPARATOR . '*.bak');
            if (!is_array($files)) {
                return;
            }
            foreach ($files as $file) {
                $mtime = @filemtime($file);
                if ($mtime !== false && $mtime < $cutoff) {
                    @unlink($file);
                }
            }
        }

        /**
         * Copy an existing file into the backup dir before it is overwritten.
         *
         * @return string|null backup path, or null when $path does not exist (new file).
         * @throws \RuntimeException if the copy fails.
         */
        private static function backup_existing($path)
        {
            if (!file_exists($path)) {
                return null;
            }
            $dir = self::backup_dir();
            // Sanitise the relative path into a flat, filesystem-safe token.
            $rel  = self::relative_token($path);
            $name = time() . '-' . $rel . '.bak';
            $dest = $dir . DIRECTORY_SEPARATOR . $name;
            if (!@copy($path, $dest)) {
                throw new \RuntimeException('Failed to back up file before write: ' . $path);
            }
            return $dest;
        }

        /**
         * Turn a path into a flat, filesystem-safe token for the backup filename,
         * preserving enough of the original layout to be recognisable.
         */
        private static function relative_token($path)
        {
            $rel = $path;
            if (defined('WP_CONTENT_DIR')) {
                $base = rtrim(WP_CONTENT_DIR, '\\/');
                if (strncmp($path, $base, strlen($base)) === 0) {
                    $rel = substr($path, strlen($base));
                }
            }
            $rel = ltrim((string) $rel, '\\/');
            $rel = str_replace(['\\', '/'], '__', $rel);
            $rel = preg_replace('/[^A-Za-z0-9._-]/', '_', $rel);
            return trim((string) $rel, '_') ?: 'file';
        }

        /**
         * Atomically write $content to $path, backing up any existing file first.
         * Preserves the prior file's permissions. tmp file lives in the same
         * directory so rename() stays atomic on the same filesystem.
         *
         * @return array{backup:?string,created:bool}
         * @throws \RuntimeException on any write/rename failure.
         */
        public static function write_atomic($path, $content)
        {
            $existed   = file_exists($path);
            $oldPerms  = $existed ? (@fileperms($path) & 0777) : null;
            $backup    = self::backup_existing($path);

            $dir = dirname($path);
            if (!is_dir($dir)) {
                // Create missing parent directories (mkdir -p). $dir is already
                // validated to live inside an allowed root by resolve_path().
                if (!wp_mkdir_p($dir)) {
                    throw new \RuntimeException('Could not create target directory: ' . $dir);
                }
            }

            $tmp = $dir . DIRECTORY_SEPARATOR . '.ajax-snippets-fs-' . uniqid('', true) . '.tmp';
            $bytes = @file_put_contents($tmp, (string) $content, LOCK_EX);
            if ($bytes === false) {
                @unlink($tmp);
                throw new \RuntimeException('Failed to write temporary file in: ' . $dir);
            }

            if (!@rename($tmp, $path)) {
                @unlink($tmp);
                throw new \RuntimeException('Atomic rename failed; original file left intact: ' . $path);
            }

            // Restore prior permissions (best-effort).
            if ($oldPerms !== null) {
                @chmod($path, $oldPerms);
            }

            return ['backup' => $backup, 'created' => !$existed];
        }

        /**
         * Is this a critical file whose breakage could take the site down?
         * functions.php (any), anything in mu-plugins, or the active theme's
         * main stylesheet/template file (style entry: <theme>/functions.php is
         * already covered; we also cover the theme's own directory root files).
         */
        private static function is_critical($path)
        {
            if (strcasecmp(basename($path), 'functions.php') === 0) {
                return true;
            }
            if (defined('WPMU_PLUGIN_DIR')) {
                $mu = realpath(WPMU_PLUGIN_DIR);
                if ($mu !== false) {
                    $prefix = rtrim($mu, '\\/') . DIRECTORY_SEPARATOR;
                    if (strncmp($path, $prefix, strlen($prefix)) === 0) {
                        return true;
                    }
                }
            }
            // Main file of the active theme (template / stylesheet dirs).
            if (function_exists('get_stylesheet_directory') && function_exists('get_template_directory')) {
                foreach ([get_stylesheet_directory(), get_template_directory()] as $themeDir) {
                    $real = realpath($themeDir);
                    if ($real === false) {
                        continue;
                    }
                    // The theme's own functions.php is the realistic crash vector,
                    // already caught by the basename check above; treat any file
                    // directly in the active theme root as critical too.
                    if (strcasecmp(dirname($path), rtrim($real, '\\/')) === 0) {
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * Canary: hit the home page after writing a critical file. On a fatal /
         * 5xx / WP critical-error page, restore the backup (or delete a freshly
         * created file) and throw — so the caller sees an error but the site is
         * saved.
         *
         * @param string      $path    file just written
         * @param string|null $backup  backup path (null = file was newly created)
         * @param bool        $created whether the write created a new file
         * @return bool rolled_back
         * @throws \RuntimeException after a rollback.
         */
        private static function canary_and_maybe_rollback($path, $backup, $created)
        {
            $response = wp_remote_get(home_url('/'), [
                'timeout'     => 10,
                'blocking'    => true,
                'sslverify'   => false,
                'redirection' => 1,
            ]);

            $broken = false;
            $reason = '';

            if (is_wp_error($response)) {
                $broken = true;
                $reason = 'request failed: ' . $response->get_error_message();
            } else {
                $code = (int) wp_remote_retrieve_response_code($response);
                $body = (string) wp_remote_retrieve_body($response);
                if ($code >= 500) {
                    $broken = true;
                    $reason = 'HTTP ' . $code;
                } elseif (preg_match('/Fatal error|Parse error|There has been a critical error|critical error on this website/i', $body)) {
                    $broken = true;
                    $reason = 'site returned a fatal/critical error page';
                }
            }

            if (!$broken) {
                return false;
            }

            // Roll back.
            if ($created) {
                @unlink($path);
            } elseif ($backup !== null && file_exists($backup)) {
                @copy($backup, $path);
            }

            throw new \RuntimeException(
                'Canary check failed after writing ' . $path . ' (' . $reason
                . '); change rolled back to keep the site online.'
            );
        }

        /**
         * List a directory. Dirs first, then alphabetical. '.' and '..' skipped.
         *
         * @return array{path:string,entries:list<array{name:string,type:string,size:int,modified:int}>}
         * @throws \RuntimeException if missing / not a directory / outside roots.
         */
        public static function list_dir($path)
        {
            $canonical = self::resolve_path($path, true);
            if (!is_dir($canonical)) {
                throw new \RuntimeException('Not a directory: ' . $canonical);
            }

            $entries = [];
            $handle  = @opendir($canonical);
            if ($handle === false) {
                throw new \RuntimeException('Could not open directory: ' . $canonical);
            }
            while (($name = readdir($handle)) !== false) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $full   = $canonical . DIRECTORY_SEPARATOR . $name;
                $isDir  = is_dir($full);
                $entries[] = [
                    'name'     => $name,
                    'type'     => $isDir ? 'dir' : 'file',
                    'size'     => $isDir ? 0 : (int) @filesize($full),
                    'modified' => (int) @filemtime($full),
                ];
            }
            closedir($handle);

            usort($entries, static function ($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'dir' ? -1 : 1;
                }
                return strcasecmp($a['name'], $b['name']);
            });

            return ['path' => $canonical, 'entries' => $entries];
        }

        /**
         * Read a file (capped at READ_LIMIT). Non-existent files return
         * exists=false with empty content rather than an error.
         *
         * @return array{path:string,exists:bool,size:int,content:string,truncated:bool}
         * @throws \RuntimeException if the (existing) path is outside roots / unreadable.
         */
        public static function read_file($path)
        {
            // Resolve against existing parent so a missing file still validates
            // the root, but report exists=false cleanly.
            try {
                $canonical = self::resolve_path($path, true);
            } catch (\RuntimeException $e) {
                // Distinguish "does not exist" (report cleanly) from "outside
                // roots / bad input" (must still surface as an error).
                $missing = strpos($e->getMessage(), 'does not exist') !== false
                    || strpos($e->getMessage(), 'Parent directory does not exist') !== false;
                if ($missing) {
                    // Validate the would-be path against roots before claiming
                    // it simply does not exist.
                    $canonical = self::resolve_path($path, false);
                    return [
                        'path'      => $canonical,
                        'exists'    => false,
                        'size'      => 0,
                        'content'   => '',
                        'truncated' => false,
                    ];
                }
                throw $e;
            }

            if (is_dir($canonical)) {
                throw new \RuntimeException('Path is a directory, not a file: ' . $canonical);
            }

            $size = (int) @filesize($canonical);
            $fh   = @fopen($canonical, 'rb');
            if ($fh === false) {
                throw new \RuntimeException('Could not open file for reading: ' . $canonical);
            }
            $content   = (string) fread($fh, self::READ_LIMIT);
            fclose($fh);
            $truncated = $size > self::READ_LIMIT;

            return [
                'path'      => $canonical,
                'exists'    => true,
                'size'      => $size,
                'content'   => $content,
                'truncated' => $truncated,
            ];
        }

        /**
         * Write (or create) a file: root check, size cap, PHP lint, backup,
         * atomic write, canary + rollback for critical files.
         *
         * @return array{path:string,bytes:int,created:bool,backup:?string,linted:bool,rolled_back:bool}
         * @throws \RuntimeException on any guard / write failure.
         */
        public static function write_file($path, $content, $create = true)
        {
            $content = (string) $content;
            if (strlen($content) > self::WRITE_LIMIT) {
                throw new \RuntimeException(
                    'Content exceeds the ' . self::WRITE_LIMIT . '-byte write limit.'
                );
            }

            $canonical = self::resolve_path($path, false, true);

            $exists = file_exists($canonical);
            if (!$exists && !$create) {
                throw new \RuntimeException('File does not exist and create=false: ' . $canonical);
            }
            if (is_dir($canonical)) {
                throw new \RuntimeException('Path is a directory, not a file: ' . $canonical);
            }

            $linted = false;
            if (self::is_php_path($canonical)) {
                self::lint_php($content);
                $linted = true;
            }

            $written = self::write_atomic($canonical, $content);

            $rolledBack = false;
            if (self::is_critical($canonical)) {
                $rolledBack = self::canary_and_maybe_rollback(
                    $canonical,
                    $written['backup'],
                    $written['created']
                );
            }

            return [
                'path'        => $canonical,
                'bytes'       => strlen($content),
                'created'     => $written['created'],
                'backup'      => $written['backup'],
                'linted'      => $linted,
                'rolled_back' => $rolledBack,
            ];
        }

        /**
         * Find/replace inside an existing file. Defaults to safe-unique: when
         * $find occurs more than once and $all is false, refuses (so the LLM
         * cannot silently edit the wrong occurrence). Then runs the same
         * lint+backup+atomic+canary pipeline as write_file.
         *
         * @return array{path:string,replacements:int,backup:?string,linted:bool,rolled_back:bool}
         * @throws \RuntimeException if file missing / find empty / not found / not unique.
         */
        public static function edit_file($path, $find, $replace, $all = false)
        {
            $find    = (string) $find;
            $replace = (string) $replace;
            if ($find === '') {
                throw new \RuntimeException('find string is required.');
            }

            $canonical = self::resolve_path($path, true, true);
            if (is_dir($canonical)) {
                throw new \RuntimeException('Path is a directory, not a file: ' . $canonical);
            }
            if (!is_readable($canonical)) {
                throw new \RuntimeException('File is not readable: ' . $canonical);
            }

            $original = (string) file_get_contents($canonical);
            $count    = substr_count($original, $find);

            if ($count === 0) {
                throw new \RuntimeException('find string not found.');
            }
            if ($count > 1 && !$all) {
                throw new \RuntimeException(
                    'find string not unique (' . $count . ' matches); pass all=true to replace all.'
                );
            }

            if ($all) {
                $updated      = str_replace($find, $replace, $original);
                $replacements = $count;
            } else {
                // Unique match — replace just the first (== only) occurrence.
                $pos = strpos($original, $find);
                $updated = substr($original, 0, $pos)
                    . $replace
                    . substr($original, $pos + strlen($find));
                $replacements = 1;
            }

            if (strlen($updated) > self::WRITE_LIMIT) {
                throw new \RuntimeException(
                    'Resulting content exceeds the ' . self::WRITE_LIMIT . '-byte write limit.'
                );
            }

            $linted = false;
            if (self::is_php_path($canonical)) {
                self::lint_php($updated);
                $linted = true;
            }

            $written = self::write_atomic($canonical, $updated);

            $rolledBack = false;
            if (self::is_critical($canonical)) {
                $rolledBack = self::canary_and_maybe_rollback(
                    $canonical,
                    $written['backup'],
                    $written['created']
                );
            }

            return [
                'path'        => $canonical,
                'replacements' => $replacements,
                'backup'      => $written['backup'],
                'linted'      => $linted,
                'rolled_back' => $rolledBack,
            ];
        }

        /**
         * Recursively grep file contents under a directory (inside the allowed
         * roots). Literal substring by default, PCRE when $opts['regex'] is set.
         * Binary files, oversized files, and noise dirs (node_modules/.git/backups)
         * are skipped. Hard-capped on results and files scanned so the response
         * stays bounded — `truncated` signals a cap was hit.
         *
         * @param string $path  Directory to search (within roots).
         * @param string $query Search string (literal) or PCRE body (regex mode).
         * @param array{regex?:bool,ignore_case?:bool,glob?:string,max_results?:int} $opts
         * @return array{path:string,matches:list<array{file:string,line:int,text:string}>,files_scanned:int,truncated:bool}
         * @throws \RuntimeException on bad input / outside roots / invalid regex.
         */
        public static function grep($path, $query, array $opts = [])
        {
            $query = (string) $query;
            if ($query === '') {
                throw new \RuntimeException('query is required.');
            }

            $regex      = !empty($opts['regex']);
            $ignoreCase = !empty($opts['ignore_case']);
            $glob       = isset($opts['glob']) ? (string) $opts['glob'] : '';
            $maxResults = isset($opts['max_results']) && (int) $opts['max_results'] > 0
                ? min(self::GREP_MAX_RESULTS, (int) $opts['max_results'])
                : self::GREP_MAX_RESULTS;

            $root = self::resolve_path($path, true);
            if (!is_dir($root)) {
                throw new \RuntimeException('Not a directory: ' . $root);
            }

            // Build + validate the regex up front so PCRE warnings can't leak and
            // a broken pattern fails fast instead of per-line.
            $pattern = null;
            if ($regex) {
                $pattern = '~' . str_replace('~', '\~', $query) . '~' . ($ignoreCase ? 'i' : '');
                if (@preg_match($pattern, '') === false) {
                    throw new \RuntimeException('Invalid regular expression: ' . $query);
                }
            }

            $skipDirs = self::GREP_SKIP_DIRS;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                    static function ($current) use ($skipDirs) {
                        if ($current->isDir()) {
                            return !in_array($current->getFilename(), $skipDirs, true);
                        }
                        return true;
                    }
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $matches      = [];
            $filesScanned = 0;
            $truncated    = false;

            foreach ($iterator as $fileInfo) {
                if ($filesScanned >= self::GREP_MAX_FILES) {
                    $truncated = true;
                    break;
                }
                if (!$fileInfo->isFile()) {
                    continue;
                }
                if ($glob !== '' && !fnmatch($glob, $fileInfo->getFilename())) {
                    continue;
                }
                if ((int) $fileInfo->getSize() > self::GREP_MAX_FILE_SIZE) {
                    continue;
                }

                $full = $fileInfo->getPathname();
                $fh   = @fopen($full, 'rb');
                if ($fh === false) {
                    continue;
                }

                // Binary sniff: a NUL byte in the first chunk => skip the file.
                $head = fread($fh, 8192);
                if ($head !== false && strpos($head, "\0") !== false) {
                    fclose($fh);
                    continue;
                }
                rewind($fh);

                $filesScanned++;
                $lineNo = 0;
                while (($line = fgets($fh)) !== false) {
                    $lineNo++;
                    $hit = $regex
                        ? (bool) @preg_match($pattern, $line)
                        : ($ignoreCase ? stripos($line, $query) !== false : strpos($line, $query) !== false);
                    if (!$hit) {
                        continue;
                    }
                    $text = rtrim($line, "\r\n");
                    if (strlen($text) > self::GREP_LINE_CAP) {
                        $text = substr($text, 0, self::GREP_LINE_CAP) . '…';
                    }
                    $matches[] = [
                        'file' => self::relpath($root, $full),
                        'line' => $lineNo,
                        'text' => $text,
                    ];
                    if (count($matches) >= $maxResults) {
                        $truncated = true;
                        break;
                    }
                }
                fclose($fh);
                if ($truncated) {
                    break;
                }
            }

            return [
                'path'          => $root,
                'matches'       => $matches,
                'files_scanned' => $filesScanned,
                'truncated'     => $truncated,
            ];
        }

        /**
         * Express $full as a path relative to $root, with forward slashes, for
         * compact grep output.
         */
        private static function relpath($root, $full)
        {
            $prefix = rtrim($root, '\\/') . DIRECTORY_SEPARATOR;
            $rel    = strncmp($full, $prefix, strlen($prefix)) === 0
                ? substr($full, strlen($prefix))
                : $full;
            return str_replace('\\', '/', $rel);
        }
    }
}
