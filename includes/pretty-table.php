<?php

defined('ABSPATH') || exit;

if (!class_exists('Ajax_Snippets_Table')) {
    class Ajax_Snippets_Table
    {
        public static function render($data, $title = '', $context = '', $path_prefix = '', $base_path = '')
        {
            if ($context === '') {
                $context = self::infer_context($data, $title);
            }
            if ($base_path === '' && $path_prefix === '' && $context === '') {
                $base_path = '$data';
            }
            if (is_array($data) && self::is_wc_meta_array($data)) {
                $data = self::normalize_wc_meta_array($data);
            } elseif (is_object($data)) {
                if (method_exists($data, 'get_data')) {
                    $data = $data->get_data();
                    if ($base_path === '') {
                        $base_path = self::context_base_path($context);
                    }
                } elseif ($data instanceof WP_Post) {
                    $data = get_object_vars($data);
                    if ($base_path === '') {
                        $base_path = '$post';
                    }
                } elseif ($data instanceof WP_User) {
                    $data = get_object_vars($data);
                    if ($base_path === '') {
                        $base_path = '$user';
                    }
                } else {
                    $data = (array) $data;
                }
            }
            if (!is_array($data)) {
                $data = ['value' => $data];
            }
            $rows = '';
            foreach ($data as $key => $value) {
                $path = self::build_path($context, $path_prefix, $base_path, $key);
                $path_attr = $path !== '' ? ' data-path="' . esc_attr($path) . '" class="ajax-snippets-path"' : '';
                $rows .= '<tr><th' . $path_attr . '>' . esc_html((string) $key) . '</th><td>' . self::value($value, $context, $path) . '</td></tr>';
            }
            $caption = $title !== '' ? '<caption>' . esc_html($title) . '</caption>' : '';
            return '<table class="ajax-snippets-table">' . $caption . '<tbody>' . $rows . '</tbody></table>';
        }

        private static function value($value, $context, $path_prefix)
        {
            $type = gettype($value);
            if (is_object($value) || is_array($value)) {
                return self::render($value, '', $context, $path_prefix, '') . self::type_badge($type);
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = 'null';
            }
            return '<code>' . esc_html((string) $value) . '</code>' . self::type_badge($type);
        }

        private static function build_path($context, $path_prefix, $base_path, $key)
        {
            $key_part = self::format_key($key);
            if ($path_prefix !== '') {
                return $path_prefix . $key_part;
            }
            if ($context === 'order_meta') {
                return "\$order->get_meta('" . self::escape_key($key) . "')";
            }
            if ($context === 'product_meta') {
                return "\$product->get_meta('" . self::escape_key($key) . "')";
            }
            if ($context === 'subscription_meta') {
                return "\$subscription->get_meta('" . self::escape_key($key) . "')";
            }
            if ($context === 'post_meta') {
                return "get_post_meta(\$post->ID, '" . self::escape_key($key) . "', true)";
            }
            if ($context === 'user_meta') {
                return "get_user_meta(\$user->ID, '" . self::escape_key($key) . "', true)";
            }
            if ($base_path !== '') {
                if (in_array($context, ['post', 'user'], true) && is_string($key)) {
                    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                        return $base_path . '->' . $key;
                    }
                    return $base_path . "->{'". self::escape_key($key) . "'}";
                }
                return $base_path . $key_part;
            }
            return $key_part !== '' ? $key_part : '';
        }

        private static function format_key($key)
        {
            if (is_int($key) || ctype_digit((string) $key)) {
                return '[' . (int) $key . ']';
            }
            return "['" . self::escape_key($key) . "']";
        }

        private static function escape_key($key)
        {
            return str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $key);
        }

        private static function infer_context($data, $title)
        {
            if (is_object($data)) {
                if (class_exists('WC_Order') && $data instanceof WC_Order) {
                    return 'order';
                }
                if (class_exists('WC_Product') && $data instanceof WC_Product) {
                    return 'product';
                }
                if (class_exists('WC_Subscription') && $data instanceof WC_Subscription) {
                    return 'subscription';
                }
                if ($data instanceof WP_Post) {
                    return 'post';
                }
                if ($data instanceof WP_User) {
                    return 'user';
                }
            }
            $title = strtolower((string) $title);
            if (strpos($title, 'order meta') !== false) {
                return 'order_meta';
            }
            if (strpos($title, 'product meta') !== false) {
                return 'product_meta';
            }
            if (strpos($title, 'subscription meta') !== false) {
                return 'subscription_meta';
            }
            if (strpos($title, 'post meta') !== false) {
                return 'post_meta';
            }
            if (strpos($title, 'user meta') !== false) {
                return 'user_meta';
            }
            return '';
        }

        private static function context_base_path($context)
        {
            if ($context === 'order') {
                return '$order->get_data()';
            }
            if ($context === 'product') {
                return '$product->get_data()';
            }
            if ($context === 'subscription') {
                return '$subscription->get_data()';
            }
            return '';
        }

        private static function is_wc_meta_array($data)
        {
            if (!is_array($data) || empty($data)) {
                return false;
            }
            $first = reset($data);
            return is_object($first) && method_exists($first, 'get_data') && method_exists($first, 'get_key');
        }

        private static function normalize_wc_meta_array($data)
        {
            $normalized = [];
            foreach ($data as $meta) {
                if (!is_object($meta) || !method_exists($meta, 'get_data')) {
                    continue;
                }
                $meta_data = $meta->get_data();
                $key = isset($meta_data['key']) ? (string) $meta_data['key'] : '';
                if ($key === '') {
                    continue;
                }
                $normalized[$key] = isset($meta_data['value']) ? $meta_data['value'] : null;
            }
            return $normalized;
        }

        private static function type_badge($type)
        {
            return '<span class="ajax-snippets-type">(' . esc_html($type) . ')</span>';
        }
    }
}
