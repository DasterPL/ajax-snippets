<?php

defined('ABSPATH') || exit;

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
