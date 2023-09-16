jQuery(function () {
    var editor = jQuery('#digits-theme-editor');
    var iframe = editor.find('#digits-editor-preview');
    var resizing_tooltip = editor.find('.digits-theme-editor_resizing-tooltip');
    var editor_cursor = editor.find('#digits-editor_cursor');
    var selector_list = jQuery('#digits-editor-selector_list');
    var preview_frame = document.getElementById("digits-editor-preview");
    var controls = jQuery('#digits-editor-selector_controls');
    var trigger_list = jQuery('#digits-editor-trigger_list');
    var trigger_btn = jQuery('#digits-editor-show_trigger_list');

    var editor_css = jQuery('#editor_css');
    var editor_js = jQuery('#editor_js');

    var editor_data_inp = jQuery('#editor_data');
    var editor_data;

    try {
        editor_data = JSON.parse(editor_data_inp.val());

        if (editor_data.css_script) {
            editor_css.val(editor_data.css_script);
        }
        if (editor_data.js_script) {
            editor_js.val(editor_data.js_script);
        }
    } catch (e) {
        editor_data = {};
    }

    jQuery('.digits-code_editor').on('click', function (e) {
        e.preventDefault();
        if (!editor.hasClass('code_editor_view')) {
            editor.find('textarea.selected').focus();
        }
        editor.toggleClass('code_editor_view');
    })

    editor_cursor.on('click', function (e) {
        editor_cursor.addClass('digits-show-cursor-expand');
    })
    jQuery('.digits_editor_cursor_type_item').on('click', function (e) {
        e.preventDefault();
        var ic = jQuery(this).find('.digits_editor_ic');
        var ic_class = '';
        if (ic.data('type') === 'cursor') {
            stop_selection();
        } else {
            start_selection();
        }

        ic_class = ic.attr('class');
        editor_cursor.find('#digits_editor_selected_ic').attr('class', ic_class);
        editor_cursor.removeClass('digits-show-cursor-expand');

        return false;
    })

    jQuery('.digits-responsive').on('click', function (e) {
        e.preventDefault();
        var width = 100;
        if (editor.hasClass('responsive-mode')) {
            editor.removeClass('responsive-mode');
        } else {
            width = 50;
            editor.addClass('responsive-mode');
        }
        iframe.stop().animate({'width': width + '%'}, function () {
            resizing_tooltip.find('span').text(Math.round(iframe.width()) + 'px');
        });

    })

    var resizers = document.querySelectorAll(".digits-theme-editor_resize");
    var windowWidth;

    function initResize(e) {
        e.preventDefault();
        windowWidth = jQuery(window).width() / 2;
        editor.addClass('resizing');
        window.addEventListener("mousemove", startIframeResize, true);
        window.addEventListener("mouseup", stopIframeResize, true);
    }

    function startIframeResize(e) {
        e.preventDefault();
        var width = Math.round(100 * e.clientX / windowWidth);
        width -= 100;
        width = Math.abs(width);
        iframe.css("width", width + '%');
        resizing_tooltip.find('span').text(Math.round(iframe.width()) + 'px');
    }

    function stopIframeResize() {
        editor.removeClass('resizing');
        window.removeEventListener("mousemove", startIframeResize, true);
    }

    resizers.forEach(function (resizer) {
        resizer.addEventListener("mousedown", initResize, true);
    });

    function send_data(value) {
        var message = create_message('digits_editor_mode', value)
        preview_frame.contentWindow.postMessage(message, "*");
    }

    function stop_selection() {
        send_data({'mode': 'cursor'});
    }

    function start_selection() {
        send_data({'mode': 'selector'});
    }

    window.addEventListener('message', function (event) {
        if (event && event.data) {
            var data = event.data;
            if (data.key && data.key === 'digits_editor_frame') {
                process_message(data);
            }
        }
    })

    var selectedElem = false;

    function process_message(data) {
        if (data.value === 'editor_select') {
            selectedElem = false;
            selector_list.empty();
            var selectors = data.selector;
            var selectors_length = selectors.length;
            if (selectors_length > 0) {
                selectors.reverse();
                selectedElem = selectors.join(' ');
                selectedElem = selectedElem.replace(".digits-login-modal","");
                selectors.forEach(function (sel, index) {
                    var html = '<div class="digits-editor-elem-sel">' +
                        '<div class="digits-editor-elem-sel_ic digits-editor-selector_ic"></div>' +
                        '<div class="digits-editor-elem-sel_text">' + sel + '</div>' +
                        '</div>';
                    selector_list.append(html);
                    if (selectors_length > index + 1) {
                        selector_list.append('<div class="digits-editor-elem-sel_arrow"></div>');
                    }
                })

                controls.removeClass('selected');
                controls.find('input:checked').prop('checked', false).trigger('change');

                var show_trigger = true;

                native_forms.show();
                builder_forms.hide();
                if (editor_data.triggerForm) {
                    if (editor_data.triggerForm.hasOwnProperty(selectedElem)) {
                        var selectedTrigger = editor_data.triggerForm[selectedElem];
                        var inp = controls.find('[value="' + selectedTrigger + '"]');
                        if (inp.length > 0) {
                            controls.addClass('selected');
                            inp.prop('checked', true).trigger('change');
                            if (builder_forms.find('[value="' + selectedTrigger + '"]').length > 0) {
                                builder_forms.show();
                                native_forms.hide();
                            }
                            show_trigger = false;
                        }
                    }
                }
                if (show_trigger) {
                    trigger_list.hide();
                    trigger_btn.show();
                } else {
                    trigger_btn.hide();
                    trigger_list.show();
                }
                controls.addClass('show');
            } else {
                controls.removeClass('show');
            }
        }
    }


    function iframeURLChange(iframe, callback) {
        var currentUrl = null;
        var dispatchChange = function () {
            var newHref = iframe.contentWindow.location.href;
            if (currentUrl == null) {
                currentUrl = newHref;
            } else if (newHref !== currentUrl) {
                callback(newHref);
            }
        };

        var unloadHandler = function () {
            setTimeout(dispatchChange, 0);
        };

        function attachUnload() {
            iframe.contentWindow.addEventListener("unload", unloadHandler);
        }

        iframe.addEventListener("load", function () {
            dispatchChange();
        });
        attachUnload();
    }


    iframeURLChange(preview_frame, function (href) {
        var url = addParamToUrl('url', href);
        window.location.href = url;
    });

    function create_message(key, body) {
        return {
            key: key, body: body
        };
    }

    function addParamToUrl(key, value) {
        var urlObj = new URL(window.location.href);
        var params = new URLSearchParams();

        if (params.has(key)) {
            params.set(key, value);
        } else {
            params.append(key, value);
        }

        urlObj.search = params.toString();

        return urlObj.toString();
    }

    var builder_forms = jQuery('#digits-editor_builder_forms');
    var native_forms = jQuery('#digits-editor_native_forms');

    jQuery('.digits_form_trigger').on('change', function (e) {

        var $this = jQuery(this);
        var row = $this.closest('.digits-editor_form_selector');

        if (!$this.is(":checked")) {
            row.removeClass('checked');
            return false;
        }
        controls.find('.checked').removeClass('checked');

        if (!selectedElem) {
            return false;
        }

        var value = $this.val();
        if (value === 'builder') {
            native_forms.hide();
            builder_forms.show();
        } else {
            if (!editor_data.triggerForm) {
                editor_data.triggerForm = {};
            }
            editor_data.triggerForm[selectedElem] = value;
            controls.addClass('selected')

        }
        row.addClass('checked');
    });

    jQuery('.digits-editor_back_form_list').on('click', function (e) {
        native_forms.show();
        builder_forms.hide();
    });

    jQuery('.digits-editor_form_label').on('click', function (e) {
        var inp = jQuery(this).find('input').first();
        if (inp.val() === 'builder') {
            native_forms.hide();
            builder_forms.show();
        }
    })


    jQuery('#digits-editor_hide_elem').on('click', function (e) {
        if (!selectedElem) {
            return false;
        }

        var type = 'hide';
        send_data({'visibility': type, 'elem': selectedElem});

        if (!editor_data.hideElements) {
            editor_data.hideElements = [];
        }

        editor_data.hideElements.push(selectedElem);

        return false;
    })

    var editor_tabs = jQuery('#digits-editor-ec_tabs');
    var editor_code = jQuery('#digits-editor-code');
    jQuery('.digits-editor-ec_tab').on('click', function (e) {
        editor_tabs.find('.selected').removeClass('selected');
        jQuery(this).addClass('selected');
        var type = jQuery(this).data('type');
        editor_code.find('.selected').removeClass('selected').hide();
        editor_code.find('.' + type).addClass('selected').show().focus();
    })

    jQuery('.digits-editor-code').on('change', function (e) {
        var code = jQuery(this).attr('name');
        var script = jQuery(this).val();
        send_data({'script_type': code, 'script': script});
    })

    jQuery('#digits-remove_trigger').on('click', function (e) {
        controls.removeClass('selected');
        if (editor_data.triggerForm && selectedElem) {
            delete editor_data.triggerForm[selectedElem];
        }
        controls.find('input:checked').prop('checked', false).trigger('change');
        trigger_list.hide();
        trigger_btn.show();
        return false;
    })
    trigger_btn.on('click', function (e) {
        trigger_list.show();
        trigger_btn.hide();
        return false;
    })

    var loader = jQuery(".dig_load_overlay").first();
    jQuery('#digits-editor_save').on('click', function (e) {
        editor_data.css_script = editor_css.val();
        editor_data.js_script = editor_js.val();

        var form = jQuery(this).closest('form');

        loader.show();
        editor_data_inp.val(JSON.stringify(editor_data));

        var data = form.serializeArray();


        jQuery.ajax({
            type: 'post',
            url: form.attr('action'),
            data: data,
            success: function (res) {
                if (res.success) {

                } else {
                    showDigErrorMessage(res.data.message);
                }
                loader.hide();
            },
            error: function (error) {
                loader.hide();
                showDigErrorMessage('Error');
            }
        });

        return false;
    })

    jQuery('#digits-editor_close').on('click',function (e) {
        window.location.href = jQuery(this).data('link');
        loader.show();
        return false;
    })
});
