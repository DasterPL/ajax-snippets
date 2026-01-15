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
            <span class="status">Status: Waiting</span>
            <input id="cancel_ajax" style="display:none" class="button button-secondary" type="button" value="Anuluj">
            <input class="button button-primary" type="submit" value="Wykonaj">
        </div>
        <?php
        require plugin_dir_path(__FILE__) . 'snippet-templates.php';
        ?>
        <div class="snippet_select">
            <label for="snippet_templates">Gotowe snippety</label>
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
        <?php
        $last_snippet = get_transient('ajax-snippet_' . get_current_user_id());
        if ($last_snippet === false) {
            $last_snippet = "<?php";
        }
        ?>
        <textarea id="snippet_content" name="snippet_content"><?php echo esc_textarea($last_snippet); ?></textarea>
    </form>
    <hr>
    <div id="output"></div>
    <div class="snippet_footer">Ajax-Snippers Version: <?= AJAX_SNIPPETS_VERSION ?></div>
</div>
