<?php
defined('ABSPATH') || exit;

if (class_exists('\YahnisElsts\PluginUpdateChecker\v5p6\PucFactory')) {
    $ajax_snippets_update_checker = \YahnisElsts\PluginUpdateChecker\v5p6\PucFactory::buildUpdateChecker(
        'https://github.com/DasterPL/ajax-snippets',
        AJAX_SNIPPETS_PLUGIN,
        'ajax-snippets'
    );
    $ajax_snippets_update_checker->setBranch('main');
    $ajax_snippets_update_checker->getVcsApi()->enableReleaseAssets('ajax-snippets.zip', \YahnisElsts\PluginUpdateChecker\v5p6\Vcs\Api::REQUIRE_RELEASE_ASSETS);
}
