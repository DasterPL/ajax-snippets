<?php

defined('ABSPATH') || exit;

if (!function_exists('ajax_snippets_guarded_eval')) {
    function ajax_snippets_guarded_eval($code, array $vars = [])
    {
        extract($vars, EXTR_SKIP);
        $guard_level = ob_get_level();
        $succeeded = false;

        ob_start(function ($buffer) use (&$succeeded) {
            if ($succeeded) {
                return $buffer;
            }
            if (!headers_sent()) {
                header_remove();
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
            }
            return wp_json_encode([
                'success' => false,
                'data' => [
                    'code' => 500,
                    'type' => 'early_exit',
                    'message' => 'Snippet terminated the request early (exit, die, or wp_send_json called).',
                ]
            ]);
        });

        ob_start();
        try {
            $return = eval("?>" . $code);
            $output = ob_get_level() > $guard_level + 1 ? ob_get_clean() : '';
            $succeeded = true;
            if (ob_get_level() > $guard_level) {
                ob_end_clean();
            }
            return ['output' => $output, 'return' => $return];
        } catch (\Throwable $th) {
            while (ob_get_level() > $guard_level + 1) {
                ob_end_clean();
            }
            $succeeded = true;
            if (ob_get_level() > $guard_level) {
                ob_end_clean();
            }
            throw $th;
        }
    }
}

if (!function_exists('ajax_snippets_classify_throwable')) {
    function ajax_snippets_classify_throwable(\Throwable $throwable)
    {
        $class_name = get_class($throwable);
        $short_name_pos = strrpos($class_name, '\\');
        $short_name = $short_name_pos === false ? $class_name : substr($class_name, $short_name_pos + 1);
        $type = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $short_name));

        switch (true) {
            case $throwable instanceof \ParseError:
            case $throwable instanceof \ArgumentCountError:
            case $throwable instanceof \TypeError:
            case $throwable instanceof \ValueError:
                $status = 422;
                break;
            default:
                $status = 500;
                break;
        }

        return [
            'type' => $type,
            'status' => $status
        ];
    }
}

add_action('wp_ajax_ajax_snippet_submit', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions.', 'ajax-snippets')
        ], 403);
    }

    if (isset($_POST['snippet_content'])) {
        require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
        require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
        $snippet_content = wp_unslash($_POST['snippet_content']);
        try {
            $result = ajax_snippets_guarded_eval($snippet_content);
            wp_send_json_success([
                'message' => $result['output'],
                'return' => $result['return']
            ], 200);
        } catch (\Throwable $th) {
            $error = ajax_snippets_classify_throwable($th);
            wp_send_json_error([
                'code' => $error['status'],
                'type' => $error['type'],
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
            ], $error['status']);
        }
    } else {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }
});

add_action('wp_ajax_ajax_snippet_batch_init', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions.', 'ajax-snippets')
        ], 403);
    }

    if (!isset($_POST['fetch_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
    $fetch_code = wp_unslash($_POST['fetch_code']);

    try {
        $result = ajax_snippets_guarded_eval($fetch_code);
        $data = $result['return'];
        if (!is_array($data)) {
            wp_send_json_error([
                'message' => 'Fetch code must return an array.'
            ], 422);
        }
        set_transient('ajax-snippet-batch-data_' . get_current_user_id(), $data, DAY_IN_SECONDS);
        set_transient('ajax-snippet-batch-index_' . get_current_user_id(), 0, DAY_IN_SECONDS);
        delete_transient('ajax-snippet-batch-prev_' . get_current_user_id());
        wp_send_json_success([
            'message' => $result['output'],
            'count' => count($data)
        ], 200);
    } catch (\Throwable $th) {
        $error = ajax_snippets_classify_throwable($th);
        wp_send_json_error([
            'code' => $error['status'],
            'type' => $error['type'],
            'message' => $th->getMessage(),
            'line' => $th->getLine(),
            'file' => $th->getFile(),
        ], $error['status']);
    }
});

add_action('wp_ajax_ajax_snippet_batch_next', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions.', 'ajax-snippets')
        ], 403);
    }

    if (!isset($_POST['process_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
    $process_code = wp_unslash($_POST['process_code']);

    $data = get_transient('ajax-snippet-batch-data_' . get_current_user_id());
    if (!is_array($data)) {
        wp_send_json_error([
            'message' => 'No batch data found. Run fetch first.'
        ], 422);
    }

    $index = isset($_POST['index']) ? max(0, (int) $_POST['index']) : 0;
    $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 10;
    if ($batch_size < 1) {
        $batch_size = 1;
    }
    $total = count($data);
    if ($index >= $total) {
        delete_transient('ajax-snippet-batch-data_' . get_current_user_id());
        delete_transient('ajax-snippet-batch-index_' . get_current_user_id());
        delete_transient('ajax-snippet-batch-prev_' . get_current_user_id());
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
        $prev = get_transient('ajax-snippet-batch-prev_' . get_current_user_id());
        $start_index = $index;
        $end_index = min($index + $batch_size, $total);
        for ($i = $start_index; $i < $end_index; $i++) {
            $index = $i;
            $item = $data[$i];
            // $prev contains the previous iteration's return value.
            $result = ajax_snippets_guarded_eval($process_code, compact('item', 'index', 'total', 'data', 'prev'));
            $messages .= $result['output'];
            $return = $result['return'];
            $prev = $return;
        }
        $next_index = $end_index;
        set_transient('ajax-snippet-batch-index_' . get_current_user_id(), $next_index, DAY_IN_SECONDS);
        set_transient('ajax-snippet-batch-prev_' . get_current_user_id(), $prev, DAY_IN_SECONDS);
        wp_send_json_success([
            'message' => $messages,
            'return' => $return,
            'done' => $next_index >= $total,
            'index' => $next_index,
            'total' => $total
        ], 200);
    } catch (\Throwable $th) {
        $error = ajax_snippets_classify_throwable($th);
        wp_send_json_error([
            'code' => $error['status'],
            'type' => $error['type'],
            'message' => $th->getMessage(),
            'line' => $th->getLine(),
            'file' => $th->getFile(),
        ], $error['status']);
    }
});

add_action('wp_ajax_ajax_snippet_batch_status', function () {
    check_ajax_referer('ajax_snippets_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error([
            'message' => __('Insufficient permissions.', 'ajax-snippets')
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
            'message' => __('Insufficient permissions.', 'ajax-snippets')
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
