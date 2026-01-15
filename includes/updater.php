<?php

defined('ABSPATH') || exit;

if (class_exists('Puc_v5_Factory')) {
    $ajax_snippets_update_checker = Puc_v5_Factory::buildUpdateChecker(
        'https://github.com/DasterPL/ajax-snippets',
        AJAX_SNIPPETS_PLUGIN,
        'ajax-snippets'
    );
    $ajax_snippets_update_checker->setBranch('main');
}
