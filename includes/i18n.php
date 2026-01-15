<?php

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'ajax-snippets',
        false,
        dirname(plugin_basename(AJAX_SNIPPETS_PLUGIN)) . '/languages'
    );
});
