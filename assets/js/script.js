'use strict';
jQuery(document).ready(function ($) {
    let return_result = false;
    let ajaxInProgress = false;
    let currentRequest = null;
    let batchStop = false;
    const storagePrefix = 'ajax_snippets_';
    const i18n = ajax_snippets_plugin_params.i18n || {};

    function $ajax(data) {
        return new Promise((resolve, reject) => {
            if (currentRequest) {
                currentRequest.abort();
            }
            currentRequest = $.ajax({
                url: ajax_snippets_plugin_params.ajax_url,
                type: 'POST',
                data,
                dataType: 'json',
                success: function (respoonse) {
                    resolve(respoonse);
                },
                error: function (error) {
                    reject(error);
                }
            });
        });
    }
    function changeStatus(text, color) {
        const label = i18n.statusLabel || 'Status:';
        $('.status').text(`${label} ${text}`);
        $('.status').css('color', color);
    }
    const getStoredValue = (key) => {
        try {
            return window.localStorage.getItem(storagePrefix + key);
        } catch (error) {
            return null;
        }
    };
    const setStoredValue = (key, value) => {
        try {
            window.localStorage.setItem(storagePrefix + key, value);
        } catch (error) {
            // Ignore storage failures.
        }
    };
    const debounce = (fn, delay) => {
        let timer = null;
        return (...args) => {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(() => fn(...args), delay);
        };
    };
    const get_loader = () => `<div style="display: grid; place-content: center;"><img src="${ajax_snippets_plugin_params.includes_url}/images/spinner-2x.gif" alt="Loading"/></div>`;
    const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const ensureSnippetDialog = () => {
        let dialog = document.getElementById('snippet_vars_dialog');
        if (dialog) {
            return dialog;
        }
        dialog = document.createElement('dialog');
        dialog.id = 'snippet_vars_dialog';
        dialog.className = 'snippet-dialog';
        dialog.innerHTML = `
            <form method="dialog" class="snippet-dialog-form">
                <h2 class="snippet-dialog-title"></h2>
                <div class="snippet-dialog-error" role="alert"></div>
                <div class="snippet-dialog-fields"></div>
                <label class="snippet-dialog-option">
                    <input type="checkbox" name="insert_mode" value="1">
                    ${i18n.insertAtCursor || 'Insert at cursor (do not replace all)'}
                </label>
                <menu class="snippet-dialog-actions">
                    <button type="button" class="snippet-dialog-button snippet-dialog-cancel">${i18n.cancel || 'Cancel'}</button>
                    <button type="submit" name="snippet_action" value="insert" class="snippet-dialog-button snippet-dialog-submit">${i18n.insert || 'Insert'}</button>
                    <button type="submit" name="snippet_action" value="execute" class="snippet-dialog-button snippet-dialog-button-primary snippet-dialog-submit">${i18n.insertAndRun || 'Insert and run'}</button>
                </menu>
            </form>
        `;
        document.body.appendChild(dialog);
        return dialog;
    };
    const openSnippetDialog = (vars, title) => {
        const dialog = ensureSnippetDialog();
        const form = dialog.querySelector('form');
        const titleEl = dialog.querySelector('.snippet-dialog-title');
        const fieldsEl = dialog.querySelector('.snippet-dialog-fields');
        const errorEl = dialog.querySelector('.snippet-dialog-error');
        const cancelBtn = dialog.querySelector('.snippet-dialog-cancel');
        const submitBtn = dialog.querySelector('.snippet-dialog-submit');
        titleEl.textContent = title || (i18n.snippetDialogTitle || 'Set snippet parameters');
        fieldsEl.innerHTML = '';
        errorEl.textContent = '';
        const selectFields = [];
        for (const variable of vars) {
            const name = variable.name || '';
            if (!name) {
                continue;
            }
            const label = variable.label || name;
            const defaultValue = variable.default || '';
            const type = variable.type || 'text';
            const field = document.createElement('div');
            field.className = 'snippet-dialog-field';
            const id = `snippet_var_${name}`;
            if (variable.source) {
                field.innerHTML = `
                    <label for="${id}">${label}</label>
                    <select id="${id}" name="${name}" data-source="${variable.source}" required></select>
                    <div class="snippet-dialog-dropdown-parent"></div>
                `;
                selectFields.push({ id, defaultValue, required: variable.required !== false });
            } else if (type === 'select') {
                field.innerHTML = `
                    <label for="${id}">${label}</label>
                    <select id="${id}" name="${name}" data-options="${encodeURIComponent(JSON.stringify(variable.options || []))}"></select>
                    <div class="snippet-dialog-dropdown-parent"></div>
                `;
                selectFields.push({ id, defaultValue, required: variable.required !== false });
            } else {
                const requiredAttr = variable.required === false ? '' : 'required';
                field.innerHTML = `
                    <label for="${id}">${label}</label>
                    <input id="${id}" name="${name}" type="${type}" value="${defaultValue}" ${requiredAttr}>
                `;
            }
            fieldsEl.appendChild(field);
        }
        form.reset();
        if (!dialog.open) {
            dialog.showModal();
        }
        for (const field of selectFields) {
            const select = document.getElementById(field.id);
            const dropdownParent = select.nextElementSibling;
            const source = select.getAttribute('data-source') || '';
            const optionsRaw = select.getAttribute('data-options');
            const $select = $(select);
            if (optionsRaw) {
                try {
                    const options = JSON.parse(decodeURIComponent(optionsRaw));
                    for (const option of options) {
                        const opt = document.createElement('option');
                        opt.value = option.value;
                        opt.textContent = option.label || option.value;
                        if (option.description) {
                            opt.setAttribute('data-description', option.description);
                        }
                        select.appendChild(opt);
                    }
                } catch (error) {
                    console.error(error);
                }
            }
            const select2Options = {
                width: '100%',
                dropdownParent: $(dropdownParent),
                placeholder: i18n.selectPlaceholder || 'Select...',
                allowClear: true,
                dropdownAutoWidth: true,
                templateResult: function (data) {
                    if (!data.id) {
                        return data.text;
                    }
                    const desc = data.element ? data.element.getAttribute('data-description') : '';
                    if (!desc) {
                        return data.text;
                    }
                    return $(
                        `<div>${data.text}<div class="snippet-dialog-option-desc">${desc}</div></div>`
                    );
                },
                templateSelection: function (data) {
                    return data.text || data.id;
                }
            };
            if (source) {
                select2Options.tags = true;
                select2Options.createTag = function (params) {
                    const term = (params.term || '').trim();
                    if (!term || !/^\d+$/.test(term)) {
                        return null;
                    }
                    return { id: term, text: term, newTag: true };
                };
                select2Options.ajax = {
                    url: ajax_snippets_plugin_params.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({
                        action: 'ajax_snippets_search',
                        nonce: ajax_snippets_plugin_params.nonce,
                        q: params.term || '',
                        source
                    }),
                    processResults: (data) => data || { results: [] }
                };
            }
            $select.select2(select2Options);
            $select.on('select2:open', function () {
                const dropdown = document.querySelector('.select2-container--open .select2-dropdown');
                if (dropdown) {
                    dropdown.style.position = 'absolute';
                    dropdown.style.left = '0';
                    dropdown.style.top = '0';
                }
            });
            if (field.defaultValue) {
                const hasOption = Array.from(select.options).some((option) => option.value === field.defaultValue);
                if (!hasOption) {
                    const opt = document.createElement('option');
                    opt.value = field.defaultValue;
                    opt.textContent = field.defaultValue;
                    select.appendChild(opt);
                }
                $select.val(field.defaultValue).trigger('change.select2');
            }
        }
        return new Promise((resolve) => {
            let resolved = false;
            const onSubmit = (event) => {
                event.preventDefault();
                errorEl.textContent = '';
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                const data = new FormData(form);
                const values = {};
                const missing = [];
                for (const variable of vars) {
                    const name = variable.name || '';
                    if (!name) {
                        continue;
                    }
                    const value = String(data.get(name) || '');
                    if (!value && variable.required !== false) {
                        missing.push(variable.label || name);
                    }
                    values[name] = value;
                }
                if (missing.length) {
                    const prefix = i18n.missingFields || 'Fill in:';
                    errorEl.textContent = `${prefix} ${missing.join(', ')}`;
                    return;
                }
                const insertMode = data.get('insert_mode') === '1';
                const action = event.submitter ? event.submitter.value : 'insert';
                resolved = true;
                dialog.close();
                resolve({ values, insertMode, action });
            };
            const onCancel = () => {
                dialog.close();
            };
            const onClose = () => {
                for (const field of selectFields) {
                    const select = document.getElementById(field.id);
                    if (select) {
                        $(select).select2('destroy');
                    }
                }
                if (!resolved) {
                    resolve(null);
                }
                form.removeEventListener('submit', onSubmit);
                cancelBtn.removeEventListener('click', onCancel);
                dialog.removeEventListener('close', onClose);
            };
            form.addEventListener('submit', onSubmit);
            cancelBtn.addEventListener('click', onCancel);
            dialog.addEventListener('close', onClose);
            submitBtn.focus();
        });
    };
    const applySnippetTemplate = async ($select, editor, executeCallback) => {
        const $option = $select.find('option:selected');
        const code = $option.attr('data-code');
        if (!code) {
            return;
        }
        let vars = [];
        const varsRaw = $option.attr('data-vars');
        if (varsRaw) {
            try {
                vars = JSON.parse(varsRaw);
            } catch (error) {
                console.error(error);
            }
        }
        const title = $option.text() || 'Snippet';
        const result = await openSnippetDialog(vars, title);
        if (!result) {
            return;
        }
        let snippet = code;
        for (const variable of vars) {
            const name = variable.name || '';
            if (!name) {
                continue;
            }
            const re = new RegExp(`{{\\s*${escapeRegExp(name)}\\s*}}`, 'g');
            snippet = snippet.replace(re, result.values[name]);
        }
        if (result.insertMode) {
            if (snippet.startsWith('<?php')) {
                snippet = snippet.replace(/^<\?php\s*\n?/, '');
            }
            editor.codemirror.replaceSelection(snippet);
        } else {
            editor.codemirror.setValue(snippet);
        }
        editor.codemirror.focus();
        editor.codemirror.save();
        $select.val(null).trigger('change.select2');
        if (result.action === 'execute' && typeof executeCallback === 'function') {
            requestAnimationFrame(() => {
                editor.codemirror.save();
                executeCallback();
            });
        }
    };

    const enableEditorTools = (cm) => {
        if (!cm) {
            return;
        }
        const extraKeys = cm.getOption('extraKeys') || {};
        cm.setOption('extraKeys', Object.assign({}, extraKeys, { 'Ctrl-Space': 'autocomplete' }));
        cm.setOption('lint', true);
        const gutters = cm.getOption('gutters') || [];
        if (gutters.indexOf('CodeMirror-lint-markers') === -1) {
            cm.setOption('gutters', gutters.concat(['CodeMirror-lint-markers']));
        }
        cm.on('inputRead', function (editorInstance, change) {
            if (change.text && change.text[0] && /[a-zA-Z_]/.test(change.text[0])) {
                editorInstance.showHint({ hint: CodeMirror.hint.php, completeSingle: false });
            }
        });
    };
    const initSingleEditor = () => {
        const $snippetContent = $('#snippet_content');
        if (!$snippetContent.length) {
            return;
        }
        const editor = wp.codeEditor.initialize($snippetContent, ajax_snippets_plugin_params.editor_settings);
        window.snippetEditor = editor;
        const storedSnippet = getStoredValue('snippet_content');
        if (storedSnippet !== null) {
            editor.codemirror.setValue(storedSnippet);
            editor.codemirror.save();
        }
        const saveSnippet = debounce(() => {
            editor.codemirror.save();
            setStoredValue('snippet_content', $snippetContent.val());
        }, 300);
        editor.codemirror.on('change', saveSnippet);
        const $snippetTemplates = $('#snippet_templates');
        if ($snippetTemplates.length && $.fn.select2) {
            $snippetTemplates.select2({
                width: '100%',
                placeholder: i18n.snippetPlaceholder || 'Choose snippet'
            });
            $snippetTemplates.on('change', function () {
                applySnippetTemplate($(this), editor, () => {
                    $('#snippet_form').trigger('submit');
                });
            });
        }
        if (window.CodeMirror && editor.codemirror && CodeMirror.showHint) {
            enableEditorTools(editor.codemirror);
        }
    };
    initSingleEditor();

    const initBatchEditors = () => {
        const $fetch = $('#batch_fetch_code');
        const $process = $('#batch_process_code');
        if (!$fetch.length || !$process.length) {
            return;
        }
        const fetchEditor = wp.codeEditor.initialize($fetch, ajax_snippets_plugin_params.editor_settings);
        const processEditor = wp.codeEditor.initialize($process, ajax_snippets_plugin_params.editor_settings);
        window.batchFetchEditor = fetchEditor;
        window.batchProcessEditor = processEditor;
        const storedFetch = getStoredValue('batch_fetch_code');
        if (storedFetch !== null) {
            fetchEditor.codemirror.setValue(storedFetch);
            fetchEditor.codemirror.save();
        }
        const storedProcess = getStoredValue('batch_process_code');
        if (storedProcess !== null) {
            processEditor.codemirror.setValue(storedProcess);
            processEditor.codemirror.save();
        }
        const saveFetch = debounce(() => {
            fetchEditor.codemirror.save();
            setStoredValue('batch_fetch_code', $fetch.val());
        }, 300);
        const saveProcess = debounce(() => {
            processEditor.codemirror.save();
            setStoredValue('batch_process_code', $process.val());
        }, 300);
        fetchEditor.codemirror.on('change', saveFetch);
        processEditor.codemirror.on('change', saveProcess);
        const $batchFetchTemplates = $('#batch_fetch_templates');
        const $batchProcessTemplates = $('#batch_process_templates');
        if ($.fn.select2) {
            $batchFetchTemplates.select2({
                width: '100%',
                placeholder: i18n.snippetPlaceholder || 'Choose snippet'
            });
            $batchProcessTemplates.select2({
                width: '100%',
                placeholder: i18n.snippetPlaceholder || 'Choose snippet'
            });
        }
        if (window.CodeMirror && fetchEditor.codemirror && processEditor.codemirror && CodeMirror.showHint) {
            enableEditorTools(fetchEditor.codemirror);
            enableEditorTools(processEditor.codemirror);
        }
        $batchFetchTemplates.on('change', function () {
            applySnippetTemplate($(this), fetchEditor, () => {
                $('#batch_fetch').trigger('click');
            });
        });
        $batchProcessTemplates.on('change', function () {
            applySnippetTemplate($(this), processEditor, () => {
                $('#batch_start').trigger('click');
            });
        });

        $('#batch_fetch').on('click', function () {
            ajaxInProgress = true;
            batchStop = false;
            changeStatus(i18n.statusFetching || 'Fetching', 'blue');
            $('#batch_fetch_output').html(get_loader());
            const fetch_code = fetchEditor.codemirror.getValue();
            $ajax({
                action: 'ajax_snippet_batch_init',
                nonce: ajax_snippets_plugin_params.nonce,
                fetch_code
            }).then((response) => {
                if (!response.success) {
                    throw response;
                }
                const { data } = response;
                $('#batch_fetch_output').html(data.message);
                $('#batch_progress').attr('max', data.count).val(0);
                $('#batch_progress_label').text(`0 / ${data.count}`);
                changeStatus(i18n.statusFetched || 'Fetched', 'green');
            }).catch((error) => {
                console.error(error);
                const errorMessage = error?.responseJSON?.data?.message || (i18n.unknownError || 'Unknown error');
                const $error = $('<pre />', { style: 'color:red;' }).text(errorMessage);
                $('#batch_fetch_output').empty().append($error);
                changeStatus(i18n.statusFail || 'Fail', 'red');
            }).finally(() => {
                ajaxInProgress = false;
            });
        });

        const setBatchControls = (options) => {
            const pauseVisible = options && options.pauseVisible;
            const resumeVisible = options && options.resumeVisible;
            $('#batch_stop').toggle(!!pauseVisible);
            $('#batch_resume').toggle(!!resumeVisible);
        };

        const fetchBatchStatus = () => $ajax({
            action: 'ajax_snippet_batch_status',
            nonce: ajax_snippets_plugin_params.nonce
        });

        const getBatchSize = () => {
            const value = parseInt($('#batch_size').val(), 10);
            return Number.isFinite(value) && value > 0 ? value : 10;
        };
        const storedBatchSize = getStoredValue('batch_size');
        if (storedBatchSize !== null && $('#batch_size').length) {
            $('#batch_size').val(storedBatchSize);
        }
        $('#batch_size').on('change input', function () {
            setStoredValue('batch_size', $(this).val());
        });

        const runBatch = (index = 0) => {
            if (batchStop) {
                changeStatus(i18n.statusPaused || 'Paused', 'orange');
                return;
            }
            ajaxInProgress = true;
            changeStatus(i18n.statusProcessing || 'Processing', 'blue');
            const process_code = processEditor.codemirror.getValue();
            $ajax({
                action: 'ajax_snippet_batch_next',
                nonce: ajax_snippets_plugin_params.nonce,
                process_code,
                index,
                batch_size: getBatchSize()
            }).then((response) => {
                if (!response.success) {
                    throw response;
                }
                const { data } = response;
                if (data.message) {
                    $('#batch_process_output').append(data.message);
                }
                $('#batch_progress').attr('max', data.total).val(data.index);
                $('#batch_progress_label').text(`${data.index} / ${data.total}`);
                if (data.done) {
                    changeStatus(i18n.statusDone || 'Done', 'green');
                    setBatchControls({ pauseVisible: false, resumeVisible: false });
                    return;
                }
                runBatch(data.index);
            }).catch((error) => {
                if (batchStop && error && error.statusText === 'abort') {
                    return;
                }
                console.error(error);
                const errorMessage = error?.responseJSON?.data?.message || (i18n.unknownError || 'Unknown error');
                const $error = $('<pre />', { style: 'color:red;' }).text(errorMessage);
                $('#batch_process_output').empty().append($error);
                changeStatus(i18n.statusFail || 'Fail', 'red');
                setBatchControls({ pauseVisible: false, resumeVisible: false });
            }).finally(() => {
                ajaxInProgress = false;
            });
        };

        $('#batch_start').on('click', function () {
            batchStop = false;
            $('#batch_process_output').empty();
            setBatchControls({ pauseVisible: true, resumeVisible: false });
            runBatch(0);
        });

        $('#batch_stop').on('click', function () {
            batchStop = true;
            if (currentRequest) {
                currentRequest.abort();
            }
            setBatchControls({ pauseVisible: false, resumeVisible: true });
            changeStatus(i18n.statusPaused || 'Paused', 'orange');
        });

        $('#batch_resume').on('click', function () {
            batchStop = false;
            changeStatus(i18n.statusProcessing || 'Processing', 'blue');
            setBatchControls({ pauseVisible: true, resumeVisible: false });
            fetchBatchStatus().then((response) => {
                if (!response.success || !response.data || !response.data.exists) {
                    throw response;
                }
                const { data } = response;
                $('#batch_progress').attr('max', data.total).val(data.index);
                $('#batch_progress_label').text(`${data.index} / ${data.total}`);
                runBatch(data.index);
            }).catch((error) => {
                console.error(error);
                const errorMessage = error?.responseJSON?.data?.message || (i18n.resumeMissing || 'No batch data to resume.');
                const $error = $('<pre />', { style: 'color:red;' }).text(errorMessage);
                $('#batch_process_output').empty().append($error);
                changeStatus(i18n.statusFail || 'Fail', 'red');
                setBatchControls({ pauseVisible: false, resumeVisible: false });
            });
        });

        fetchBatchStatus().then((response) => {
            if (!response.success || !response.data || !response.data.exists) {
                return;
            }
            const { data } = response;
            if (data.index < data.total) {
                $('#batch_progress').attr('max', data.total).val(data.index);
                $('#batch_progress_label').text(`${data.index} / ${data.total}`);
                changeStatus(i18n.statusPaused || 'Paused', 'orange');
                setBatchControls({ pauseVisible: false, resumeVisible: true });
            }
        }).catch(() => {
            // Ignore status errors on load.
        });
    };
    initBatchEditors();
    // editor.on("inputRead", function (editor, event) {
    //     if (event.text[0].match(/[a-zA-Z_]/)) {
    //         editor.showHint({ hint: CodeMirror.hint.php });
    //     }
    // });

    $(window).on('beforeunload', function () {
        if (ajaxInProgress) {
            return 'An AJAX request is in progress. Are you sure you want to leave?';
        }
    });

    $(document).on('click', '.ajax-snippets-path', function (event) {
        const path = $(this).data('path');
        if (!path) {
            return;
        }
        event.preventDefault();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(path);
        } else {
            const $temp = $('<textarea />').val(path).appendTo('body').select();
            document.execCommand('copy');
            $temp.remove();
        }
    });

        $('#snippet_form').submit(function (event) {
            event.preventDefault();
            ajaxInProgress = true;
            changeStatus(i18n.statusProcessing || 'Processing', 'blue');
            $('#cancel_ajax').show();
            $('#output').html(get_loader());
            const snippet_content = $('#snippet_content').val();
        $ajax({
            action: 'ajax_snippet_submit',
            nonce: ajax_snippets_plugin_params.nonce,
            snippet_content,
            return_result
        }).then((response) => {
            if (response.success) {
                const { data } = response;
                if (data.return) {
                    return_result = data.return;
                }
                $('#output').html(data.message);
                changeStatus(i18n.statusDone || 'Done', 'green');
            } else {
                throw (response);
            }
        }).catch((error) => {
            console.error(error);
            const errorMessage = error?.responseJSON?.data?.message || (i18n.unknownError || 'Unknown error');
            const $error = $('<pre />', { style: 'color:red;' }).text(errorMessage);
            $('#output').empty().append($error);
            changeStatus(i18n.statusFail || 'Fail', 'red');
        }).finally(() => {
            ajaxInProgress = false;
            $('#cancel_ajax').hide();
        });
    });
    $('#cancel_ajax').click(function (event) {
        event.preventDefault();
        if (currentRequest) {
            currentRequest.abort();
        }
        $('#cancel_ajax').hide();
    });
});
