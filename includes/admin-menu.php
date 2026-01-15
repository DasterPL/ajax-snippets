<?php

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Ajax Snippets',
        'Ajax Snippets',
        'manage_options',
        'ajax-snippets',
        function () {
            include plugin_dir_path(__FILE__) . '/../admin/admin-page.php';
        }
    );
    add_submenu_page(
        'ajax-snippets',
        'Ajax Snippets Batch',
        'Batch Runner',
        'manage_options',
        'ajax-snippets-batch',
        function () {
            include plugin_dir_path(__FILE__) . '/../admin/batch-page.php';
        }
    );
});
