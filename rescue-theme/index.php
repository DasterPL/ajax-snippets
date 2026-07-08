<?php
/**
 * Rescue placeholder theme — intentionally renders nothing.
 *
 * The rescue endpoint runs headless (WP_USE_THEMES is false) and never routes a
 * front-end request through this template. It exists so WordPress has a valid,
 * inert active theme whose functions.php cannot fatal the bootstrap.
 */
defined('ABSPATH') || exit;
