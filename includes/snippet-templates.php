<?php

defined('ABSPATH') || exit;

$has_wc = class_exists('WooCommerce');
$has_wcs = function_exists('wcs_get_subscriptions') || class_exists('WC_Subscriptions');
$has_wpml = defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress');
$has_acf = function_exists('get_field') || class_exists('ACF');
$has_yoast = defined('WPSEO_VERSION') || class_exists('WPSEO_Options');
$has_elementor = defined('ELEMENTOR_VERSION') || class_exists('Elementor\\Plugin');
$has_cf7 = defined('WPCF7_VERSION') || class_exists('WPCF7');
$has_wpforms = defined('WPFORMS_VERSION') || class_exists('WPForms\\WPForms');
$has_rank_math = defined('RANK_MATH_VERSION') || class_exists('RankMath\\Helper');
$has_wp_rocket = defined('WP_ROCKET_VERSION') || function_exists('rocket_clean_domain');
$has_litespeed = defined('LSCWP_V') || class_exists('LiteSpeed\\Main');
$has_redirection = defined('REDIRECTION_VERSION') || class_exists('Redirection');
$has_polylang = defined('POLYLANG_VERSION') || function_exists('pll_current_language');
$has_smush = defined('WP_SMUSH_VERSION') || class_exists('Smush\\App');
$has_updraft = defined('UPDRAFTPLUS_VERSION') || class_exists('UpdraftPlus');
$has_jetpack = defined('JETPACK__VERSION') || class_exists('Jetpack');

$post_methods = [
    ['value' => 'get_title', 'label' => 'get_title()', 'description' => 'Post title'],
    ['value' => 'get_permalink', 'label' => 'get_permalink()', 'description' => 'Permalink'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Post status'],
    ['value' => 'get_date', 'label' => 'get_date()', 'description' => 'Post date'],
    ['value' => 'get_content', 'label' => 'get_content()', 'description' => 'Post content'],
    ['value' => 'get_excerpt', 'label' => 'get_excerpt()', 'description' => 'Excerpt'],
    ['value' => 'get_slug', 'label' => 'get_slug()', 'description' => 'Slug'],
    ['value' => 'get_author_id', 'label' => 'get_author_id()', 'description' => 'Author ID'],
    ['value' => 'get_comment_count', 'label' => 'get_comment_count()', 'description' => 'Comment count'],
    ['value' => 'get_post_type', 'label' => 'get_post_type()', 'description' => 'Post type'],
    ['value' => 'get_guid', 'label' => 'get_guid()', 'description' => 'GUID'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key']
];

$order_methods = [
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Order total'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Net subtotal'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Total tax'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Shipping total'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Discount total'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Status'],
    ['value' => 'get_currency', 'label' => 'get_currency()', 'description' => 'Currency'],
    ['value' => 'get_date_created', 'label' => 'get_date_created()', 'description' => 'Date created'],
    ['value' => 'get_date_paid', 'label' => 'get_date_paid()', 'description' => 'Date paid'],
    ['value' => 'get_billing_email', 'label' => 'get_billing_email()', 'description' => 'Billing email'],
    ['value' => 'get_billing_phone', 'label' => 'get_billing_phone()', 'description' => 'Billing phone'],
    ['value' => 'get_payment_method', 'label' => 'get_payment_method()', 'description' => 'Payment method'],
    ['value' => 'get_customer_id', 'label' => 'get_customer_id()', 'description' => 'Customer ID'],
    ['value' => 'get_order_number', 'label' => 'get_order_number()', 'description' => 'Order number'],
    ['value' => 'get_shipping_method', 'label' => 'get_shipping_method()', 'description' => 'Shipping method'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key']
];

$product_methods = [
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Current price'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Regular price'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Sale price'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_quantity', 'label' => 'get_stock_quantity()', 'description' => 'Stock quantity'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Stock status'],
    ['value' => 'get_type', 'label' => 'get_type()', 'description' => 'Product type'],
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Product name'],
    ['value' => 'get_weight', 'label' => 'get_weight()', 'description' => 'Weight'],
    ['value' => 'get_length', 'label' => 'get_length()', 'description' => 'Length'],
    ['value' => 'get_width', 'label' => 'get_width()', 'description' => 'Width'],
    ['value' => 'get_height', 'label' => 'get_height()', 'description' => 'Height'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Status'],
    ['value' => 'get_catalog_visibility', 'label' => 'get_catalog_visibility()', 'description' => 'Catalog visibility'],
    ['value' => 'get_permalink', 'label' => 'get_permalink()', 'description' => 'Permalink'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key']
];

$order_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Item name'],
    ['value' => 'get_quantity', 'label' => 'get_quantity()', 'description' => 'Quantity'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Line total'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Line subtotal'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Line tax'],
    ['value' => 'get_taxes', 'label' => 'get_taxes()', 'description' => 'Taxes'],
    ['value' => 'get_variation_id', 'label' => 'get_variation_id()', 'description' => 'Variation ID'],
    ['value' => 'get_product_id', 'label' => 'get_product_id()', 'description' => 'Product ID'],
    ['value' => 'get_product', 'label' => 'get_product()', 'description' => 'Product object'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key'],
    ['value' => 'get_meta_data', 'label' => 'get_meta_data()', 'description' => 'Meta data']
];

$cart_methods = [
    ['value' => 'get_cart_contents_count', 'label' => 'get_cart_contents_count()', 'description' => 'Item count'],
    ['value' => 'get_cart_contents_total', 'label' => 'get_cart_contents_total()', 'description' => 'Cart total'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Total with tax'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Discount total'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Total tax'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Shipping total'],
    ['value' => 'get_cart', 'label' => 'get_cart()', 'description' => 'Cart contents'],
    ['value' => 'get_fees', 'label' => 'get_fees()', 'description' => 'Fees'],
    ['value' => 'get_coupons', 'label' => 'get_coupons()', 'description' => 'Coupons'],
    ['value' => 'get_applied_coupons', 'label' => 'get_applied_coupons()', 'description' => 'Applied coupons'],
    ['value' => 'get_shipping_packages', 'label' => 'get_shipping_packages()', 'description' => 'Shipping packages']
];

$cart_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Product name'],
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Product price'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Regular price'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Sale price'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Stock status'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key']
];

$order_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Item name'],
    ['value' => 'get_quantity', 'label' => 'get_quantity()', 'description' => 'Quantity'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Line total'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Line subtotal'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Line tax'],
    ['value' => 'get_taxes', 'label' => 'get_taxes()', 'description' => 'Taxes'],
    ['value' => 'get_variation_id', 'label' => 'get_variation_id()', 'description' => 'Variation ID'],
    ['value' => 'get_product_id', 'label' => 'get_product_id()', 'description' => 'Product ID'],
    ['value' => 'get_product', 'label' => 'get_product()', 'description' => 'Product object'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key'],
    ['value' => 'get_meta_data', 'label' => 'get_meta_data()', 'description' => 'Meta data']
];

$cart_methods = [
    ['value' => 'get_cart_contents_count', 'label' => 'get_cart_contents_count()', 'description' => 'Item count'],
    ['value' => 'get_cart_contents_total', 'label' => 'get_cart_contents_total()', 'description' => 'Cart total'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Total with tax'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Discount total'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Total tax'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Shipping total'],
    ['value' => 'get_cart', 'label' => 'get_cart()', 'description' => 'Cart contents'],
    ['value' => 'get_fees', 'label' => 'get_fees()', 'description' => 'Fees'],
    ['value' => 'get_coupons', 'label' => 'get_coupons()', 'description' => 'Coupons'],
    ['value' => 'get_applied_coupons', 'label' => 'get_applied_coupons()', 'description' => 'Applied coupons'],
    ['value' => 'get_shipping_packages', 'label' => 'get_shipping_packages()', 'description' => 'Shipping packages']
];

$cart_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Product name'],
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Product price'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Regular price'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Sale price'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Stock status'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta by key']
];

$option_keys = [
    ['value' => 'siteurl', 'label' => 'siteurl', 'description' => 'WordPress Address (URL)'],
    ['value' => 'home', 'label' => 'home', 'description' => 'Site Address (URL)'],
    ['value' => 'blogname', 'label' => 'blogname', 'description' => 'Site title'],
    ['value' => 'blogdescription', 'label' => 'blogdescription', 'description' => 'Tagline'],
    ['value' => 'admin_email', 'label' => 'admin_email', 'description' => 'Admin email'],
    ['value' => 'timezone_string', 'label' => 'timezone_string', 'description' => 'Timezone'],
    ['value' => 'permalink_structure', 'label' => 'permalink_structure', 'description' => 'Permalink structure'],
    ['value' => 'cron', 'label' => 'cron', 'description' => 'WP-Cron schedule array'],
    ['value' => 'active_plugins', 'label' => 'active_plugins', 'description' => 'Active plugins list'],
    ['value' => 'woocommerce_currency', 'label' => 'woocommerce_currency', 'description' => 'WooCommerce currency'],
    ['value' => 'woocommerce_default_country', 'label' => 'woocommerce_default_country', 'description' => 'WooCommerce default country'],
    ['value' => 'woocommerce_store_address', 'label' => 'woocommerce_store_address', 'description' => 'WooCommerce store address'],
    ['value' => 'woocommerce_store_city', 'label' => 'woocommerce_store_city', 'description' => 'WooCommerce store city'],
    ['value' => 'woocommerce_price_decimal_sep', 'label' => 'woocommerce_price_decimal_sep', 'description' => 'WooCommerce price decimal separator'],
    ['value' => 'woocommerce_weight_unit', 'label' => 'woocommerce_weight_unit', 'description' => 'WooCommerce weight unit'],
    ['value' => 'woocommerce_dimension_unit', 'label' => 'woocommerce_dimension_unit', 'description' => 'WooCommerce dimension unit'],
    ['value' => 'woocommerce_calc_taxes', 'label' => 'woocommerce_calc_taxes', 'description' => 'WooCommerce taxes enabled'],
    ['value' => 'woocommerce_enable_guest_checkout', 'label' => 'woocommerce_enable_guest_checkout', 'description' => 'WooCommerce guest checkout'],
    ['value' => 'woocommerce_enable_coupons', 'label' => 'woocommerce_enable_coupons', 'description' => 'WooCommerce coupons enabled'],
    ['value' => 'woocommerce_checkout_page_id', 'label' => 'woocommerce_checkout_page_id', 'description' => 'WooCommerce checkout page ID'],
    ['value' => 'acf_version', 'label' => 'acf_version', 'description' => 'ACF version'],
    ['value' => 'acf_pro', 'label' => 'acf_pro', 'description' => 'ACF Pro flag'],
    ['value' => 'acf_json_save_file', 'label' => 'acf_json_save_file', 'description' => 'ACF JSON save path'],
    ['value' => 'wpseo', 'label' => 'wpseo', 'description' => 'Yoast SEO options array'],
    ['value' => 'wpseo_titles', 'label' => 'wpseo_titles', 'description' => 'Yoast SEO titles'],
    ['value' => 'wpseo_social', 'label' => 'wpseo_social', 'description' => 'Yoast SEO social'],
    ['value' => 'wpseo_xml', 'label' => 'wpseo_xml', 'description' => 'Yoast SEO XML settings'],
    ['value' => 'elementor_active_kit', 'label' => 'elementor_active_kit', 'description' => 'Elementor active kit'],
    ['value' => 'elementor_cpt_support', 'label' => 'elementor_cpt_support', 'description' => 'Elementor CPT support'],
    ['value' => 'wpcf7', 'label' => 'wpcf7', 'description' => 'Contact Form 7 options'],
    ['value' => 'wpforms_settings', 'label' => 'wpforms_settings', 'description' => 'WPForms settings'],
    ['value' => 'rank_math_options_general', 'label' => 'rank_math_options_general', 'description' => 'Rank Math general options'],
    ['value' => 'rank_math_options_titles', 'label' => 'rank_math_options_titles', 'description' => 'Rank Math titles options'],
    ['value' => 'rank_math_options_sitemap', 'label' => 'rank_math_options_sitemap', 'description' => 'Rank Math sitemap options'],
    ['value' => 'wp_rocket_settings', 'label' => 'wp_rocket_settings', 'description' => 'WP Rocket settings'],
    ['value' => 'litespeed.conf', 'label' => 'litespeed.conf', 'description' => 'LiteSpeed Cache settings'],
    ['value' => 'redirection_options', 'label' => 'redirection_options', 'description' => 'Redirection options'],
    ['value' => 'polylang', 'label' => 'polylang', 'description' => 'Polylang options'],
    ['value' => 'polylang_settings', 'label' => 'polylang_settings', 'description' => 'Polylang settings'],
    ['value' => 'wp-smush-settings', 'label' => 'wp-smush-settings', 'description' => 'Smush settings'],
    ['value' => 'updraftplus_settings', 'label' => 'updraftplus_settings', 'description' => 'UpdraftPlus settings'],
    ['value' => 'jetpack_options', 'label' => 'jetpack_options', 'description' => 'Jetpack options'],
    ['value' => 'theme_mods_current', 'label' => 'theme_mods (current theme)', 'description' => 'Theme mods for current theme'],
    ['value' => '__custom__', 'label' => 'Custom option', 'description' => 'Provide your own option key']
];

$snippet_templates = [
    'WordPress core' => [
        'pre_var_dump' => [
            'label' => 'Dump: echo <pre> + var_dump',
            'code' => "<?php\necho '<pre>';\nvar_dump({{value}});\necho '</pre>';",
            'vars' => [
                ['name' => 'value', 'label' => 'Expression', 'default' => '', 'type' => 'text']
            ]
        ],
        'user_user_meta' => [
            'label' => 'User: dump user and meta',
            'code' => "<?php\n\$user = get_user_by('id', {{user_id}});\necho Ajax_Snippets_Table::render(\$user, 'User');\necho Ajax_Snippets_Table::render(get_user_meta(\$user->ID), 'User Meta');",
            'vars' => [
                ['name' => 'user_id', 'label' => 'User ID', 'default' => '', 'type' => 'number', 'source' => 'user']
            ]
        ],
        'post_post_meta' => [
            'label' => 'Post: dump post and meta',
            'code' => "<?php\n\$post = get_post({{post_id}});\necho Ajax_Snippets_Table::render(\$post, 'Post');\necho Ajax_Snippets_Table::render(get_post_meta(\$post->ID), 'Post Meta');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
            ]
        ],
        'post_meta_single' => [
            'label' => 'Post: single meta',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$value = \$post ? get_post_meta(\$post->ID, '{{meta_key}}', true) : null;\necho Ajax_Snippets_Table::render(['{{meta_key}}' => \$value], 'Post Meta');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
                ['name' => 'meta_key', 'label' => 'Meta key', 'default' => '', 'type' => 'text']
            ]
        ],
        'option_get' => [
            'label' => 'Options: get_option table',
            'code' => "<?php\n\$option_key = '{{option_key}}';\nif (\$option_key === '__custom__') {\n    \$option_key = '{{custom_option}}';\n}\nif (\$option_key === 'theme_mods_current') {\n    \$stylesheet = get_option('stylesheet');\n    \$option_key = \$stylesheet ? 'theme_mods_' . \$stylesheet : 'theme_mods';\n}\nif (!\$option_key) {\n    echo Ajax_Snippets_Table::render([], 'Option');\n    return;\n}\n\$value = get_option(\$option_key);\necho Ajax_Snippets_Table::render([\$option_key => \$value], 'Option');",
            'vars' => [
                [
                    'name' => 'option_key',
                    'label' => 'Option',
                    'default' => 'siteurl',
                    'type' => 'select',
                    'options' => $option_keys
                ],
                ['name' => 'custom_option', 'label' => 'Custom option key', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ],
        'current_user_caps' => [
            'label' => 'User: current user roles + caps',
            'code' => "<?php\n\$user = wp_get_current_user();\n\$data = [\n    'ID' => \$user->ID,\n    'user_login' => \$user->user_login,\n    'roles' => \$user->roles,\n    'caps' => \$user->allcaps\n];\necho Ajax_Snippets_Table::render(\$data, 'Current User');"
        ],
        'options_autoload_top' => [
            'label' => 'Options: top autoloaded (size)',
            'code' => "<?php\nglobal \$wpdb;\n\$limit = max(1, (int) {{limit}});\n\$autoload_values = wp_autoload_values_to_autoload();\nif (!is_array(\$autoload_values) || empty(\$autoload_values)) {\n    \$autoload_values = ['yes'];\n}\n\$placeholders = implode(',', array_fill(0, count(\$autoload_values), '%s'));\n\$sql = \"SELECT option_name, LENGTH(option_value) AS size, autoload FROM {\$wpdb->options} WHERE autoload IN (\$placeholders) ORDER BY size DESC LIMIT %d\";\n\$params = array_merge(\$autoload_values, [\$limit]);\n\$rows = \$wpdb->get_results(\n    \$wpdb->prepare(\$sql, \$params),\n    ARRAY_A\n);\necho Ajax_Snippets_Table::render(\$rows, 'Autoloaded Options');",
            'vars' => [
                ['name' => 'limit', 'label' => 'Limit', 'default' => '20', 'type' => 'number']
            ]
        ],
        'options_autoload_total' => [
            'label' => 'Options: autoload total size',
            'code' => "<?php\nglobal \$wpdb;\n\$autoload_values = wp_autoload_values_to_autoload();\nif (!is_array(\$autoload_values) || empty(\$autoload_values)) {\n    \$autoload_values = ['yes'];\n}\n\$placeholders = implode(',', array_fill(0, count(\$autoload_values), '%s'));\n\$sql = \"SELECT SUM(LENGTH(option_value)) AS total_size, COUNT(*) AS total_count FROM {\$wpdb->options} WHERE autoload IN (\$placeholders)\";\n\$row = \$wpdb->get_row(\n    \$wpdb->prepare(\$sql, \$autoload_values),\n    ARRAY_A\n);\n\$data = [\n    'total_size_bytes' => isset(\$row['total_size']) ? (int) \$row['total_size'] : 0,\n    'total_count' => isset(\$row['total_count']) ? (int) \$row['total_count'] : 0\n];\necho Ajax_Snippets_Table::render(\$data, 'Autoload Total Size');"
        ],
        'option_owner' => [
            'label' => 'Options: find plugin usage',
            'code' => "<?php\n\$option_name = '{{option_name}}';\n\$scope = '{{scope}}';\n\$deep = '{{deep}}' === 'yes';\n\$max_files = max(1, (int) {{max_files}});\nif (!\$option_name) {\n    echo Ajax_Snippets_Table::render([], 'Option Owner');\n    return;\n}\nif (!function_exists('get_plugins')) {\n    require_once ABSPATH . 'wp-admin/includes/plugin.php';\n}\n\$all_plugins = function_exists('get_plugins') ? get_plugins() : [];\n\$active_plugins = (array) get_option('active_plugins', []);\n\$network_active = function_exists('get_site_option') ? array_keys((array) get_site_option('active_sitewide_plugins', [])) : [];\n\$active_map = array_fill_keys(array_merge(\$active_plugins, \$network_active), true);\n\$plugin_dirs = [];\n\$plugin_labels = [];\nforeach (\$all_plugins as \$file => \$data) {\n    \$dir = dirname(\$file);\n    \$dir = \$dir === '.' ? '' : \$dir;\n    \$name = isset(\$data['Name']) ? \$data['Name'] : \$file;\n    if (!isset(\$plugin_labels[\$dir])) {\n        \$plugin_labels[\$dir] = \$name;\n    }\n    if (\$scope === 'all') {\n        \$plugin_dirs[\$dir] = true;\n    } elseif (\$scope === 'active') {\n        if (isset(\$active_map[\$file])) {\n            \$plugin_dirs[\$dir] = true;\n        }\n    }\n}\nif (\$scope === 'mu' && defined('WPMU_PLUGIN_DIR')) {\n    \$plugin_dirs = ['' => true];\n}\nif (\$scope === 'all' || \$scope === 'active') {\n    if (empty(\$plugin_dirs)) {\n        echo Ajax_Snippets_Table::render([], 'Option Owner');\n        return;\n    }\n}\n\$base_paths = [];\nif (\$scope === 'mu' && defined('WPMU_PLUGIN_DIR')) {\n    \$base_paths[] = WPMU_PLUGIN_DIR;\n} else {\n    foreach (array_keys(\$plugin_dirs) as \$dir) {\n        \$base_paths[] = rtrim(WP_PLUGIN_DIR . '/' . \$dir, '/');\n    }\n}\n\$matches = [];\n\$matches_key = [];\n\$add_match = function (\$relative, \$reason) use (&\$matches, &\$matches_key, \$plugin_labels) {\n    \$plugin_dir = dirname(\$relative);\n    \$plugin_dir = \$plugin_dir === '.' ? '' : \$plugin_dir;\n    \$plugin_name = isset(\$plugin_labels[\$plugin_dir]) ? \$plugin_labels[\$plugin_dir] : (\$plugin_dir ?: 'mu-plugins');\n    \$key = \$plugin_name . '|' . \$relative . '|' . \$reason;\n    if (isset(\$matches_key[\$key])) {\n        return;\n    }\n    \$matches_key[\$key] = true;\n    \$matches[] = [\n        'plugin' => \$plugin_name,\n        'file' => \$relative,\n        'reason' => \$reason\n    ];\n};\n\$const_values = [];\n\$const_usages = [];\n\$var_string_map = [];\n\$var_const_map = [];\n\$var_usages = [];\n\$scanned = 0;\nforeach (\$base_paths as \$base) {\n    if (!is_dir(\$base)) {\n        continue;\n    }\n    \$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\$base, FilesystemIterator::SKIP_DOTS));\n    foreach (\$iterator as \$file) {\n        if (\$scanned >= \$max_files) {\n            break 2;\n        }\n        if (!\$file->isFile() || \$file->getExtension() !== 'php') {\n            continue;\n        }\n        \$scanned++;\n        \$path = \$file->getPathname();\n        \$contents = @file_get_contents(\$path);\n        if (\$contents === false) {\n            continue;\n        }\n        \$relative = str_replace(WP_PLUGIN_DIR . '/', '', \$path);\n        \$reasons = [];\n        if (preg_match('/[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]/', \$contents)) {\n            \$reasons[] = 'literal';\n        }\n        \$consts = [];\n        if (preg_match_all('/define\\(\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*,\\s*[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]\\s*\\)/', \$contents, \$m)) {\n            \$consts = array_unique(\$m[1]);\n        }\n        foreach (\$consts as \$const) {\n            if (preg_match('/get_option\\s*\\(\\s*' . preg_quote(\$const, '/') . '\\s*[\\),]/', \$contents)) {\n                \$reasons[] = 'define->get_option';\n                break;\n            }\n        }\n        \$vars = [];\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]/', \$contents, \$m)) {\n            \$vars = array_unique(\$m[1]);\n        }\n        foreach (\$vars as \$var) {\n            if (preg_match('/get_option\\s*\\(\\s*\\$' . preg_quote(\$var, '/') . '\\s*[\\),]/', \$contents)) {\n                \$reasons[] = 'var->get_option';\n                break;\n            }\n        }\n        if (!empty(\$reasons)) {\n            \$add_match(\$relative, implode(', ', array_unique(\$reasons)));\n        }\n        if (!\$deep) {\n            continue;\n        }\n        if (preg_match_all('/define\\(\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*,\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*\\)/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$const_values[\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/get_option\\s*\\(\\s*([A-Z0-9_]+)\\s*[\\),]/', \$contents, \$m)) {\n            foreach (array_unique(\$m[1]) as \$const) {\n                \$const_usages[] = ['file' => \$relative, 'const' => \$const];\n            }\n        }\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*;/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$var_string_map[\$relative][\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*([A-Z0-9_]+)\\s*;/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$var_const_map[\$relative][\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/get_option\\s*\\(\\s*\\$([A-Za-z0-9_]+)\\s*[\\),]/', \$contents, \$m)) {\n            foreach (array_unique(\$m[1]) as \$var) {\n                \$var_usages[] = ['file' => \$relative, 'var' => \$var];\n            }\n        }\n    }\n}\nif (\$deep) {\n    foreach (\$const_usages as \$usage) {\n        \$const = \$usage['const'];\n        if (isset(\$const_values[\$const]) && \$const_values[\$const] === \$option_name) {\n            \$add_match(\$usage['file'], 'const->get_option (cross-file)');\n        }\n    }\n    foreach (\$var_usages as \$usage) {\n        \$file = \$usage['file'];\n        \$var = \$usage['var'];\n        if (isset(\$var_string_map[\$file][\$var]) && \$var_string_map[\$file][\$var] === \$option_name) {\n            \$add_match(\$file, 'var->get_option (deep)');\n            continue;\n        }\n        if (isset(\$var_const_map[\$file][\$var])) {\n            \$const = \$var_const_map[\$file][\$var];\n            if (isset(\$const_values[\$const]) && \$const_values[\$const] === \$option_name) {\n                \$add_match(\$file, 'var->const->get_option (deep)');\n            }\n        }\n    }\n}\necho Ajax_Snippets_Table::render([\n    'option' => \$option_name,\n    'scope' => \$scope,\n    'deep' => \$deep,\n    'scanned_files' => \$scanned,\n    'matches' => \$matches\n], 'Option Owner');",
            'vars' => [
                ['name' => 'option_name', 'label' => 'Option name', 'default' => '', 'type' => 'text'],
                [
                    'name' => 'scope',
                    'label' => 'Scope',
                    'default' => 'active',
                    'type' => 'select',
                    'options' => [
                        ['value' => 'active', 'label' => 'Active plugins'],
                        ['value' => 'all', 'label' => 'All plugins'],
                        ['value' => 'mu', 'label' => 'MU plugins']
                    ]
                ],
                [
                    'name' => 'deep',
                    'label' => 'Deep scan',
                    'default' => 'no',
                    'type' => 'select',
                    'options' => [
                        ['value' => 'no', 'label' => 'No'],
                        ['value' => 'yes', 'label' => 'Yes']
                    ]
                ],
                ['name' => 'max_files', 'label' => 'Max files', 'default' => '2000', 'type' => 'number']
            ]
        ],
        'rewrite_rules' => [
            'label' => 'Rewrite: rules + matched rule',
            'code' => "<?php\nglobal \$wp_rewrite, \$wp;\n\$rules = \$wp_rewrite ? \$wp_rewrite->wp_rewrite_rules() : [];\n\$data = [\n    'matched_rule' => isset(\$wp->matched_rule) ? \$wp->matched_rule : '',\n    'matched_query' => isset(\$wp->matched_query) ? \$wp->matched_query : '',\n    'request' => isset(\$wp->request) ? \$wp->request : '',\n    'rules' => \$rules\n];\necho Ajax_Snippets_Table::render(\$data, 'Rewrite Rules');"
        ],
        'transients_list' => [
            'label' => 'Transients: list by prefix',
            'code' => "<?php\nglobal \$wpdb;\n\$prefix = '{{prefix}}';\n\$limit = max(1, (int) {{limit}});\n\$like = \$wpdb->esc_like('_transient_' . \$prefix) . '%';\n\$rows = \$wpdb->get_results(\n    \$wpdb->prepare(\n        \"SELECT option_name, LENGTH(option_value) AS size FROM {\$wpdb->options} WHERE option_name LIKE %s ORDER BY size DESC LIMIT %d\",\n        \$like,\n        \$limit\n    ),\n    ARRAY_A\n);\nforeach (\$rows as &\$row) {\n    \$row['transient'] = preg_replace('/^_transient_/', '', \$row['option_name']);\n}\nunset(\$row);\necho Ajax_Snippets_Table::render(\$rows, 'Transients');",
            'vars' => [
                ['name' => 'prefix', 'label' => 'Prefix', 'default' => '', 'type' => 'text', 'required' => false],
                ['name' => 'limit', 'label' => 'Limit', 'default' => '20', 'type' => 'number']
            ]
        ],
        'cron_events' => [
            'label' => 'Cron: events list',
            'code' => "<?php\n\$hook_filter = '{{hook}}';\n\$cron = _get_cron_array();\n\$items = [];\nif (is_array(\$cron)) {\n    foreach (\$cron as \$timestamp => \$hooks) {\n        foreach (\$hooks as \$hook => \$events) {\n            if (\$hook_filter && \$hook !== \$hook_filter) {\n                continue;\n            }\n            foreach (\$events as \$event) {\n                \$items[] = [\n                    'hook' => \$hook,\n                    'timestamp' => \$timestamp,\n                    'date' => date('Y-m-d H:i:s', \$timestamp),\n                    'schedule' => isset(\$event['schedule']) ? \$event['schedule'] : '',\n                    'args' => isset(\$event['args']) ? \$event['args'] : []\n                ];\n            }\n        }\n    }\n}\necho Ajax_Snippets_Table::render(\$items, 'Cron Events');",
            'vars' => [
                ['name' => 'hook', 'label' => 'Hook (optional)', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ],
        'rest_routes' => [
            'label' => 'REST: routes + callbacks',
            'code' => "<?php\n\$server = rest_get_server();\n\$routes = \$server->get_routes();\n\$items = [];\nforeach (\$routes as \$route => \$handlers) {\n    foreach (\$handlers as \$handler) {\n        if (!is_array(\$handler)) {\n            continue;\n        }\n        \$methods = '';\n        if (isset(\$handler['methods']) && is_array(\$handler['methods'])) {\n            \$methods = implode(',', array_keys(\$handler['methods']));\n        }\n        \$callback = isset(\$handler['callback']) ? \$handler['callback'] : null;\n        if (is_string(\$callback)) {\n            \$callback_label = \$callback;\n        } elseif (is_array(\$callback)) {\n            \$callback_label = is_object(\$callback[0]) ? get_class(\$callback[0]) . '->' . \$callback[1] : \$callback[0] . '::' . \$callback[1];\n        } elseif (\$callback instanceof Closure) {\n            \$callback_label = 'Closure';\n        } else {\n            \$callback_label = '';\n        }\n        \$items[] = [\n            'route' => \$route,\n            'methods' => \$methods,\n            'callback' => \$callback_label\n        ];\n    }\n}\necho Ajax_Snippets_Table::render(\$items, 'REST Routes');"
        ],
        'post_meta_overview' => [
            'label' => 'Post: meta keys + size preview',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$items = [];\nif (\$post) {\n    \$meta = get_post_meta(\$post->ID);\n    foreach (\$meta as \$key => \$values) {\n        \$value = count(\$values) === 1 ? \$values[0] : \$values;\n        \$raw = maybe_unserialize(\$value);\n        if (is_scalar(\$raw) || \$raw === null) {\n            \$text = (string) \$raw;\n        } else {\n            \$text = wp_json_encode(\$raw);\n        }\n        \$items[] = [\n            'meta_key' => \$key,\n            'size' => strlen(\$text),\n            'preview' => substr(\$text, 0, 200)\n        ];\n    }\n}\necho Ajax_Snippets_Table::render(\$items, 'Post Meta Overview');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
            ]
        ],
        'attachment_meta' => [
            'label' => 'Media: attachment meta + sizes',
            'code' => "<?php\n\$attachment_id = {{attachment_id}};\n\$attachment = get_post(\$attachment_id);\n\$meta = wp_get_attachment_metadata(\$attachment_id);\n\$sizes = is_array(\$meta) && isset(\$meta['sizes']) ? \$meta['sizes'] : [];\n\$data = [\n    'attachment' => \$attachment,\n    'meta' => \$meta,\n    'sizes' => \$sizes\n];\necho Ajax_Snippets_Table::render(\$data, 'Attachment Meta');",
            'vars' => [
                ['name' => 'attachment_id', 'label' => 'Attachment ID', 'default' => '', 'type' => 'number', 'source' => 'post']
            ]
        ],
        'post_method' => [
            'label' => 'Post: call method',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$value = \$post ? \$post->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Post Method');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
                [
                    'name' => 'method',
                    'label' => 'Method',
                    'default' => 'get_title',
                    'type' => 'select',
                    'options' => $post_methods
                ],
                ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ],
        'taxonomy_terms' => [
            'label' => 'Taxonomy: get post terms',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$value = \$post ? wp_get_post_terms(\$post->ID, '{{taxonomy}}', ['fields' => 'all']) : null;\necho Ajax_Snippets_Table::render(\$value, 'Taxonomy Terms');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
                ['name' => 'taxonomy', 'label' => 'Taxonomy (e.g. category)', 'default' => 'category', 'type' => 'text']
            ]
        ],
        'taxonomy_term_meta' => [
            'label' => 'Taxonomy: term meta',
            'code' => "<?php\n\$term = get_term({{term_id}});\n\$value = \$term ? get_term_meta(\$term->term_id, '{{meta_key}}', true) : null;\necho Ajax_Snippets_Table::render(['{{meta_key}}' => \$value], 'Term Meta');",
            'vars' => [
                ['name' => 'term_id', 'label' => 'Term ID', 'default' => '', 'type' => 'number'],
                ['name' => 'meta_key', 'label' => 'Meta key', 'default' => '', 'type' => 'text']
            ]
        ],
        'hook_callbacks' => [
            'label' => 'Hooks: show callbacks for action/filter',
            'code' => "<?php\nglobal \$wp_filter;\n\$hook_name = '{{hook_name}}';\nif (!isset(\$wp_filter[\$hook_name])) {\n    echo Ajax_Snippets_Table::render([], 'Hook not found');\n    return;\n}\n\$hook = \$wp_filter[\$hook_name];\n\$callbacks = \$hook instanceof WP_Hook ? \$hook->callbacks : \$hook;\n\$items = [];\nforeach (\$callbacks as \$priority => \$group) {\n    foreach (\$group as \$id => \$data) {\n        \$callback = \$data['function'];\n        if (is_string(\$callback)) {\n            \$label = \$callback;\n        } elseif (is_array(\$callback)) {\n            if (is_object(\$callback[0])) {\n                \$label = get_class(\$callback[0]) . '->' . \$callback[1];\n            } else {\n                \$label = \$callback[0] . '::' . \$callback[1];\n            }\n        } elseif (\$callback instanceof Closure) {\n            \$label = 'Closure';\n        } else {\n            \$label = (string) \$id;\n        }\n        \$items[] = [\n            'priority' => \$priority,\n            'callback' => \$label,\n            'accepted_args' => \$data['accepted_args']\n        ];\n    }\n}\necho Ajax_Snippets_Table::render(\$items, 'Hooks: ' . \$hook_name);",
            'vars' => [
                ['name' => 'hook_name', 'label' => 'Hook name', 'default' => 'init', 'type' => 'text']
            ]
        ]
    ]
];

$batch_fetch_templates = [
    'WordPress core' => [
        'pre_var_dump' => [
            'label' => 'Dump: echo <pre> + var_dump',
            'code' => "<?php\necho '<pre>';\nvar_dump({{value}});\necho '</pre>';",
            'vars' => [
                ['name' => 'value', 'label' => 'Expression', 'default' => '', 'type' => 'text']
            ]
        ],
        'options_autoload_all' => [
            'label' => 'Options: autoloaded (names)',
            'code' => "<?php\nglobal \$wpdb;\n\$autoload_values = wp_autoload_values_to_autoload();\nif (!is_array(\$autoload_values) || empty(\$autoload_values)) {\n    \$autoload_values = ['yes'];\n}\n\$placeholders = implode(',', array_fill(0, count(\$autoload_values), '%s'));\n\$sql = \"SELECT option_name FROM {\$wpdb->options} WHERE autoload IN (\$placeholders)\";\n\$names = \$wpdb->get_col(\n    \$wpdb->prepare(\$sql, \$autoload_values)\n);\nreturn array_values(array_filter((array) \$names));"
        ],
        'options_all' => [
            'label' => 'Options: all names',
            'code' => "<?php\nglobal \$wpdb;\n\$names = \$wpdb->get_col(\"SELECT option_name FROM {\$wpdb->options}\");\nreturn array_values(array_filter((array) \$names));"
        ],
        'users_all' => [
            'label' => 'Users: get all users (ID)',
            'code' => "<?php\n\$users = get_users([\n    'fields' => 'ID'\n]);\nreturn \$users;"
        ],
        'posts_all' => [
            'label' => 'Posts: get all posts (ID)',
            'code' => "<?php\n\$posts = get_posts([\n    'numberposts' => -1,\n    'fields' => 'ids'\n]);\nreturn \$posts;"
        ]
    ]
];

$batch_process_templates = [
    'WordPress core' => [
        'pre_var_dump' => [
            'label' => 'Dump: echo <pre> + var_dump',
            'code' => "<?php\necho '<pre>';\nvar_dump({{value}});\necho '</pre>';",
            'vars' => [
                ['name' => 'value', 'label' => 'Expression', 'default' => '', 'type' => 'text']
            ]
        ],
        'option_owner_batch' => [
            'label' => 'Options: find plugin usage (batch)',
            'code' => "<?php\n\$option_name = \$item;\n\$scope = '{{scope}}';\n\$mode = '{{mode}}';\n\$deep = \$mode === 'deep';\n\$light = \$mode === 'light';\n\$max_files = max(1, (int) {{max_files}});\nif (!\$option_name) {\n    echo Ajax_Snippets_Table::render([], 'Option Owner');\n    return;\n}\nif (!function_exists('get_plugins')) {\n    require_once ABSPATH . 'wp-admin/includes/plugin.php';\n}\n\$all_plugins = function_exists('get_plugins') ? get_plugins() : [];\n\$active_plugins = (array) get_option('active_plugins', []);\n\$network_active = function_exists('get_site_option') ? array_keys((array) get_site_option('active_sitewide_plugins', [])) : [];\n\$active_map = array_fill_keys(array_merge(\$active_plugins, \$network_active), true);\n\$plugin_dirs = [];\n\$plugin_labels = [];\nforeach (\$all_plugins as \$file => \$data) {\n    \$dir = dirname(\$file);\n    \$dir = \$dir === '.' ? '' : \$dir;\n    \$name = isset(\$data['Name']) ? \$data['Name'] : \$file;\n    if (!isset(\$plugin_labels[\$dir])) {\n        \$plugin_labels[\$dir] = \$name;\n    }\n    if (\$scope === 'all') {\n        \$plugin_dirs[\$dir] = true;\n    } elseif (\$scope === 'active') {\n        if (isset(\$active_map[\$file])) {\n            \$plugin_dirs[\$dir] = true;\n        }\n    }\n}\nif (\$scope === 'mu' && defined('WPMU_PLUGIN_DIR')) {\n    \$plugin_dirs = ['' => true];\n}\nif (\$scope === 'all' || \$scope === 'active') {\n    if (empty(\$plugin_dirs)) {\n        echo Ajax_Snippets_Table::render([], 'Option Owner');\n        return;\n    }\n}\n\$base_paths = [];\nif (\$scope === 'mu' && defined('WPMU_PLUGIN_DIR')) {\n    \$base_paths[] = WPMU_PLUGIN_DIR;\n} else {\n    foreach (array_keys(\$plugin_dirs) as \$dir) {\n        \$base_paths[] = rtrim(WP_PLUGIN_DIR . '/' . \$dir, '/');\n    }\n}\n\$matches = [];\n\$matches_key = [];\n\$add_match = function (\$relative, \$reason) use (&\$matches, &\$matches_key, \$plugin_labels) {\n    \$plugin_dir = dirname(\$relative);\n    \$plugin_dir = \$plugin_dir === '.' ? '' : \$plugin_dir;\n    \$plugin_name = isset(\$plugin_labels[\$plugin_dir]) ? \$plugin_labels[\$plugin_dir] : (\$plugin_dir ?: 'mu-plugins');\n    \$key = \$plugin_name . '|' . \$relative . '|' . \$reason;\n    if (isset(\$matches_key[\$key])) {\n        return;\n    }\n    \$matches_key[\$key] = true;\n    \$matches[] = [\n        'plugin' => \$plugin_name,\n        'file' => \$relative,\n        'reason' => \$reason\n    ];\n};\nif (\$light) {\n    \$prefix = strtolower(preg_replace('/[^a-z0-9_]+/', '_', \$option_name));\n    \$prefix = preg_replace('/_+/', '_', \$prefix);\n    \$chunks = explode('_', trim(\$prefix, '_'));\n    \$candidates = [];\n    if (!empty(\$chunks)) {\n        \$candidates[] = \$chunks[0];\n        if (count(\$chunks) > 1) {\n            \$candidates[] = \$chunks[0] . '_' . \$chunks[1];\n        }\n    }\n    \$candidates = array_unique(array_filter(\$candidates));\n    foreach (\$all_plugins as \$file => \$data) {\n        if (\$scope === 'active' && !isset(\$active_map[\$file])) {\n            continue;\n        }\n        \$slug = dirname(\$file);\n        \$slug = \$slug === '.' ? basename(\$file, '.php') : \$slug;\n        \$name = isset(\$data['Name']) ? \$data['Name'] : \$file;\n        foreach (\$candidates as \$candidate) {\n            if (\$candidate && stripos(\$slug, \$candidate) !== false) {\n                \$matches[] = [\n                    'plugin' => \$name,\n                    'file' => \$file,\n                    'reason' => 'prefix-heuristic:' . \$candidate\n                ];\n                break;\n            }\n        }\n    }\n    echo Ajax_Snippets_Table::render([\n        'option' => \$option_name,\n        'scope' => \$scope,\n        'mode' => 'light',\n        'scanned_files' => 0,\n        'matches' => \$matches\n    ], 'Option Owner');\n    return;\n}\n\$const_values = [];\n\$const_usages = [];\n\$var_string_map = [];\n\$var_const_map = [];\n\$var_usages = [];\n\$scanned = 0;\nforeach (\$base_paths as \$base) {\n    if (!is_dir(\$base)) {\n        continue;\n    }\n    \$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\$base, FilesystemIterator::SKIP_DOTS));\n    foreach (\$iterator as \$file) {\n        if (\$scanned >= \$max_files) {\n            break 2;\n        }\n        if (!\$file->isFile() || \$file->getExtension() !== 'php') {\n            continue;\n        }\n        \$scanned++;\n        \$path = \$file->getPathname();\n        \$contents = @file_get_contents(\$path);\n        if (\$contents === false) {\n            continue;\n        }\n        \$relative = str_replace(WP_PLUGIN_DIR . '/', '', \$path);\n        \$reasons = [];\n        if (preg_match('/[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]/', \$contents)) {\n            \$reasons[] = 'literal';\n        }\n        \$consts = [];\n        if (preg_match_all('/define\\(\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*,\\s*[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]\\s*\\)/', \$contents, \$m)) {\n            \$consts = array_unique(\$m[1]);\n        }\n        foreach (\$consts as \$const) {\n            if (preg_match('/get_option\\s*\\(\\s*' . preg_quote(\$const, '/') . '\\s*[\\),]/', \$contents)) {\n                \$reasons[] = 'define->get_option';\n                break;\n            }\n        }\n        \$vars = [];\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*[\\'\\\"]' . preg_quote(\$option_name, '/') . '[\\'\\\"]/', \$contents, \$m)) {\n            \$vars = array_unique(\$m[1]);\n        }\n        foreach (\$vars as \$var) {\n            if (preg_match('/get_option\\s*\\(\\s*\\$' . preg_quote(\$var, '/') . '\\s*[\\),]/', \$contents)) {\n                \$reasons[] = 'var->get_option';\n                break;\n            }\n        }\n        if (!empty(\$reasons)) {\n            \$add_match(\$relative, implode(', ', array_unique(\$reasons)));\n        }\n        if (!\$deep) {\n            continue;\n        }\n        if (preg_match_all('/define\\(\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*,\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*\\)/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$const_values[\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/get_option\\s*\\(\\s*([A-Z0-9_]+)\\s*[\\),]/', \$contents, \$m)) {\n            foreach (array_unique(\$m[1]) as \$const) {\n                \$const_usages[] = ['file' => \$relative, 'const' => \$const];\n            }\n        }\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*[\\'\\\"]([^\\'\\\"]+)[\\'\\\"]\\s*;/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$var_string_map[\$relative][\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/\\$([A-Za-z0-9_]+)\\s*=\\s*([A-Z0-9_]+)\\s*;/', \$contents, \$m, PREG_SET_ORDER)) {\n            foreach (\$m as \$row) {\n                \$var_const_map[\$relative][\$row[1]] = \$row[2];\n            }\n        }\n        if (preg_match_all('/get_option\\s*\\(\\s*\\$([A-Za-z0-9_]+)\\s*[\\),]/', \$contents, \$m)) {\n            foreach (array_unique(\$m[1]) as \$var) {\n                \$var_usages[] = ['file' => \$relative, 'var' => \$var];\n            }\n        }\n    }\n}\nif (\$deep) {\n    foreach (\$const_usages as \$usage) {\n        \$const = \$usage['const'];\n        if (isset(\$const_values[\$const]) && \$const_values[\$const] === \$option_name) {\n            \$add_match(\$usage['file'], 'const->get_option (cross-file)');\n        }\n    }\n    foreach (\$var_usages as \$usage) {\n        \$file = \$usage['file'];\n        \$var = \$usage['var'];\n        if (isset(\$var_string_map[\$file][\$var]) && \$var_string_map[\$file][\$var] === \$option_name) {\n            \$add_match(\$file, 'var->get_option (deep)');\n            continue;\n        }\n        if (isset(\$var_const_map[\$file][\$var])) {\n            \$const = \$var_const_map[\$file][\$var];\n            if (isset(\$const_values[\$const]) && \$const_values[\$const] === \$option_name) {\n                \$add_match(\$file, 'var->const->get_option (deep)');\n            }\n        }\n    }\n}\necho Ajax_Snippets_Table::render([\n    'option' => \$option_name,\n    'scope' => \$scope,\n    'deep' => \$deep,\n    'scanned_files' => \$scanned,\n    'matches' => \$matches\n], 'Option Owner');",
            'vars' => [
                [
                    'name' => 'scope',
                    'label' => 'Scope',
                    'default' => 'active',
                    'type' => 'select',
                    'options' => [
                        ['value' => 'active', 'label' => 'Active plugins'],
                        ['value' => 'all', 'label' => 'All plugins'],
                        ['value' => 'mu', 'label' => 'MU plugins']
                    ]
                ],
                [
                    'name' => 'mode',
                    'label' => 'Mode',
                    'default' => 'normal',
                    'type' => 'select',
                    'options' => [
                        ['value' => 'light', 'label' => 'Light (prefix only)'],
                        ['value' => 'normal', 'label' => 'Normal'],
                        ['value' => 'deep', 'label' => 'Deep']
                    ]
                ],
                ['name' => 'max_files', 'label' => 'Max files', 'default' => '2000', 'type' => 'number']
            ]
        ],
        'user_dump' => [
            'label' => 'User: table user + meta',
            'code' => "<?php\n\$user = get_user_by('id', \$item);\necho Ajax_Snippets_Table::render(\$user, 'User');\necho Ajax_Snippets_Table::render(get_user_meta(\$user->ID), 'User Meta');"
        ],
        'post_dump' => [
            'label' => 'Post: table post + meta',
            'code' => "<?php\n\$post = get_post(\$item);\necho Ajax_Snippets_Table::render(\$post, 'Post');\necho Ajax_Snippets_Table::render(get_post_meta(\$post->ID), 'Post Meta');"
        ],
        'post_method' => [
            'label' => 'Post: method (batch)',
            'code' => "<?php\n\$post = get_post(\$item);\n\$value = \$post ? \$post->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Post Method');",
            'vars' => [
                [
                    'name' => 'method',
                    'label' => 'Method',
                    'default' => 'get_title',
                    'type' => 'select',
                    'options' => $post_methods
                ],
                ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ]
    ]
];

if ($has_wc) {
    $snippet_templates['WooCommerce']['wc_get_order'] = [
        'label' => 'WC: get order',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\necho Ajax_Snippets_Table::render(\$order, 'Order');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order']
        ]
    ];
    $snippet_templates['WooCommerce']['wc_order_meta'] = [
        'label' => 'WC: var_dump order meta',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\necho Ajax_Snippets_Table::render(\$order->get_meta_data(), 'Order Meta');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order']
        ]
    ];
    $snippet_templates['WooCommerce']['wc_order_meta_single'] = [
        'label' => 'WC: single meta order',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$value = \$order ? \$order->get_meta('{{meta_key}}') : null;\necho Ajax_Snippets_Table::render(['{{meta_key}}' => \$value], 'Order Meta');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order'],
            ['name' => 'meta_key', 'label' => 'Meta key', 'default' => '', 'type' => 'text']
        ]
    ];
    $snippet_templates['WooCommerce']['wc_order_items_meta'] = [
        'label' => 'WC: order items + meta',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$items = \$order ? \$order->get_items() : [];\n\$items_data = [];\nforeach (\$items as \$item_id => \$item) {\n    \$items_data[\$item_id] = [\n        'data' => \$item->get_data(),\n        'meta' => \$item->get_meta_data()\n    ];\n}\necho Ajax_Snippets_Table::render(\$items_data, 'Order Items');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order']
        ]
    ];
    $snippet_templates['WooCommerce']['order_method'] = [
        'label' => 'WC: call order method',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$value = \$order ? \$order->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Order Method');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order'],
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_total',
                'type' => 'select',
                'options' => $order_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['order_item_method'] = [
        'label' => 'WC: order item method',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$items = \$order ? \$order->get_items() : [];\n\$result = [];\nforeach (\$items as \$item_id => \$item) {\n    \$result[\$item_id] = \$item->{{method}}({{param}});\n}\necho Ajax_Snippets_Table::render(\$result, 'Order Item Method');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order'],
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $order_item_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['product_product_meta'] = [
        'label' => 'WC: dump product and meta',
        'code' => "<?php\n\$product = wc_get_product({{product_id}});\necho Ajax_Snippets_Table::render(\$product, 'Product');\necho Ajax_Snippets_Table::render(\$product->get_meta_data(), 'Product Meta');",
        'vars' => [
            ['name' => 'product_id', 'label' => 'Product ID', 'default' => '', 'type' => 'number', 'source' => 'product']
        ]
    ];
    $snippet_templates['WooCommerce']['product_meta_single'] = [
        'label' => 'WC: single meta product',
        'code' => "<?php\n\$product = wc_get_product({{product_id}});\n\$value = \$product ? \$product->get_meta('{{meta_key}}') : null;\necho Ajax_Snippets_Table::render(['{{meta_key}}' => \$value], 'Product Meta');",
        'vars' => [
            ['name' => 'product_id', 'label' => 'Product ID', 'default' => '', 'type' => 'number', 'source' => 'product'],
            ['name' => 'meta_key', 'label' => 'Meta key', 'default' => '', 'type' => 'text']
        ]
    ];
    $snippet_templates['WooCommerce']['product_method'] = [
        'label' => 'WC: call product method',
        'code' => "<?php\n\$product = wc_get_product({{product_id}});\n\$value = \$product ? \$product->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Product Method');",
        'vars' => [
            ['name' => 'product_id', 'label' => 'Product ID', 'default' => '', 'type' => 'number', 'source' => 'product'],
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_price',
                'type' => 'select',
                'options' => $product_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['cart_method'] = [
        'label' => 'WC: cart method',
        'code' => "<?php\n\$cart = WC()->cart;\n\$value = \$cart ? \$cart->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Cart Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_cart_contents_count',
                'type' => 'select',
                'options' => $cart_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['cart_item_method'] = [
        'label' => 'WC: cart item method',
        'code' => "<?php\n\$cart = WC()->cart;\n\$items = \$cart ? \$cart->get_cart() : [];\n\$result = [];\nforeach (\$items as \$item_key => \$item) {\n    \$product = isset(\$item['data']) ? \$item['data'] : null;\n    \$result[\$item_key] = \$product ? \$product->{{method}}({{param}}) : null;\n}\necho Ajax_Snippets_Table::render(\$result, 'Cart Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $cart_item_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['wc_cart_overview'] = [
        'label' => 'WC: cart contents + totals',
        'code' => "<?php\n\$cart = WC()->cart;\n\$data = [\n    'contents' => \$cart ? \$cart->get_cart() : [],\n    'totals' => \$cart ? \$cart->get_totals() : [],\n    'fees' => \$cart ? \$cart->get_fees() : []\n];\necho Ajax_Snippets_Table::render(\$data, 'Cart Overview');"
    ];
    $snippet_templates['WooCommerce']['wc_session_data'] = [
        'label' => 'WC: session data',
        'code' => "<?php\n\$session = WC()->session;\n\$data = [];\nif (\$session) {\n    if (method_exists(\$session, 'get_session_data')) {\n        \$data = \$session->get_session_data();\n    } elseif (method_exists(\$session, 'get')) {\n        \$data = [\n            'customer' => \$session->get('customer'),\n            'cart' => \$session->get('cart'),\n            'cart_totals' => \$session->get('cart_totals'),\n            'applied_coupons' => \$session->get('applied_coupons')\n        ];\n    }\n}\necho Ajax_Snippets_Table::render(\$data, 'WC Session');"
    ];
    $batch_fetch_templates['WooCommerce']['wc_orders_all'] = [
        'label' => 'WC: get all orders',
        'code' => "<?php\n\$query = new WC_Order_Query([\n    'limit' => -1,\n    'return' => 'ids'\n]);\nreturn \$query->get_orders();"
    ];
    $batch_fetch_templates['WooCommerce']['products_all'] = [
        'label' => 'WC: get all products (ID)',
        'code' => "<?php\n\$products = wc_get_products([\n    'limit' => -1,\n    'return' => 'ids'\n]);\nreturn \$products;"
    ];
    $batch_process_templates['WooCommerce']['order_dump'] = [
        'label' => 'Order: table order + meta',
        'code' => "<?php\n\$order = wc_get_order(\$item);\necho Ajax_Snippets_Table::render(\$order, 'Order');\necho Ajax_Snippets_Table::render(\$order->get_meta_data(), 'Order Meta');"
    ];
    $batch_process_templates['WooCommerce']['order_items_dump'] = [
        'label' => 'Order: items + meta',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$items = \$order ? \$order->get_items() : [];\n\$items_data = [];\nforeach (\$items as \$item_id => \$item) {\n    \$items_data[\$item_id] = [\n        'data' => \$item->get_data(),\n        'meta' => \$item->get_meta_data()\n    ];\n}\necho Ajax_Snippets_Table::render(\$items_data, 'Order Items');"
    ];
    $batch_process_templates['WooCommerce']['product_dump'] = [
        'label' => 'Product: table product + meta',
        'code' => "<?php\n\$product = wc_get_product(\$item);\necho Ajax_Snippets_Table::render(\$product, 'Product');\necho Ajax_Snippets_Table::render(\$product->get_meta_data(), 'Product Meta');"
    ];
    $batch_process_templates['WooCommerce']['order_method'] = [
        'label' => 'Order: method (batch)',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$value = \$order ? \$order->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Order Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_total',
                'type' => 'select',
                'options' => $order_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['product_method'] = [
        'label' => 'Product: method (batch)',
        'code' => "<?php\n\$product = wc_get_product(\$item);\n\$value = \$product ? \$product->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Product Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_price',
                'type' => 'select',
                'options' => $product_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['order_item_method'] = [
        'label' => 'Order item: method (batch)',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$items = \$order ? \$order->get_items() : [];\n\$result = [];\nforeach (\$items as \$item_id => \$item) {\n    \$result[\$item_id] = \$item->{{method}}({{param}});\n}\necho Ajax_Snippets_Table::render(\$result, 'Order Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $order_item_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['cart_method'] = [
        'label' => 'Cart: method (batch)',
        'code' => "<?php\n\$cart = WC()->cart;\n\$value = \$cart ? \$cart->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Cart Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_cart_contents_count',
                'type' => 'select',
                'options' => $cart_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['cart_item_method'] = [
        'label' => 'Cart item: method (batch)',
        'code' => "<?php\n\$cart = WC()->cart;\n\$items = \$cart ? \$cart->get_cart() : [];\n\$result = [];\nforeach (\$items as \$item_key => \$item) {\n    \$product = isset(\$item['data']) ? \$item['data'] : null;\n    \$result[\$item_key] = \$product ? \$product->{{method}}({{param}}) : null;\n}\necho Ajax_Snippets_Table::render(\$result, 'Cart Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Method',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $cart_item_methods
            ],
            ['name' => 'param', 'label' => 'Parameter (optional)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
}

if ($has_wcs) {
    $snippet_templates['Woo Subscriptions']['wcs_get_subscription'] = [
        'label' => 'WCS: get subscription',
        'code' => "<?php\n\$subscription = wcs_get_subscription({{subscription_id}});\necho Ajax_Snippets_Table::render(\$subscription, 'Subscription');",
        'vars' => [
            ['name' => 'subscription_id', 'label' => 'Subscription ID', 'default' => '', 'type' => 'number', 'source' => 'subscription']
        ]
    ];
    $snippet_templates['Woo Subscriptions']['wcs_subscription_meta'] = [
        'label' => 'WCS: var_dump subscription meta',
        'code' => "<?php\n\$subscription = wcs_get_subscription({{subscription_id}});\necho Ajax_Snippets_Table::render(\$subscription->get_meta_data(), 'Subscription Meta');",
        'vars' => [
            ['name' => 'subscription_id', 'label' => 'Subscription ID', 'default' => '', 'type' => 'number', 'source' => 'subscription']
        ]
    ];
    $batch_fetch_templates['Woo Subscriptions']['wcs_subscriptions_all'] = [
        'label' => 'WCS: get all subscriptions (ID)',
        'code' => "<?php\n\$subscriptions = wcs_get_subscriptions([\n    'subscriptions_per_page' => -1\n]);\nreturn array_map(function (\$subscription) {\n    return \$subscription->get_id();\n}, \$subscriptions);"
    ];
    $batch_process_templates['Woo Subscriptions']['wcs_subscription_dump'] = [
        'label' => 'WCS: table subscription + meta',
        'code' => "<?php\n\$subscription = wcs_get_subscription(\$item);\necho Ajax_Snippets_Table::render(\$subscription, 'Subscription');\necho Ajax_Snippets_Table::render(\$subscription->get_meta_data(), 'Subscription Meta');"
    ];
}

if ($has_wpml) {
    $snippet_templates['WPML']['wpml_languages'] = [
        'label' => 'WPML: language list',
        'code' => "<?php\n\$languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0]);\necho Ajax_Snippets_Table::render(\$languages, 'WPML Languages');"
    ];
    $snippet_templates['WPML']['wpml_post_translations'] = [
        'label' => 'WPML: post translations',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$post_type = get_post_type(\$post_id);\n\$element_type = apply_filters('wpml_element_type', \$post_type);\n\$translations = apply_filters('wpml_get_element_translations', null, \$post_id, \$element_type);\necho Ajax_Snippets_Table::render(\$translations, 'WPML Translations');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
}

if ($has_acf) {
    $snippet_templates['ACF']['acf_post_fields'] = [
        'label' => 'ACF: get_fields for post',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$fields = get_fields(\$post_id);\necho Ajax_Snippets_Table::render(\$fields, 'ACF Fields');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
    $snippet_templates['ACF']['acf_single_field'] = [
        'label' => 'ACF: get_field by key/name',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$value = get_field('{{field_key}}', \$post_id);\necho Ajax_Snippets_Table::render(['{{field_key}}' => \$value], 'ACF Field');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
            ['name' => 'field_key', 'label' => 'Field key/name', 'default' => '', 'type' => 'text']
        ]
    ];
    $snippet_templates['ACF']['acf_options'] = [
        'label' => 'ACF: get fields from options',
        'code' => "<?php\n\$fields = get_fields('option');\necho Ajax_Snippets_Table::render(\$fields, 'ACF Options');"
    ];
    $snippet_templates['ACF']['acf_field_groups_by_object'] = [
        'label' => 'ACF: field groups by post type/taxonomy',
        'code' => "<?php\n\$object_type = '{{object_type}}';\n\$object_name = '{{object_name}}';\n\$criteria = [];\nif (\$object_type === 'post_type') {\n    \$criteria['post_type'] = \$object_name;\n} elseif (\$object_type === 'taxonomy') {\n    \$criteria['taxonomy'] = \$object_name;\n}\n\$groups = function_exists('acf_get_field_groups') ? acf_get_field_groups(\$criteria) : [];\n\$items = [];\nforeach (\$groups as \$group) {\n    \$items[] = [\n        'ID' => isset(\$group['ID']) ? \$group['ID'] : '',\n        'title' => isset(\$group['title']) ? \$group['title'] : '',\n        'key' => isset(\$group['key']) ? \$group['key'] : ''\n    ];\n}\necho Ajax_Snippets_Table::render(\$items, 'ACF Field Groups');",
        'vars' => [
            [
                'name' => 'object_type',
                'label' => 'Object type',
                'default' => 'post_type',
                'type' => 'select',
                'options' => [
                    ['value' => 'post_type', 'label' => 'post_type'],
                    ['value' => 'taxonomy', 'label' => 'taxonomy']
                ]
            ],
            ['name' => 'object_name', 'label' => 'Name (e.g. post, product, category)', 'default' => 'post', 'type' => 'text']
        ]
    ];
}

if ($has_yoast) {
    $snippet_templates['Yoast SEO']['yoast_option'] = [
        'label' => 'Yoast: get option',
        'code' => "<?php\n\$value = class_exists('WPSEO_Options') ? WPSEO_Options::get('{{option_key}}') : null;\necho Ajax_Snippets_Table::render(['{{option_key}}' => \$value], 'Yoast Option');",
        'vars' => [
            ['name' => 'option_key', 'label' => 'Option key (e.g. title-home-wpseo)', 'default' => 'title-home-wpseo', 'type' => 'text']
        ]
    ];
    $snippet_templates['Yoast SEO']['yoast_options_group'] = [
        'label' => 'Yoast: options group (get_option)',
        'code' => "<?php\n\$group = '{{group_key}}';\n\$value = get_option(\$group);\necho Ajax_Snippets_Table::render([\$group => \$value], 'Yoast Options');",
        'vars' => [
            ['name' => 'group_key', 'label' => 'Group key', 'default' => 'wpseo_titles', 'type' => 'text']
        ]
    ];
    $snippet_templates['Yoast SEO']['yoast_post_meta'] = [
        'label' => 'Yoast: post SEO meta',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$keys = [\n    '_yoast_wpseo_title',\n    '_yoast_wpseo_metadesc',\n    '_yoast_wpseo_focuskw',\n    '_yoast_wpseo_canonical',\n    '_yoast_wpseo_opengraph-title',\n    '_yoast_wpseo_opengraph-description',\n    '_yoast_wpseo_twitter-title',\n    '_yoast_wpseo_twitter-description'\n];\n\$meta = [];\nforeach (\$keys as \$key) {\n    \$meta[\$key] = get_post_meta(\$post_id, \$key, true);\n}\necho Ajax_Snippets_Table::render(\$meta, 'Yoast Post Meta');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
}

if ($has_rank_math) {
    $snippet_templates['Rank Math']['rank_math_post_meta'] = [
        'label' => 'Rank Math: post SEO meta',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$keys = [\n    'rank_math_title',\n    'rank_math_description',\n    'rank_math_focus_keyword',\n    'rank_math_canonical_url',\n    'rank_math_robots'\n];\n\$meta = [];\nforeach (\$keys as \$key) {\n    \$meta[\$key] = get_post_meta(\$post_id, \$key, true);\n}\necho Ajax_Snippets_Table::render(\$meta, 'Rank Math Post Meta');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
}

if ($has_elementor) {
    $snippet_templates['Elementor']['elementor_options'] = [
        'label' => 'Elementor: options',
        'code' => "<?php\n\$value = get_option('elementor_active_kit');\necho Ajax_Snippets_Table::render(['elementor_active_kit' => \$value], 'Elementor Options');"
    ];
    $snippet_templates['Elementor']['elementor_post_meta'] = [
        'label' => 'Elementor: post meta',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$meta = [\n    '_elementor_data' => get_post_meta(\$post_id, '_elementor_data', true),\n    '_elementor_edit_mode' => get_post_meta(\$post_id, '_elementor_edit_mode', true),\n    '_elementor_template_type' => get_post_meta(\$post_id, '_elementor_template_type', true),\n    '_elementor_css' => get_post_meta(\$post_id, '_elementor_css', true)\n];\necho Ajax_Snippets_Table::render(\$meta, 'Elementor Meta');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
    $snippet_templates['Elementor']['elementor_active_kit'] = [
        'label' => 'Elementor: active kit',
        'code' => "<?php\n\$kit_id = get_option('elementor_active_kit');\n\$kit = \$kit_id ? get_post(\$kit_id) : null;\n\$kit_meta = \$kit_id ? [\n    '_elementor_data' => get_post_meta(\$kit_id, '_elementor_data', true),\n    '_elementor_page_settings' => get_post_meta(\$kit_id, '_elementor_page_settings', true)\n] : [];\necho Ajax_Snippets_Table::render([\n    'kit_id' => \$kit_id,\n    'kit' => \$kit,\n    'meta' => \$kit_meta\n], 'Elementor Kit');"
    ];
    $snippet_templates['Elementor']['elementor_post_widgets'] = [
        'label' => 'Elementor: post widgets list',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$raw = get_post_meta(\$post_id, '_elementor_data', true);\n\$data = is_string(\$raw) ? json_decode(\$raw, true) : \$raw;\n\$widgets = [];\n\$walk = function (\$elements) use (&\$walk, &\$widgets) {\n    if (!is_array(\$elements)) {\n        return;\n    }\n    foreach (\$elements as \$element) {\n        if (!is_array(\$element)) {\n            continue;\n        }\n        if (isset(\$element['elType']) && \$element['elType'] === 'widget') {\n            \$widgets[] = isset(\$element['widgetType']) ? \$element['widgetType'] : 'unknown';\n        }\n        if (!empty(\$element['elements'])) {\n            \$walk(\$element['elements']);\n        }\n    }\n};\n\$walk(\$data);\n\$counts = array_count_values(array_filter(\$widgets));\n\$items = [];\nforeach (\$counts as \$widget => \$count) {\n    \$items[] = ['widget' => \$widget, 'count' => \$count];\n}\necho Ajax_Snippets_Table::render([\n    'total_widgets' => array_sum(\$counts),\n    'widgets' => \$items\n], 'Elementor Widgets');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
}

if ($has_cf7) {
    $snippet_templates['Contact Form 7']['cf7_options'] = [
        'label' => 'CF7: options',
        'code' => "<?php\n\$value = get_option('wpcf7');\necho Ajax_Snippets_Table::render(['wpcf7' => \$value], 'Contact Form 7');"
    ];
}

if ($has_wpforms) {
    $snippet_templates['WPForms']['wpforms_options'] = [
        'label' => 'WPForms: settings',
        'code' => "<?php\n\$value = get_option('wpforms_settings');\necho Ajax_Snippets_Table::render(['wpforms_settings' => \$value], 'WPForms');"
    ];
}

if ($has_rank_math) {
    $snippet_templates['Rank Math']['rank_math_general'] = [
        'label' => 'Rank Math: general options',
        'code' => "<?php\n\$value = get_option('rank_math_options_general');\necho Ajax_Snippets_Table::render(['rank_math_options_general' => \$value], 'Rank Math');"
    ];
}

if ($has_wp_rocket) {
    $snippet_templates['WP Rocket']['wp_rocket_settings'] = [
        'label' => 'WP Rocket: settings',
        'code' => "<?php\n\$value = get_option('wp_rocket_settings');\necho Ajax_Snippets_Table::render(['wp_rocket_settings' => \$value], 'WP Rocket');"
    ];
}

if ($has_litespeed) {
    $snippet_templates['LiteSpeed Cache']['litespeed_settings'] = [
        'label' => 'LiteSpeed: settings',
        'code' => "<?php\n\$value = get_option('litespeed.conf');\necho Ajax_Snippets_Table::render(['litespeed.conf' => \$value], 'LiteSpeed Cache');"
    ];
}

if ($has_redirection) {
    $snippet_templates['Redirection']['redirection_options'] = [
        'label' => 'Redirection: options',
        'code' => "<?php\n\$value = get_option('redirection_options');\necho Ajax_Snippets_Table::render(['redirection_options' => \$value], 'Redirection');"
    ];
}

if ($has_polylang) {
    $snippet_templates['Polylang']['polylang_settings'] = [
        'label' => 'Polylang: settings',
        'code' => "<?php\n\$value = get_option('polylang_settings');\necho Ajax_Snippets_Table::render(['polylang_settings' => \$value], 'Polylang');"
    ];
}

if ($has_smush) {
    $snippet_templates['Smush']['smush_settings'] = [
        'label' => 'Smush: settings',
        'code' => "<?php\n\$value = get_option('wp-smush-settings');\necho Ajax_Snippets_Table::render(['wp-smush-settings' => \$value], 'Smush');"
    ];
}

if ($has_updraft) {
    $snippet_templates['UpdraftPlus']['updraft_settings'] = [
        'label' => 'UpdraftPlus: settings',
        'code' => "<?php\n\$value = get_option('updraftplus_settings');\necho Ajax_Snippets_Table::render(['updraftplus_settings' => \$value], 'UpdraftPlus');"
    ];
}

if ($has_jetpack) {
    $snippet_templates['Jetpack']['jetpack_options'] = [
        'label' => 'Jetpack: options',
        'code' => "<?php\n\$value = get_option('jetpack_options');\necho Ajax_Snippets_Table::render(['jetpack_options' => \$value], 'Jetpack');"
    ];
}
