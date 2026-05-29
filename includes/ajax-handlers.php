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

/**
 * Shared guard for every admin-ajax handler: verify the nonce and that the
 * current user can manage_options, otherwise emit a 403 and die. Extracted so
 * the five handlers below share one definition of "who may run snippets".
 */
if (!function_exists('ajax_snippets_guard_admin_ajax')) {
    function ajax_snippets_guard_admin_ajax($action)
    {
        check_ajax_referer('ajax_snippets_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'ajax-snippets')
            ], 403);
        }
    }
}

add_action('wp_ajax_ajax_snippet_submit', function () {
    ajax_snippets_guard_admin_ajax('ajax_snippet_submit');

    if (isset($_POST['snippet_content'])) {
        require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
        require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
        $snippet_content = wp_unslash($_POST['snippet_content']);
        try {
            $result = ajax_snippets_core_execute($snippet_content);
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
    ajax_snippets_guard_admin_ajax('ajax_snippet_batch_init');

    if (!isset($_POST['fetch_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
    $fetch_code = wp_unslash($_POST['fetch_code']);

    try {
        $result = ajax_snippets_core_batch_init($fetch_code, get_current_user_id());
        wp_send_json_success([
            'message' => $result['output'],
            'count' => $result['count']
        ], 200);
    } catch (Ajax_Snippets_Bad_Fetch_Result_Exception $th) {
        wp_send_json_error([
            'message' => 'Fetch code must return an array.'
        ], 422);
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
    ajax_snippets_guard_admin_ajax('ajax_snippet_batch_next');

    if (!isset($_POST['process_code'])) {
        wp_send_json_error([
            'message' => 'Empty code!'
        ], 422);
    }

    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';
    $process_code = wp_unslash($_POST['process_code']);

    $index = isset($_POST['index']) ? max(0, (int) $_POST['index']) : 0;
    $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 10;

    try {
        $result = ajax_snippets_core_batch_next($process_code, get_current_user_id(), $index, $batch_size);
        // Preserve the historical AJAX envelope: 'message' (not 'output') and
        // the 'return' key only present on a progress (non-done-early) result.
        $response = [
            'message' => $result['output'],
            'done'    => $result['done'],
            'index'   => $result['index'],
            'total'   => $result['total'],
        ];
        if (array_key_exists('return', $result)) {
            $response['return'] = $result['return'];
        }
        wp_send_json_success($response, 200);
    } catch (Ajax_Snippets_No_Batch_Data_Exception $th) {
        wp_send_json_error([
            'message' => 'No batch data found. Run fetch first.'
        ], 422);
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
    ajax_snippets_guard_admin_ajax('ajax_snippet_batch_status');

    $status = ajax_snippets_core_batch_status(get_current_user_id());
    if (empty($status['exists'])) {
        wp_send_json_success([
            'exists' => false
        ], 200);
    }

    wp_send_json_success([
        'exists' => true,
        'index' => (int) $status['index'],
        'total' => (int) $status['total']
    ], 200);
});

add_action('wp_ajax_ajax_snippets_search', function () {
    ajax_snippets_guard_admin_ajax('ajax_snippets_search');

    $source = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';
    $term = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';

    $results = ajax_snippets_core_search($source, $term);

    wp_send_json([
        'results' => $results
    ], 200);
});
