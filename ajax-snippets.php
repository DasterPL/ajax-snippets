<?php
/*
Plugin Name: AJAX Snippets
Description: Simple AJAX Snippets
Version: 2.2.0
Requires at least: 4.9
Tested up to: 6.9
Requires PHP: 7.0
Text Domain: ajax-snippets
Domain Path: /languages
Author: Apturn
*/

defined('ABSPATH') || exit;

define('AJAX_SNIPPETS_VERSION', '2.2.0');
define('AJAX_SNIPPETS_PLUGIN', __FILE__);
define('AJAX_SNIPPETS_DIR', plugin_dir_path(AJAX_SNIPPETS_PLUGIN));
define('AJAX_SNIPPETS_URL', plugin_dir_url(AJAX_SNIPPETS_PLUGIN));

$autoload = AJAX_SNIPPETS_DIR . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} elseif (file_exists(AJAX_SNIPPETS_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php')) {
    require_once AJAX_SNIPPETS_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
}

require_once AJAX_SNIPPETS_DIR . 'includes/i18n.php';
require_once AJAX_SNIPPETS_DIR . 'includes/updater.php';
require_once AJAX_SNIPPETS_DIR . 'includes/assets.php';
require_once AJAX_SNIPPETS_DIR . 'includes/ajax-handlers.php';
require_once AJAX_SNIPPETS_DIR . 'includes/admin-menu.php';
