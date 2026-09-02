<?php
/*
Plugin Name: AJAX Snippets
Description: A tool for WordPress administrators to run and test PHP code via AJAX.
Version: 2.16.2
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 7.0
Text Domain: ajax-snippets
Domain Path: /languages
Author: Apturn
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined('ABSPATH') || exit;

// Must match the `Version:` header above (CI enforces it on release).
define('AJAX_SNIPPETS_VERSION', '2.16.2');
define('AJAX_SNIPPETS_PLUGIN', __FILE__);
define('AJAX_SNIPPETS_DIR', plugin_dir_path(AJAX_SNIPPETS_PLUGIN));
define('AJAX_SNIPPETS_URL', plugin_dir_url(AJAX_SNIPPETS_PLUGIN));

require_once AJAX_SNIPPETS_DIR . 'vendor/autoload.php';

require_once AJAX_SNIPPETS_DIR . 'includes/visibility.php';
// Fail-open guard: if visibility.php is ever emptied or removed (e.g. a host
// antivirus zeroes it — the self-hiding filters trip malware heuristics), the
// gate function would be undefined and the first call on `admin_menu` would
// fatal the whole wp-admin. Define a fallback that keeps the plugin VISIBLE so
// the site stays up and an admin can still reach and repair it.
if (!function_exists('ajax_snippets_is_revealed')) {
    function ajax_snippets_is_revealed()
    {
        return true;
    }
}
require_once AJAX_SNIPPETS_DIR . 'includes/i18n.php';
require_once AJAX_SNIPPETS_DIR . 'includes/updater.php';
require_once AJAX_SNIPPETS_DIR . 'includes/assets.php';
require_once AJAX_SNIPPETS_DIR . 'includes/ajax-handlers.php';
require_once AJAX_SNIPPETS_DIR . 'includes/admin-menu.php';
require_once AJAX_SNIPPETS_DIR . 'includes/mcp/loader.php';
