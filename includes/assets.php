<?php

defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['toplevel_page_ajax-snippets', 'ajax-snippets_page_ajax-snippets-batch'], true)) {
        return;
    }
    $settings = wp_enqueue_code_editor([
        'type' => 'application/x-httpd-php',
        'codemirror' => [
            'indentUnit' => 4,
            'tabSize' => 4,
            'mode' => 'application/x-httpd-php',
        ],
    ]);
    $codemirror_base = includes_url('js/codemirror');
    wp_enqueue_style('ajax-snippets-codemirror-hint', $codemirror_base . '/addon/hint/show-hint.css', [], AJAX_SNIPPETS_VERSION);
    wp_enqueue_script('ajax-snippets-codemirror-hint', $codemirror_base . '/addon/hint/show-hint.js', ['wp-codemirror'], AJAX_SNIPPETS_VERSION, true);
    wp_enqueue_style('ajax-snippets-codemirror-lint', $codemirror_base . '/addon/lint/lint.css', [], AJAX_SNIPPETS_VERSION);
    wp_enqueue_script('ajax-snippets-codemirror-lint', $codemirror_base . '/addon/lint/lint.js', ['wp-codemirror'], AJAX_SNIPPETS_VERSION, true);
    wp_enqueue_script('ajax-snippets-php-intelisense', AJAX_SNIPPETS_URL . 'assets/js/php_intelisense.js', ['ajax-snippets-codemirror-hint', 'ajax-snippets-codemirror-lint'], AJAX_SNIPPETS_VERSION, true);
    if (!wp_style_is('select2', 'registered')) {
        wp_register_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
    }
    if (!wp_script_is('select2', 'registered')) {
        wp_register_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
    }
    wp_enqueue_style('select2');
    wp_enqueue_script('select2');
    wp_enqueue_style('ajax-snippets', AJAX_SNIPPETS_URL . 'assets/css/style.css', ['select2'], AJAX_SNIPPETS_VERSION);
    wp_enqueue_script('ajax-snippets', AJAX_SNIPPETS_URL . 'assets/js/script.js', ['jquery', 'select2', 'ajax-snippets-php-intelisense'], AJAX_SNIPPETS_VERSION, true);

    wp_localize_script(
        'ajax-snippets',
        'ajax_snippets_plugin_params',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax_snippets_nonce'),
            'includes_url' => includes_url(),
            'editor_settings' => $settings
        ]
    );
});
