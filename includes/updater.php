<?php
defined('ABSPATH') || exit;

if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $ajax_snippets_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/DasterPL/ajax-snippets',
        AJAX_SNIPPETS_PLUGIN,
        'ajax-snippets'
    );
    $ajax_snippets_update_checker->setBranch('main');
}
