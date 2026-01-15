<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <hr>
    <form id="snippet_form">
        <div class="snippet_nav">
            <div class="snippet_progress">
                <progress id="progress" max="0" value="0"></progress>
            </div>
            <span class="status"><?php echo esc_html__('Status: Waiting', 'ajax-snippets'); ?></span>
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
    <div id="output"></div>
    <div class="snippet_footer"><?php echo esc_html(sprintf(__('Ajax-Snippets Version: %s', 'ajax-snippets'), AJAX_SNIPPETS_VERSION)); ?></div>
</div>
