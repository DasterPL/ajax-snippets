<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <?php if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) : ?>
    <div class="notice notice-error">
        <p>
            <strong><?php echo esc_html__('Ajax Snippets is locked.', 'ajax-snippets'); ?></strong>
            <?php echo esc_html__('The constant DISALLOW_FILE_MODS is set to true in wp-config.php, which prevents this plugin from executing code.', 'ajax-snippets'); ?>
            <?php echo esc_html__('To unlock, open wp-config.php and remove or set to false:', 'ajax-snippets'); ?>
            <code>define('DISALLOW_FILE_MODS', true);</code>
        </p>
    </div>
    <?php endif; ?>
    <hr>
    <form id="snippet_form">
        <div class="snippet_nav">
            <div class="snippet_progress">
                <progress id="progress" max="0" value="0"></progress>
            </div>
            <span class="status"><?php echo esc_html__('Status: Waiting', 'ajax-snippets'); ?></span>
            <label class="output-format-label">
                <input type="checkbox" id="output_format_pre" checked>
                <code>&lt;pre&gt;</code>
            </label>
            <input id="cancel_ajax" style="display:none" class="button button-secondary" type="button" value="<?php echo esc_attr__('Cancel', 'ajax-snippets'); ?>">
            <input class="button button-primary" type="submit" value="<?php echo esc_attr__('Run', 'ajax-snippets'); ?>">
        </div>
        <?php
        require plugin_dir_path(__FILE__) . '../includes/snippet-templates.php';
        ?>
        <div class="snippet_select">
            <label for="snippet_templates"><?php echo esc_html__('Templates', 'ajax-snippets'); ?></label>
            <select id="snippet_templates">
                <option value=""></option>
                <?php foreach ($snippet_templates as $group => $templates) : ?>
                    <optgroup label="<?php echo esc_attr($group); ?>">
                        <?php foreach ($templates as $template) : ?>
                            <option value="<?php echo esc_attr($template['label']); ?>" data-code="<?php echo esc_attr($template['code']); ?>" data-vars="<?php echo esc_attr(wp_json_encode($template['vars'] ?? [])); ?>"><?php echo esc_html($template['label']); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <textarea id="snippet_content" name="snippet_content"><?php echo esc_textarea("<?php"); ?></textarea>
    </form>
    <hr>
    <div class="output-toolbar">
        <button type="button" id="copy_output" class="button button-small"><?php echo esc_html__('Copy', 'ajax-snippets'); ?></button>
    </div>
    <pre id="output"></pre>
    <div class="snippet_footer"><?php echo esc_html(sprintf(__('Ajax-Snippets Version: %s', 'ajax-snippets'), AJAX_SNIPPETS_VERSION)); ?></div>
</div>
