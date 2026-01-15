<?php
/*
Plugin Name: AJAX Snippets
Description: Simple AJAX Snippets
Version: 2.0.2
Requires at least: 4.9
Tested up to: 6.9
Requires PHP: 7.0
Author: Apturn
*/

defined('ABSPATH') || exit;

define('AJAX_SNIPPETS_VERSION', '2.0.2');
define('AJAX_SNIPPETS_PLUGIN', __FILE__);
define('AJAX_SNIPPETS_DIR', plugin_dir_path(AJAX_SNIPPETS_PLUGIN));
define('AJAX_SNIPPETS_URL', plugin_dir_url(AJAX_SNIPPETS_PLUGIN));

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

add_action('wp_ajax_ajax_snippet_submit', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => 'Forbidden'
        ], 403);
    }

    if (isset($_POST['snippet_content'])) {
        require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
        $snippet_content = wp_unslash($_POST['snippet_content']);
        set_transient('ajax-snippet_' . get_current_user_id(), $snippet_content, DAY_IN_SECONDS);
        try {
            ob_start();
            $return = eval ("?>" . $snippet_content);
            $output = ob_get_clean();
            wp_send_json_success([
                'message' => $output,
                'return' => $return
            ], 200);
        } catch (\Throwable $th) {
            wp_send_json_error([
                'message' => $th->getMessage()
            ], 500);
        }
    } else {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }
    die();
});

add_action('wp_ajax_ajax_snippet_batch_init', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => 'Forbidden'
        ], 403);
    }

    if (!isset($_POST['fetch_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    $fetch_code = wp_unslash($_POST['fetch_code']);
    set_transient('ajax-snippet-batch-fetch_' . get_current_user_id(), $fetch_code, DAY_IN_SECONDS);

    try {
        ob_start();
        $data = eval ("?>" . $fetch_code);
        $output = ob_get_clean();
        if (!is_array($data)) {
            wp_send_json_error([
                'message' => 'Fetch code must return an array.'
            ], 422);
        }
        set_transient('ajax-snippet-batch-data_' . get_current_user_id(), $data, DAY_IN_SECONDS);
        set_transient('ajax-snippet-batch-index_' . get_current_user_id(), 0, DAY_IN_SECONDS);
        wp_send_json_success([
            'message' => $output,
            'count' => count($data)
        ], 200);
    } catch (\Throwable $th) {
        wp_send_json_error([
            'message' => $th->getMessage()
        ], 500);
    }
    die();
});

add_action('wp_ajax_ajax_snippet_batch_next', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => 'Forbidden'
        ], 403);
    }

    if (!isset($_POST['process_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    $process_code = wp_unslash($_POST['process_code']);
    set_transient('ajax-snippet-batch-process_' . get_current_user_id(), $process_code, DAY_IN_SECONDS);

    $data = get_transient('ajax-snippet-batch-data_' . get_current_user_id());
    if (!is_array($data)) {
        wp_send_json_error([
            'message' => 'No batch data found. Run fetch first.'
        ], 422);
    }

    $index = isset($_POST['index']) ? (int) $_POST['index'] : 0;
    $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 10;
    if ($batch_size < 1) {
        $batch_size = 1;
    }
    $total = count($data);
    if ($index >= $total) {
        delete_transient('ajax-snippet-batch-data_' . get_current_user_id());
        delete_transient('ajax-snippet-batch-index_' . get_current_user_id());
        wp_send_json_success([
            'message' => '',
            'done' => true,
            'index' => $index,
            'total' => $total
        ], 200);
    }

    try {
        $messages = '';
        $return = null;
        $start_index = $index;
        $end_index = min($index + $batch_size, $total);
        for ($i = $start_index; $i < $end_index; $i++) {
            $index = $i;
            $item = $data[$i];
            ob_start();
            $return = eval ("?>" . $process_code);
            $messages .= ob_get_clean();
        }
        $next_index = $end_index;
        set_transient('ajax-snippet-batch-index_' . get_current_user_id(), $next_index, DAY_IN_SECONDS);
        wp_send_json_success([
            'message' => $messages,
            'return' => $return,
            'done' => $next_index >= $total,
            'index' => $next_index,
            'total' => $total
        ], 200);
    } catch (\Throwable $th) {
        wp_send_json_error([
            'message' => $th->getMessage()
        ], 500);
    }
    die();
});

add_action('wp_ajax_ajax_snippet_batch_status', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => 'Forbidden'
        ], 403);
    }

    $data = get_transient('ajax-snippet-batch-data_' . get_current_user_id());
    if (!is_array($data)) {
        wp_send_json_success([
            'exists' => false
        ], 200);
    }

    $index = get_transient('ajax-snippet-batch-index_' . get_current_user_id());
    if ($index === false) {
        $index = 0;
    }

    wp_send_json_success([
        'exists' => true,
        'index' => (int) $index,
        'total' => count($data)
    ], 200);
    die();
});

add_action('wp_ajax_ajax_snippets_search', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => 'Forbidden'
        ], 403);
    }

    $source = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';
    $term = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
    $results = [];

    if ($source === 'user') {
        $users = get_users([
            'search' => '*' . $term . '*',
            'number' => 20,
            'fields' => ['ID', 'display_name', 'user_login']
        ]);
        foreach ($users as $user) {
            $results[] = [
                'id' => (string) $user->ID,
                'text' => $user->display_name . ' (' . $user->user_login . ', #' . $user->ID . ')'
            ];
        }
    } elseif ($source === 'post') {
        $query = new WP_Query([
            's' => $term,
            'posts_per_page' => 20,
            'post_type' => 'any',
            'post_status' => 'any'
        ]);
        foreach ($query->posts as $post) {
            $results[] = [
                'id' => (string) $post->ID,
                'text' => $post->post_title . ' (#' . $post->ID . ')'
            ];
        }
    } elseif ($source === 'order' && class_exists('WC_Order_Query')) {
        $query = new WC_Order_Query([
            'limit' => 20,
            'return' => 'ids',
            'search' => $term
        ]);
        $orders = $query->get_orders();
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            $billing_name = '';
            if (method_exists($order, 'get_formatted_billing_full_name')) {
                $billing_name = $order->get_formatted_billing_full_name();
            } elseif (method_exists($order, 'get_billing_first_name')) {
                $billing_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            }
            $results[] = [
                'id' => (string) $order_id,
                'text' => '#' . $order_id . ($billing_name !== '' ? ' - ' . $billing_name : '')
            ];
        }
    } elseif ($source === 'product' && function_exists('wc_get_products')) {
        $products = wc_get_products([
            'limit' => 20,
            'return' => 'ids',
            'search' => $term,
            'status' => 'any',
            'type' => ['simple', 'variable', 'variation']
        ]);
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }
            $title = $product->get_name();
            if ($product->is_type('variation')) {
                $title = 'Variation: ' . $title;
            }
            $results[] = [
                'id' => (string) $product_id,
                'text' => $title . ' (#' . $product_id . ')'
            ];
        }
    } elseif ($source === 'subscription') {
        $query = new WP_Query([
            's' => $term,
            'posts_per_page' => 20,
            'post_type' => 'shop_subscription',
            'post_status' => 'any'
        ]);
        foreach ($query->posts as $post) {
            $results[] = [
                'id' => (string) $post->ID,
                'text' => $post->post_title . ' (#' . $post->ID . ')'
            ];
        }
    }

    wp_send_json([
        'results' => $results
    ], 200);
});

add_action('admin_menu', function () {
    add_menu_page(
        'Ajax Snippets',
        'Ajax Snippets',
        'manage_options',
        'ajax-snippets',
        function () {
            include plugin_dir_path(__FILE__) . '/admin/admin-page.php';
        }
    );
    add_submenu_page(
        'ajax-snippets',
        'Ajax Snippets Batch',
        'Batch Runner',
        'manage_options',
        'ajax-snippets-batch',
        function () {
            include plugin_dir_path(__FILE__) . '/admin/batch-page.php';
        }
    );
});
