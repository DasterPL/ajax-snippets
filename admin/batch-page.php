<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <hr>
    <div class="snippet_batch">
        <div class="snippet_batch_controls">
            <button id="batch_fetch" class="button button-primary" type="button">Pobierz dane</button>
            <button id="batch_start" class="button button-secondary" type="button">Start batch</button>
            <button id="batch_stop" class="button button-secondary" type="button" style="display:none">Pauza</button>
            <button id="batch_resume" class="button button-secondary" type="button" style="display:none">Wznow</button>
            <label for="batch_size">Elementow / AJAX</label>
            <input id="batch_size" type="number" min="1" value="10" style="width:80px">
            <span class="status">Status: Waiting</span>
        </div>
        <?php
        $fetch_code = get_transient('ajax-snippet-batch-fetch_' . get_current_user_id());
        if ($fetch_code === false) {
            $fetch_code = "<?php\n// return array of items\nreturn [];";
        }
        $process_code = get_transient('ajax-snippet-batch-process_' . get_current_user_id());
        if ($process_code === false) {
            $process_code = "<?php\n// available: \$item, \$index, \$total, \$data\n// echo Ajax_Snippets_Table::render(\$item, 'Item');\n";
        }
        ?>
        <?php require plugin_dir_path(__FILE__) . 'snippet-templates.php'; ?>
        <div class="snippet_batch_select">
            <div class="snippet_select">
                <label for="batch_fetch_templates">Gotowe: pobierz dane</label>
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
                <label for="batch_process_templates">Gotowe: operacje batch</label>
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
                <label for="batch_fetch_code">1) Pobierz dane (musi zwrocic tablice)</label>
                <textarea id="batch_fetch_code"><?php echo esc_textarea($fetch_code); ?></textarea>
            </div>
            <div class="snippet_batch_editor">
                <label for="batch_process_code">2) Operacje batch (wiele elementow na AJAX)</label>
                <textarea id="batch_process_code"><?php echo esc_textarea($process_code); ?></textarea>
            </div>
        </div>
        <div class="snippet_batch_hint">
            Dostepne zmienne w batch:
            <code>$item</code> (aktualny element),
            <code>$index</code> (indeks od 0),
            <code>$total</code> (liczba elementow),
            <code>$data</code> (pelna tablica danych).
        </div>
        <div class="snippet_batch_progress">
            <progress id="batch_progress" max="0" value="0"></progress>
            <span id="batch_progress_label">0 / 0</span>
        </div>
        <div class="snippet_batch_outputs">
            <details open>
                <summary>Output: pobieranie</summary>
                <div id="batch_fetch_output"></div>
            </details>
            <details open>
                <summary>Output: batch</summary>
                <div id="batch_process_output"></div>
            </details>
        </div>
    </div>
    <div class="snippet_footer">Ajax-Snippers Version: <?= AJAX_SNIPPETS_VERSION ?></div>
</div>
