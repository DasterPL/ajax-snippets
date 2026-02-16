<?php
defined('ABSPATH') || exit;

if (class_exists('\YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $api_class = '\YahnisElsts\PluginUpdateChecker\v5p6\Vcs\Api';

    $ajax_snippets_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/DasterPL/ajax-snippets',
        AJAX_SNIPPETS_PLUGIN,
        'ajax-snippets'
    );
    $ajax_snippets_update_checker->setBranch('main');
    $ajax_snippets_update_checker->getVcsApi()->enableReleaseAssets(
        '/(^|\/)ajax-snippets\.zip$/i',
        $api_class::REQUIRE_RELEASE_ASSETS
    );

    // Prevent fallback to tag/branch source archives when release asset matching fails.
    $ajax_snippets_update_checker->addFilter('vcs_update_detection_strategies', static function ($strategies) use ($api_class) {
        if (isset($strategies[$api_class::STRATEGY_LATEST_RELEASE])) {
            return array($api_class::STRATEGY_LATEST_RELEASE => $strategies[$api_class::STRATEGY_LATEST_RELEASE]);
        }
        return $strategies;
    });
}
