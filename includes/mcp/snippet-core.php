<?php

defined('ABSPATH') || exit;

/**
 * Shared execution core for AJAX Snippets.
 *
 * Pure-ish functions that contain the snippet/batch/search logic used by BOTH
 * the admin-ajax handlers (includes/ajax-handlers.php) and the signed MCP REST
 * routes (includes/mcp/rest-routes.php). They take plain arguments and return
 * arrays (or throw \Throwable) — they NEVER call wp_send_json / WP_REST_Response.
 *
 * The caller is responsible for:
 *   - access control (AJAX: nonce + manage_options; REST: Ed25519 signature),
 *   - input parsing,
 *   - wrapping the returned array / caught throwable in the right envelope.
 *
 * Snippet helpers (pretty-table / csv-helper) are required by the callers
 * before invoking these, because the eval'd code may reference them.
 *
 * PL: wspólny rdzeń — likwiduje wcześniejszą duplikację "If you change one,
 * mirror the other" między AJAX a REST.
 */

if (!class_exists('Ajax_Snippets_No_Batch_Data_Exception')) {
    /**
     * Thrown by ajax_snippets_core_batch_next() when there is no stored batch
     * data. A dedicated type (rather than a bare \RuntimeException) lets the
     * AJAX adapter map this to its historical 422 "No batch data" response
     * without accidentally swallowing a \RuntimeException raised by the user's
     * own snippet code. Extends \RuntimeException so the REST adapter's generic
     * \Throwable handling keeps treating it exactly as before.
     */
    class Ajax_Snippets_No_Batch_Data_Exception extends \RuntimeException
    {
    }
}

if (!class_exists('Ajax_Snippets_Bad_Fetch_Result_Exception')) {
    /**
     * Thrown by ajax_snippets_core_batch_init() when fetch_code did not return
     * an array. Distinct type so the AJAX adapter can map it to 422 without
     * catching an \UnexpectedValueException thrown by the user's own snippet.
     * Extends \UnexpectedValueException so the REST path keeps the prior shape.
     */
    class Ajax_Snippets_Bad_Fetch_Result_Exception extends \UnexpectedValueException
    {
    }
}

if (!function_exists('ajax_snippets_core_batch_keys')) {
    /**
     * Per-user transient keys for batch state. Centralised so AJAX and REST
     * operate on exactly the same storage.
     *
     * @return array{data:string,index:string,prev:string}
     */
    function ajax_snippets_core_batch_keys($user_id)
    {
        $uid = (int) $user_id;
        return [
            'data'  => 'ajax-snippet-batch-data_' . $uid,
            'index' => 'ajax-snippet-batch-index_' . $uid,
            'prev'  => 'ajax-snippet-batch-prev_' . $uid,
        ];
    }
}

if (!function_exists('ajax_snippets_core_execute')) {
    /**
     * Eval a single snippet. Returns the guarded-eval result.
     *
     * @return array{output:string,return:mixed}
     * @throws \Throwable
     */
    function ajax_snippets_core_execute($code)
    {
        $result = ajax_snippets_guarded_eval((string) $code);
        return [
            'output' => (string) $result['output'],
            'return' => $result['return'],
        ];
    }
}

if (!function_exists('ajax_snippets_core_batch_init')) {
    /**
     * Run the fetch_code, store the returned array as batch data for the user.
     *
     * @return array{output:string,count:int}
     * @throws \Throwable|\UnexpectedValueException when fetch_code does not return an array
     */
    function ajax_snippets_core_batch_init($fetch_code, $user_id)
    {
        $result = ajax_snippets_guarded_eval((string) $fetch_code);
        $data = $result['return'];
        if (!is_array($data)) {
            throw new Ajax_Snippets_Bad_Fetch_Result_Exception('Fetch code must return an array.');
        }
        $keys = ajax_snippets_core_batch_keys($user_id);
        set_transient($keys['data'], $data, DAY_IN_SECONDS);
        set_transient($keys['index'], 0, DAY_IN_SECONDS);
        delete_transient($keys['prev']);
        return [
            'output' => (string) $result['output'],
            'count'  => count($data),
        ];
    }
}

if (!function_exists('ajax_snippets_core_batch_next')) {
    /**
     * Process one batch window of the stored data with process_code.
     *
     * Returns either a "done" payload (no data / index past end) or a normal
     * progress payload. The boolean 'done' key is always present.
     *
     * The eval'd process_code receives these variables: item, index, idx,
     * total, data, prev. (`idx` is an alias of `index`; both are provided so
     * the two historical call sites stay byte-compatible.)
     *
     * @return array{output:string,return?:mixed,done:bool,index:int,total:int}
     * @throws \RuntimeException when no batch data exists
     * @throws \Throwable from the eval'd code
     */
    function ajax_snippets_core_batch_next($process_code, $user_id, $index, $batch_size)
    {
        $keys = ajax_snippets_core_batch_keys($user_id);
        $data = get_transient($keys['data']);
        if (!is_array($data)) {
            throw new Ajax_Snippets_No_Batch_Data_Exception('No batch data found. Run fetch first.');
        }

        $index      = max(0, (int) $index);
        $batch_size = max(1, (int) $batch_size);
        $total      = count($data);

        if ($index >= $total) {
            delete_transient($keys['data']);
            delete_transient($keys['index']);
            delete_transient($keys['prev']);
            return [
                'output' => '',
                'done'   => true,
                'index'  => $index,
                'total'  => $total,
            ];
        }

        $prev     = get_transient($keys['prev']);
        $start    = $index;
        $end      = min($index + $batch_size, $total);
        $messages = '';
        $return   = null;
        for ($i = $start; $i < $end; $i++) {
            $item  = $data[$i];
            $index = $i;
            $idx   = $i;
            // $prev contains the previous iteration's return value.
            $result = ajax_snippets_guarded_eval(
                (string) $process_code,
                compact('item', 'index', 'idx', 'total', 'data', 'prev')
            );
            $messages .= $result['output'];
            $return    = $result['return'];
            $prev      = $return;
        }
        $next = $end;
        set_transient($keys['index'], $next, DAY_IN_SECONDS);
        set_transient($keys['prev'], $prev, DAY_IN_SECONDS);
        return [
            'output' => $messages,
            'return' => $return,
            'done'   => $next >= $total,
            'index'  => $next,
            'total'  => $total,
        ];
    }
}

if (!function_exists('ajax_snippets_core_batch_status')) {
    /**
     * Inspect stored batch state for the user.
     *
     * @return array{exists:bool,index?:int,total?:int}
     */
    function ajax_snippets_core_batch_status($user_id)
    {
        $keys = ajax_snippets_core_batch_keys($user_id);
        $data = get_transient($keys['data']);
        if (!is_array($data)) {
            return ['exists' => false];
        }
        $index = get_transient($keys['index']);
        return [
            'exists' => true,
            'index'  => (int) ($index === false ? 0 : $index),
            'total'  => count($data),
        ];
    }
}

if (!function_exists('ajax_snippets_core_search')) {
    /**
     * Search entities by source. Single canonical implementation used by both
     * the AJAX handler and the REST route — includes the full WooCommerce order
     * billing-name fallback (get_billing_first_name) that previously only lived
     * in the AJAX path.
     *
     * @return list<array{id:string,text:string}>
     */
    function ajax_snippets_core_search($source, $term)
    {
        $source  = (string) $source;
        $term    = (string) $term;
        $results = [];

        if ($source === 'user') {
            $users = get_users([
                'search' => '*' . $term . '*',
                'number' => 20,
                'fields' => ['ID', 'display_name', 'user_login'],
            ]);
            foreach ($users as $user) {
                $results[] = [
                    'id'   => (string) $user->ID,
                    'text' => $user->display_name . ' (' . $user->user_login . ', #' . $user->ID . ')',
                ];
            }
        } elseif ($source === 'post') {
            $query = new WP_Query([
                's'              => $term,
                'posts_per_page' => 20,
                'post_type'      => 'any',
                'post_status'    => 'any',
            ]);
            foreach ($query->posts as $post) {
                $results[] = [
                    'id'   => (string) $post->ID,
                    'text' => $post->post_title . ' (#' . $post->ID . ')',
                ];
            }
        } elseif ($source === 'order' && class_exists('WC_Order_Query')) {
            $query = new WC_Order_Query([
                'limit'  => 20,
                'return' => 'ids',
                'search' => $term,
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
                    'id'   => (string) $order_id,
                    'text' => '#' . $order_id . ($billing_name !== '' ? ' - ' . $billing_name : ''),
                ];
            }
        } elseif ($source === 'product' && function_exists('wc_get_products')) {
            $products = wc_get_products([
                'limit'  => 20,
                'return' => 'ids',
                'search' => $term,
                'status' => 'any',
                'type'   => ['simple', 'variable', 'variation'],
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
                    'id'   => (string) $product_id,
                    'text' => $title . ' (#' . $product_id . ')',
                ];
            }
        } elseif ($source === 'subscription') {
            $query = new WP_Query([
                's'              => $term,
                'posts_per_page' => 20,
                'post_type'      => 'shop_subscription',
                'post_status'    => 'any',
            ]);
            foreach ($query->posts as $post) {
                $results[] = [
                    'id'   => (string) $post->ID,
                    'text' => $post->post_title . ' (#' . $post->ID . ')',
                ];
            }
        }

        return $results;
    }
}
