<?php

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        __('Ajax Snippets', 'ajax-snippets'),
        __('Ajax Snippets', 'ajax-snippets'),
        'manage_options',
        'ajax-snippets',
        function () {
            include plugin_dir_path(__FILE__) . '/../admin/admin-page.php';
        }
    );
    add_submenu_page(
        'ajax-snippets',
        __('Ajax Snippets Batch', 'ajax-snippets'),
        __('Batch Runner', 'ajax-snippets'),
        'manage_options',
        'ajax-snippets-batch',
        function () {
            include plugin_dir_path(__FILE__) . '/../admin/batch-page.php';
        }
    );
});
