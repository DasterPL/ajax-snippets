<?php

defined('ABSPATH') || exit;

/**
 * Settings page for the MCP integration. Hidden by default — only shown when
 * `define('AJAX_SNIPPETS_MCP_SHOW_UI', true)` is set in wp-config.php. The
 * panel is a debug/inspection tool; the integration runs fully automatically
 * without any UI interaction.
 *
 * When enabled, the panel exposes:
 *   - Enable/disable toggle + run-as user selector
 *   - Registry URL (read-only — set by hardcoded default or constant override)
 *   - Status indicator (unregistered / pending / enabled / disabled)
 *   - Manual actions: generate keypair, register now, refresh admin keys
 *   - Cached admin keys list (read-only)
 *   - Recent local audit log table
 *
 * All actions go through admin-post.php with nonces; no AJAX needed here.
 */

add_action('admin_menu', 'ajax_snippets_mcp_admin_menu', 20);
add_action('admin_post_ajax_snippets_mcp_action', 'ajax_snippets_mcp_handle_admin_post');

function ajax_snippets_mcp_admin_menu()
{
    add_submenu_page(
        'ajax-snippets',
        __('MCP Integration', 'ajax-snippets'),
        __('MCP', 'ajax-snippets'),
        'manage_options',
        'ajax-snippets-mcp',
        'ajax_snippets_mcp_render_admin_page'
    );
}

function ajax_snippets_mcp_handle_admin_post()
{
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.', 403);
    }
    check_admin_referer('ajax_snippets_mcp_admin');

    $action = isset($_POST['mcp_action']) ? sanitize_key($_POST['mcp_action']) : '';
    $notice = '';
    $notice_type = 'success';

    try {
        switch ($action) {
            case 'save_settings':
                $enabled = !empty($_POST['enabled']);
                $user_id = isset($_POST['run_as_user']) ? (int) $_POST['run_as_user'] : 1;
                update_option(AJAX_SNIPPETS_MCP_OPT_ENABLED, $enabled, true);
                update_option(AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER, $user_id, true);
                $notice = __('Settings saved.', 'ajax-snippets');
                break;

            case 'generate_keypair':
                $info = ajax_snippets_mcp_generate_keypair();
                $notice = sprintf(
                    __('Keypair generated. Fingerprint: %s', 'ajax-snippets'),
                    $info['fp']
                );
                break;

            case 'register':
                if (!ajax_snippets_mcp_has_keypair()) {
                    throw new \RuntimeException('Generate a keypair first.');
                }
                $resp = ajax_snippets_mcp_registry_self_register(true);
                $status = is_array($resp) && isset($resp['status']) ? $resp['status'] : '?';
                $notice = sprintf(__('Registered with registry. Status: %s', 'ajax-snippets'), $status);
                break;

            case 'refresh_keys':
                $refreshed = ajax_snippets_mcp_registry_refresh_admin_keys(true);
                $notice = $refreshed
                    ? __('Admin keys refreshed.', 'ajax-snippets')
                    : __('No changes (key list already up to date).', 'ajax-snippets');
                break;

            default:
                throw new \RuntimeException('Unknown action.');
        }
    } catch (\Throwable $e) {
        $notice = $e->getMessage();
        $notice_type = 'error';
    }

    $back = add_query_arg(
        [
            'page'   => 'ajax-snippets-mcp',
            'notice' => rawurlencode($notice),
            'type'   => $notice_type,
        ],
        admin_url('admin.php')
    );
    wp_safe_redirect($back);
    exit;
}

function ajax_snippets_mcp_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.', 403);
    }

    $notice      = isset($_GET['notice']) ? sanitize_text_field(rawurldecode((string) $_GET['notice'])) : '';
    $notice_type = isset($_GET['type']) && $_GET['type'] === 'error' ? 'error' : 'success';

    $enabled       = (bool) get_option(AJAX_SNIPPETS_MCP_OPT_ENABLED, false);
    $registry_url  = ajax_snippets_mcp_registry_url();
    $registry_locked_by_constant = defined('AJAX_SNIPPETS_MCP_REGISTRY_URL');
    $has_keypair   = ajax_snippets_mcp_has_keypair();
    $fp            = (string) get_option(AJAX_SNIPPETS_MCP_OPT_FP, '');
    $pubkey_b64    = (string) get_option(AJAX_SNIPPETS_MCP_OPT_PUBKEY, '');
    $status        = (string) get_option(AJAX_SNIPPETS_MCP_OPT_STATUS, 'unregistered');
    $keys_version  = (int) get_option(AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION, 0);
    $keys_updated  = (int) get_option(AJAX_SNIPPETS_MCP_OPT_KEYS_UPDATED, 0);
    $admin_keys    = ajax_snippets_mcp_get_cached_admin_keys();
    $run_as_user   = (int) get_option(AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER, 1);
    $admins        = get_users(['role' => 'administrator', 'fields' => ['ID', 'display_name', 'user_login']]);

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Ajax Snippets — MCP Integration', 'ajax-snippets'); ?></h1>

        <?php if ($notice !== ''): ?>
            <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
                <p><?php echo esc_html($notice); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ajax_snippets_mcp_admin'); ?>
            <input type="hidden" name="action" value="ajax_snippets_mcp_action">
            <input type="hidden" name="mcp_action" value="save_settings">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable MCP', 'ajax-snippets'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
                            <?php esc_html_e('Accept signed requests from the MCP bridge', 'ajax-snippets'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Registry URL', 'ajax-snippets'); ?></th>
                    <td>
                        <code><?php echo esc_html($registry_url); ?></code>
                        <p class="description">
                            <?php
                            if ($registry_locked_by_constant) {
                                esc_html_e('Set by AJAX_SNIPPETS_MCP_REGISTRY_URL in wp-config.php.', 'ajax-snippets');
                            } else {
                                esc_html_e('Plugin default. Override via AJAX_SNIPPETS_MCP_REGISTRY_URL constant in wp-config.php.', 'ajax-snippets');
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Run snippets as user', 'ajax-snippets'); ?></th>
                    <td>
                        <select name="run_as_user">
                            <?php foreach ($admins as $u): ?>
                                <option value="<?php echo (int) $u->ID; ?>" <?php selected($u->ID, $run_as_user); ?>>
                                    <?php echo esc_html($u->display_name . ' (' . $u->user_login . ', #' . $u->ID . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Which administrator account snippets run under when triggered via MCP.', 'ajax-snippets'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save settings', 'ajax-snippets')); ?>
        </form>

        <hr>
        <h2><?php esc_html_e('Identity', 'ajax-snippets'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Fingerprint', 'ajax-snippets'); ?></th>
                <td>
                    <?php if ($has_keypair): ?>
                        <code><?php echo esc_html($fp); ?></code>
                    <?php else: ?>
                        <em><?php esc_html_e('Not generated yet.', 'ajax-snippets'); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Public key (base64)', 'ajax-snippets'); ?></th>
                <td>
                    <?php if ($has_keypair): ?>
                        <textarea readonly class="large-text code" rows="2"><?php echo esc_textarea($pubkey_b64); ?></textarea>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Registry status', 'ajax-snippets'); ?></th>
                <td>
                    <strong><?php echo esc_html($status); ?></strong>
                    <?php if ($status === 'pending'): ?>
                        <p class="description">
                            <?php esc_html_e('Awaiting approval on the registry. Run:', 'ajax-snippets'); ?>
                            <code>php bin/mcp-admin approve <?php echo esc_html(substr($fp, 0, 12)); ?></code>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:.5em;">
            <?php wp_nonce_field('ajax_snippets_mcp_admin'); ?>
            <input type="hidden" name="action" value="ajax_snippets_mcp_action">
            <input type="hidden" name="mcp_action" value="generate_keypair">
            <button type="submit" class="button" <?php disabled($has_keypair); ?>>
                <?php esc_html_e('Generate keypair', 'ajax-snippets'); ?>
            </button>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:.5em;">
            <?php wp_nonce_field('ajax_snippets_mcp_admin'); ?>
            <input type="hidden" name="action" value="ajax_snippets_mcp_action">
            <input type="hidden" name="mcp_action" value="register">
            <button type="submit" class="button" <?php disabled(!$has_keypair || $registry_url === ''); ?>>
                <?php esc_html_e('Register now', 'ajax-snippets'); ?>
            </button>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
            <?php wp_nonce_field('ajax_snippets_mcp_admin'); ?>
            <input type="hidden" name="action" value="ajax_snippets_mcp_action">
            <input type="hidden" name="mcp_action" value="refresh_keys">
            <button type="submit" class="button" <?php disabled(!$has_keypair || $registry_url === ''); ?>>
                <?php esc_html_e('Refresh admin keys', 'ajax-snippets'); ?>
            </button>
        </form>

        <hr>
        <h2>
            <?php esc_html_e('Trusted admin keys', 'ajax-snippets'); ?>
            <small style="font-weight:normal;">
                <?php
                printf(
                    /* translators: %1$d version, %2$s updated time */
                    esc_html__('(version %1$d, updated %2$s)', 'ajax-snippets'),
                    $keys_version,
                    $keys_updated ? esc_html(date_i18n('Y-m-d H:i', $keys_updated)) : '—'
                );
                ?>
            </small>
        </h2>
        <?php if ($admin_keys === []): ?>
            <p><em><?php esc_html_e('No admin keys cached yet.', 'ajax-snippets'); ?></em></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('Label', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Fingerprint', 'ajax-snippets'); ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($admin_keys as $k): ?>
                        <tr>
                            <td><?php echo esc_html((string) ($k['label'] ?? '—')); ?></td>
                            <td><code><?php echo esc_html(substr((string) $k['fp'], 0, 16) . '…'); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>
        <h2><?php esc_html_e('Recent MCP activity', 'ajax-snippets'); ?></h2>
        <?php
        global $wpdb;
        $table = ajax_snippets_mcp_audit_table();
        $rows  = $wpdb->get_results(
            "SELECT ts, caller_fp, action, status, error_type, error_msg, duration_ms, pushed_at
               FROM {$table}
              ORDER BY id DESC
              LIMIT 25",
            ARRAY_A
        );
        ?>
        <?php if (!is_array($rows) || $rows === []): ?>
            <p><em><?php esc_html_e('No MCP requests recorded.', 'ajax-snippets'); ?></em></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('When', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Caller', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Action', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Status', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Time', 'ajax-snippets'); ?></th>
                    <th><?php esc_html_e('Pushed', 'ajax-snippets'); ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', (int) $r['ts'])); ?></td>
                            <td><code><?php echo esc_html(substr((string) ($r['caller_fp'] ?? '—'), 0, 12)); ?></code></td>
                            <td><?php echo esc_html((string) $r['action']); ?></td>
                            <td>
                                <?php echo esc_html($r['status']); ?>
                                <?php if ($r['status'] === 'error'): ?>
                                    <br><small><?php echo esc_html((string) ($r['error_type'] ?? '') . ': ' . (string) ($r['error_msg'] ?? '')); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $r['duration_ms'] !== null ? (int) $r['duration_ms'] . ' ms' : '—'; ?></td>
                            <td><?php echo $r['pushed_at'] ? '✓' : '⏳'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
