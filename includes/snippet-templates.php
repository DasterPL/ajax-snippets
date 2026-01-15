<?php

defined('ABSPATH') || exit;

$has_wc = class_exists('WooCommerce');
$has_wcs = function_exists('wcs_get_subscriptions') || class_exists('WC_Subscriptions');
$has_wpml = defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress');

$post_methods = [
    ['value' => 'get_title', 'label' => 'get_title()', 'description' => 'Tytul posta'],
    ['value' => 'get_permalink', 'label' => 'get_permalink()', 'description' => 'Permalink'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Status posta'],
    ['value' => 'get_date', 'label' => 'get_date()', 'description' => 'Data posta'],
    ['value' => 'get_content', 'label' => 'get_content()', 'description' => 'Tresc posta'],
    ['value' => 'get_excerpt', 'label' => 'get_excerpt()', 'description' => 'Zajawka'],
    ['value' => 'get_slug', 'label' => 'get_slug()', 'description' => 'Slug'],
    ['value' => 'get_author_id', 'label' => 'get_author_id()', 'description' => 'ID autora'],
    ['value' => 'get_comment_count', 'label' => 'get_comment_count()', 'description' => 'Liczba komentarzy'],
    ['value' => 'get_post_type', 'label' => 'get_post_type()', 'description' => 'Typ posta'],
    ['value' => 'get_guid', 'label' => 'get_guid()', 'description' => 'GUID'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu']
];

$order_methods = [
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Kwota zamowienia'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Suma netto'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Suma podatku'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Koszt wysylki'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Suma rabatow'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Status'],
    ['value' => 'get_currency', 'label' => 'get_currency()', 'description' => 'Waluta'],
    ['value' => 'get_date_created', 'label' => 'get_date_created()', 'description' => 'Data utworzenia'],
    ['value' => 'get_date_paid', 'label' => 'get_date_paid()', 'description' => 'Data platnosci'],
    ['value' => 'get_billing_email', 'label' => 'get_billing_email()', 'description' => 'Email kupujacego'],
    ['value' => 'get_billing_phone', 'label' => 'get_billing_phone()', 'description' => 'Telefon kupujacego'],
    ['value' => 'get_payment_method', 'label' => 'get_payment_method()', 'description' => 'Metoda platnosci'],
    ['value' => 'get_customer_id', 'label' => 'get_customer_id()', 'description' => 'ID klienta'],
    ['value' => 'get_order_number', 'label' => 'get_order_number()', 'description' => 'Numer zamowienia'],
    ['value' => 'get_shipping_method', 'label' => 'get_shipping_method()', 'description' => 'Metoda wysylki'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu']
];

$product_methods = [
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Cena aktualna'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Cena regularna'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Cena promocyjna'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_quantity', 'label' => 'get_stock_quantity()', 'description' => 'Ilosc na stanie'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Status stanu'],
    ['value' => 'get_type', 'label' => 'get_type()', 'description' => 'Typ produktu'],
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Nazwa produktu'],
    ['value' => 'get_weight', 'label' => 'get_weight()', 'description' => 'Waga'],
    ['value' => 'get_length', 'label' => 'get_length()', 'description' => 'Dlugosc'],
    ['value' => 'get_width', 'label' => 'get_width()', 'description' => 'Szerokosc'],
    ['value' => 'get_height', 'label' => 'get_height()', 'description' => 'Wysokosc'],
    ['value' => 'get_status', 'label' => 'get_status()', 'description' => 'Status'],
    ['value' => 'get_catalog_visibility', 'label' => 'get_catalog_visibility()', 'description' => 'Widocznosc'],
    ['value' => 'get_permalink', 'label' => 'get_permalink()', 'description' => 'Permalink'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu']
];

$order_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Nazwa pozycji'],
    ['value' => 'get_quantity', 'label' => 'get_quantity()', 'description' => 'Ilosc'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Suma pozycji'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal pozycji'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Podatek pozycji'],
    ['value' => 'get_taxes', 'label' => 'get_taxes()', 'description' => 'Podatki'],
    ['value' => 'get_variation_id', 'label' => 'get_variation_id()', 'description' => 'ID wariantu'],
    ['value' => 'get_product_id', 'label' => 'get_product_id()', 'description' => 'ID produktu'],
    ['value' => 'get_product', 'label' => 'get_product()', 'description' => 'Obiekt produktu'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu'],
    ['value' => 'get_meta_data', 'label' => 'get_meta_data()', 'description' => 'Meta data']
];

$cart_methods = [
    ['value' => 'get_cart_contents_count', 'label' => 'get_cart_contents_count()', 'description' => 'Liczba produktow'],
    ['value' => 'get_cart_contents_total', 'label' => 'get_cart_contents_total()', 'description' => 'Suma koszyka'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Razem z podatkami'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Suma rabatow'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Suma podatkow'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Suma wysylki'],
    ['value' => 'get_cart', 'label' => 'get_cart()', 'description' => 'Zawartosc koszyka'],
    ['value' => 'get_fees', 'label' => 'get_fees()', 'description' => 'Oplaty'],
    ['value' => 'get_coupons', 'label' => 'get_coupons()', 'description' => 'Kupony'],
    ['value' => 'get_applied_coupons', 'label' => 'get_applied_coupons()', 'description' => 'Zastosowane kupony'],
    ['value' => 'get_shipping_packages', 'label' => 'get_shipping_packages()', 'description' => 'Paczki wysylkowe']
];

$cart_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Nazwa produktu'],
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Cena produktu'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Cena regularna'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Cena promocyjna'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Status stanu'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu']
];

$order_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Nazwa pozycji'],
    ['value' => 'get_quantity', 'label' => 'get_quantity()', 'description' => 'Ilosc'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Suma pozycji'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal pozycji'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Podatek pozycji'],
    ['value' => 'get_taxes', 'label' => 'get_taxes()', 'description' => 'Podatki'],
    ['value' => 'get_variation_id', 'label' => 'get_variation_id()', 'description' => 'ID wariantu'],
    ['value' => 'get_product_id', 'label' => 'get_product_id()', 'description' => 'ID produktu'],
    ['value' => 'get_product', 'label' => 'get_product()', 'description' => 'Obiekt produktu'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu'],
    ['value' => 'get_meta_data', 'label' => 'get_meta_data()', 'description' => 'Meta data']
];

$cart_methods = [
    ['value' => 'get_cart_contents_count', 'label' => 'get_cart_contents_count()', 'description' => 'Liczba produktow'],
    ['value' => 'get_cart_contents_total', 'label' => 'get_cart_contents_total()', 'description' => 'Suma koszyka'],
    ['value' => 'get_total', 'label' => 'get_total()', 'description' => 'Razem z podatkami'],
    ['value' => 'get_subtotal', 'label' => 'get_subtotal()', 'description' => 'Subtotal'],
    ['value' => 'get_discount_total', 'label' => 'get_discount_total()', 'description' => 'Suma rabatow'],
    ['value' => 'get_total_tax', 'label' => 'get_total_tax()', 'description' => 'Suma podatkow'],
    ['value' => 'get_shipping_total', 'label' => 'get_shipping_total()', 'description' => 'Suma wysylki'],
    ['value' => 'get_cart', 'label' => 'get_cart()', 'description' => 'Zawartosc koszyka'],
    ['value' => 'get_fees', 'label' => 'get_fees()', 'description' => 'Oplaty'],
    ['value' => 'get_coupons', 'label' => 'get_coupons()', 'description' => 'Kupony'],
    ['value' => 'get_applied_coupons', 'label' => 'get_applied_coupons()', 'description' => 'Zastosowane kupony'],
    ['value' => 'get_shipping_packages', 'label' => 'get_shipping_packages()', 'description' => 'Paczki wysylkowe']
];

$cart_item_methods = [
    ['value' => 'get_name', 'label' => 'get_name()', 'description' => 'Nazwa produktu'],
    ['value' => 'get_price', 'label' => 'get_price()', 'description' => 'Cena produktu'],
    ['value' => 'get_regular_price', 'label' => 'get_regular_price()', 'description' => 'Cena regularna'],
    ['value' => 'get_sale_price', 'label' => 'get_sale_price()', 'description' => 'Cena promocyjna'],
    ['value' => 'get_sku', 'label' => 'get_sku()', 'description' => 'SKU'],
    ['value' => 'get_stock_status', 'label' => 'get_stock_status()', 'description' => 'Status stanu'],
    ['value' => 'get_meta', 'label' => 'get_meta()', 'description' => 'Meta po kluczu']
];

$snippet_templates = [
    'WordPress core' => [
        'pre_var_dump' => [
            'label' => 'Dump: echo <pre> + var_dump',
            'code' => "<?php\necho '<pre>';\nvar_dump({{value}});\necho '</pre>';",
            'vars' => [
                ['name' => 'value', 'label' => 'Wyrazenie', 'default' => '', 'type' => 'text']
            ]
        ],
        'user_user_meta' => [
            'label' => 'User: var_dump user i meta',
            'code' => "<?php\n\$user = get_user_by('id', {{user_id}});\necho Ajax_Snippets_Table::render(\$user, 'User');\necho Ajax_Snippets_Table::render(get_user_meta(\$user->ID), 'User Meta');",
            'vars' => [
                ['name' => 'user_id', 'label' => 'User ID', 'default' => '', 'type' => 'number', 'source' => 'user']
            ]
        ],
        'post_post_meta' => [
            'label' => 'Post: var_dump post i meta',
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
        'post_method' => [
            'label' => 'Post: wywolaj metode',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$value = \$post ? \$post->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Post Method');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
                [
                    'name' => 'method',
                    'label' => 'Metoda',
                    'default' => 'get_title',
                    'type' => 'select',
                    'options' => $post_methods
                ],
                ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ],
        'taxonomy_terms' => [
            'label' => 'Taxonomy: pobierz termy postu',
            'code' => "<?php\n\$post = get_post({{post_id}});\n\$value = \$post ? wp_get_post_terms(\$post->ID, '{{taxonomy}}', ['fields' => 'all']) : null;\necho Ajax_Snippets_Table::render(\$value, 'Taxonomy Terms');",
            'vars' => [
                ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post'],
                ['name' => 'taxonomy', 'label' => 'Taxonomy (np. category)', 'default' => 'category', 'type' => 'text']
            ]
        ],
        'taxonomy_term_meta' => [
            'label' => 'Taxonomy: meta termu',
            'code' => "<?php\n\$term = get_term({{term_id}});\n\$value = \$term ? get_term_meta(\$term->term_id, '{{meta_key}}', true) : null;\necho Ajax_Snippets_Table::render(['{{meta_key}}' => \$value], 'Term Meta');",
            'vars' => [
                ['name' => 'term_id', 'label' => 'Term ID', 'default' => '', 'type' => 'number'],
                ['name' => 'meta_key', 'label' => 'Meta key', 'default' => '', 'type' => 'text']
            ]
        ],
        'hook_callbacks' => [
            'label' => 'Hooks: podglad callbackow akcji/filtra',
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
                ['name' => 'value', 'label' => 'Wyrazenie', 'default' => '', 'type' => 'text']
            ]
        ],
        'users_all' => [
            'label' => 'Users: pobierz wszystkich userow (ID)',
            'code' => "<?php\n\$users = get_users([\n    'fields' => 'ID'\n]);\nreturn \$users;"
        ],
        'posts_all' => [
            'label' => 'Posts: pobierz wszystkie posty (ID)',
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
                ['name' => 'value', 'label' => 'Wyrazenie', 'default' => '', 'type' => 'text']
            ]
        ],
        'user_dump' => [
            'label' => 'User: tabela user + meta',
            'code' => "<?php\n\$user = get_user_by('id', \$item);\necho Ajax_Snippets_Table::render(\$user, 'User');\necho Ajax_Snippets_Table::render(get_user_meta(\$user->ID), 'User Meta');"
        ],
        'post_dump' => [
            'label' => 'Post: tabela post + meta',
            'code' => "<?php\n\$post = get_post(\$item);\necho Ajax_Snippets_Table::render(\$post, 'Post');\necho Ajax_Snippets_Table::render(get_post_meta(\$post->ID), 'Post Meta');"
        ],
        'post_method' => [
            'label' => 'Post: metoda (batch)',
            'code' => "<?php\n\$post = get_post(\$item);\n\$value = \$post ? \$post->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Post Method');",
            'vars' => [
                [
                    'name' => 'method',
                    'label' => 'Metoda',
                    'default' => 'get_title',
                    'type' => 'select',
                    'options' => $post_methods
                ],
                ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
            ]
        ]
    ]
];

if ($has_wc) {
    $snippet_templates['WooCommerce']['wc_get_order'] = [
        'label' => 'WC: pobierz zamowienie',
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
        'label' => 'WC: wywolaj metode zamowienia',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$value = \$order ? \$order->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Order Method');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order'],
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_total',
                'type' => 'select',
                'options' => $order_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['order_item_method'] = [
        'label' => 'WC: metoda order item',
        'code' => "<?php\n\$order = wc_get_order({{order_id}});\n\$items = \$order ? \$order->get_items() : [];\n\$result = [];\nforeach (\$items as \$item_id => \$item) {\n    \$result[\$item_id] = \$item->{{method}}({{param}});\n}\necho Ajax_Snippets_Table::render(\$result, 'Order Item Method');",
        'vars' => [
            ['name' => 'order_id', 'label' => 'Order ID', 'default' => '', 'type' => 'number', 'source' => 'order'],
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $order_item_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['product_product_meta'] = [
        'label' => 'WC: var_dump product i meta',
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
        'label' => 'WC: wywolaj metode produktu',
        'code' => "<?php\n\$product = wc_get_product({{product_id}});\n\$value = \$product ? \$product->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Product Method');",
        'vars' => [
            ['name' => 'product_id', 'label' => 'Product ID', 'default' => '', 'type' => 'number', 'source' => 'product'],
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_price',
                'type' => 'select',
                'options' => $product_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['cart_method'] = [
        'label' => 'WC: metoda koszyka',
        'code' => "<?php\n\$cart = WC()->cart;\n\$value = \$cart ? \$cart->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Cart Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_cart_contents_count',
                'type' => 'select',
                'options' => $cart_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $snippet_templates['WooCommerce']['cart_item_method'] = [
        'label' => 'WC: metoda cart item',
        'code' => "<?php\n\$cart = WC()->cart;\n\$items = \$cart ? \$cart->get_cart() : [];\n\$result = [];\nforeach (\$items as \$item_key => \$item) {\n    \$product = isset(\$item['data']) ? \$item['data'] : null;\n    \$result[\$item_key] = \$product ? \$product->{{method}}({{param}}) : null;\n}\necho Ajax_Snippets_Table::render(\$result, 'Cart Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $cart_item_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_fetch_templates['WooCommerce']['wc_orders_all'] = [
        'label' => 'WC: pobierz wszystkie zamowienia',
        'code' => "<?php\n\$query = new WC_Order_Query([\n    'limit' => -1,\n    'return' => 'ids'\n]);\nreturn \$query->get_orders();"
    ];
    $batch_fetch_templates['WooCommerce']['products_all'] = [
        'label' => 'WC: pobierz wszystkie produkty (ID)',
        'code' => "<?php\n\$products = wc_get_products([\n    'limit' => -1,\n    'return' => 'ids'\n]);\nreturn \$products;"
    ];
    $batch_process_templates['WooCommerce']['order_dump'] = [
        'label' => 'Order: tabela order + meta',
        'code' => "<?php\n\$order = wc_get_order(\$item);\necho Ajax_Snippets_Table::render(\$order, 'Order');\necho Ajax_Snippets_Table::render(\$order->get_meta_data(), 'Order Meta');"
    ];
    $batch_process_templates['WooCommerce']['order_items_dump'] = [
        'label' => 'Order: items + meta',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$items = \$order ? \$order->get_items() : [];\n\$items_data = [];\nforeach (\$items as \$item_id => \$item) {\n    \$items_data[\$item_id] = [\n        'data' => \$item->get_data(),\n        'meta' => \$item->get_meta_data()\n    ];\n}\necho Ajax_Snippets_Table::render(\$items_data, 'Order Items');"
    ];
    $batch_process_templates['WooCommerce']['product_dump'] = [
        'label' => 'Product: tabela product + meta',
        'code' => "<?php\n\$product = wc_get_product(\$item);\necho Ajax_Snippets_Table::render(\$product, 'Product');\necho Ajax_Snippets_Table::render(\$product->get_meta_data(), 'Product Meta');"
    ];
    $batch_process_templates['WooCommerce']['order_method'] = [
        'label' => 'Order: metoda (batch)',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$value = \$order ? \$order->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Order Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_total',
                'type' => 'select',
                'options' => $order_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['product_method'] = [
        'label' => 'Product: metoda (batch)',
        'code' => "<?php\n\$product = wc_get_product(\$item);\n\$value = \$product ? \$product->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Product Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_price',
                'type' => 'select',
                'options' => $product_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['order_item_method'] = [
        'label' => 'Order item: metoda (batch)',
        'code' => "<?php\n\$order = wc_get_order(\$item);\n\$items = \$order ? \$order->get_items() : [];\n\$result = [];\nforeach (\$items as \$item_id => \$item) {\n    \$result[\$item_id] = \$item->{{method}}({{param}});\n}\necho Ajax_Snippets_Table::render(\$result, 'Order Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $order_item_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['cart_method'] = [
        'label' => 'Cart: metoda (batch)',
        'code' => "<?php\n\$cart = WC()->cart;\n\$value = \$cart ? \$cart->{{method}}({{param}}) : null;\necho Ajax_Snippets_Table::render(['{{method}}' => \$value], 'Cart Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_cart_contents_count',
                'type' => 'select',
                'options' => $cart_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
    $batch_process_templates['WooCommerce']['cart_item_method'] = [
        'label' => 'Cart item: metoda (batch)',
        'code' => "<?php\n\$cart = WC()->cart;\n\$items = \$cart ? \$cart->get_cart() : [];\n\$result = [];\nforeach (\$items as \$item_key => \$item) {\n    \$product = isset(\$item['data']) ? \$item['data'] : null;\n    \$result[\$item_key] = \$product ? \$product->{{method}}({{param}}) : null;\n}\necho Ajax_Snippets_Table::render(\$result, 'Cart Item Method');",
        'vars' => [
            [
                'name' => 'method',
                'label' => 'Metoda',
                'default' => 'get_name',
                'type' => 'select',
                'options' => $cart_item_methods
            ],
            ['name' => 'param', 'label' => 'Parametr (opcjonalny)', 'default' => '', 'type' => 'text', 'required' => false]
        ]
    ];
}

if ($has_wcs) {
    $snippet_templates['Woo Subscriptions']['wcs_get_subscription'] = [
        'label' => 'WCS: pobierz subskrypcje',
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
        'label' => 'WCS: pobierz wszystkie subskrypcje (ID)',
        'code' => "<?php\n\$subscriptions = wcs_get_subscriptions([\n    'subscriptions_per_page' => -1\n]);\nreturn array_map(function (\$subscription) {\n    return \$subscription->get_id();\n}, \$subscriptions);"
    ];
    $batch_process_templates['Woo Subscriptions']['wcs_subscription_dump'] = [
        'label' => 'WCS: tabela subscription + meta',
        'code' => "<?php\n\$subscription = wcs_get_subscription(\$item);\necho Ajax_Snippets_Table::render(\$subscription, 'Subscription');\necho Ajax_Snippets_Table::render(\$subscription->get_meta_data(), 'Subscription Meta');"
    ];
}

if ($has_wpml) {
    $snippet_templates['WPML']['wpml_languages'] = [
        'label' => 'WPML: lista jezykow',
        'code' => "<?php\n\$languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0]);\necho Ajax_Snippets_Table::render(\$languages, 'WPML Languages');"
    ];
    $snippet_templates['WPML']['wpml_post_translations'] = [
        'label' => 'WPML: tlumaczenia postu',
        'code' => "<?php\n\$post_id = {{post_id}};\n\$post_type = get_post_type(\$post_id);\n\$element_type = apply_filters('wpml_element_type', \$post_type);\n\$translations = apply_filters('wpml_get_element_translations', null, \$post_id, \$element_type);\necho Ajax_Snippets_Table::render(\$translations, 'WPML Translations');",
        'vars' => [
            ['name' => 'post_id', 'label' => 'Post ID', 'default' => '', 'type' => 'number', 'source' => 'post']
        ]
    ];
}
