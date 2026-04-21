<?php
/*
Plugin Name: AJAX Snippets
Description: A tool for WordPress administrators to run and test PHP code via AJAX.
Version: 2.7
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 7.0
Text Domain: ajax-snippets
Domain Path: /languages
Author: Apturn
*/

defined('ABSPATH') || exit;

define('AJAX_SNIPPETS_VERSION', '2.7');
define('AJAX_SNIPPETS_PLUGIN', __FILE__);
define('AJAX_SNIPPETS_DIR', plugin_dir_path(AJAX_SNIPPETS_PLUGIN));
define('AJAX_SNIPPETS_URL', plugin_dir_url(AJAX_SNIPPETS_PLUGIN));

require_once AJAX_SNIPPETS_DIR . 'vendor/autoload.php';

require_once AJAX_SNIPPETS_DIR . 'includes/i18n.php';
require_once AJAX_SNIPPETS_DIR . 'includes/updater.php';
require_once AJAX_SNIPPETS_DIR . 'includes/assets.php';
require_once AJAX_SNIPPETS_DIR . 'includes/ajax-handlers.php';
require_once AJAX_SNIPPETS_DIR . 'includes/admin-menu.php';
