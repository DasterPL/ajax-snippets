<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <hr>
    <div class="snippet_batch">
        <div class="snippet_batch_controls">
            <button id="batch_fetch" class="button button-primary" type="button"><?php echo esc_html__('Fetch data', 'ajax-snippets'); ?></button>
            <button id="batch_start" class="button button-secondary" type="button"><?php echo esc_html__('Start batch', 'ajax-snippets'); ?></button>
            <button id="batch_stop" class="button button-secondary" type="button" style="display:none"><?php echo esc_html__('Pause', 'ajax-snippets'); ?></button>
            <button id="batch_resume" class="button button-secondary" type="button" style="display:none"><?php echo esc_html__('Resume', 'ajax-snippets'); ?></button>
            <label for="batch_size"><?php echo esc_html__('Items / AJAX', 'ajax-snippets'); ?></label>
            <input id="batch_size" type="number" min="1" value="10" style="width:80px">
            <span class="status"><?php echo esc_html__('Status: Waiting', 'ajax-snippets'); ?></span>
        </div>
        <?php
        $fetch_code = "<?php\n// return array of items\nreturn [];";
        $process_code = "<?php\n// available: \$item, \$index, \$total, \$data, \$prev\n// \$prev is the previous iteration's return value\n// echo Ajax_Snippets_Table::render(\$item, 'Item');\n";
        ?>
        <?php require plugin_dir_path(__FILE__) . '../includes/snippet-templates.php'; ?>
        <div class="snippet_batch_select">
            <div class="snippet_select">
                <label for="batch_fetch_templates"><?php echo esc_html__('Templates: fetch data', 'ajax-snippets'); ?></label>
                <select id="batch_fetch_templates">
                    <option value=""></option>
                    <?php foreach ($batch_fetch_templates as $group => $templates) : ?>
                        <optgroup label="<?php echo esc_attr($group); ?>">
                            <?php foreach ($templates as $template) : ?>
                                <option value="<?php echo esc_attr($template['label']); ?>" data-code="<?php echo esc_attr($template['code']); ?>" data-vars="<?php echo esc_attr(wp_json_encode($template['vars'] ?? [])); ?>"><?php echo esc_html($template['label']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="snippet_select">
                <label for="batch_process_templates"><?php echo esc_html__('Templates: batch operations', 'ajax-snippets'); ?></label>
                <select id="batch_process_templates">
                    <option value=""></option>
                    <?php foreach ($batch_process_templates as $group => $templates) : ?>
                        <optgroup label="<?php echo esc_attr($group); ?>">
                            <?php foreach ($templates as $template) : ?>
                                <option value="<?php echo esc_attr($template['label']); ?>" data-code="<?php echo esc_attr($template['code']); ?>" data-vars="<?php echo esc_attr(wp_json_encode($template['vars'] ?? [])); ?>"><?php echo esc_html($template['label']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="snippet_batch_editors">
            <div class="snippet_batch_editor">
                <label for="batch_fetch_code"><?php echo esc_html__('1) Fetch data (must return an array)', 'ajax-snippets'); ?></label>
                <textarea id="batch_fetch_code"><?php echo esc_textarea($fetch_code); ?></textarea>
            </div>
            <div class="snippet_batch_editor">
                <label for="batch_process_code"><?php echo esc_html__('2) Batch operations (multiple items per AJAX)', 'ajax-snippets'); ?></label>
                <textarea id="batch_process_code"><?php echo esc_textarea($process_code); ?></textarea>
            </div>
        </div>
        <div class="snippet_batch_hint">
            <?php echo esc_html__('Available batch variables:', 'ajax-snippets'); ?>
            <code>$item</code> (<?php echo esc_html__('current item', 'ajax-snippets'); ?>),
            <code>$index</code> (<?php echo esc_html__('index from 0', 'ajax-snippets'); ?>),
            <code>$total</code> (<?php echo esc_html__('total items', 'ajax-snippets'); ?>),
            <code>$data</code> (<?php echo esc_html__('full data array', 'ajax-snippets'); ?>),
            <code>$prev</code> (<?php echo esc_html__('previous iteration return value', 'ajax-snippets'); ?>).
        </div>
        <div class="snippet_batch_progress">
            <progress id="batch_progress" max="0" value="0"></progress>
            <span id="batch_progress_label">0 / 0</span>
        </div>
        <div class="snippet_batch_outputs">
            <details open>
                <summary><?php echo esc_html__('Output: fetch', 'ajax-snippets'); ?></summary>
                <div id="batch_fetch_output"></div>
            </details>
            <details open>
                <summary><?php echo esc_html__('Output: batch', 'ajax-snippets'); ?></summary>
                <div id="batch_process_output"></div>
            </details>
        </div>
    </div>
    <div class="snippet_footer"><?php echo esc_html(sprintf(__('Ajax-Snippets Version: %s', 'ajax-snippets'), AJAX_SNIPPETS_VERSION)); ?></div>
</div>
