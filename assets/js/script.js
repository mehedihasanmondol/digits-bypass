jQuery(function ($) {

    var loader = jQuery(".dig_load_overlay").first();

    var auto_login_processed = false;
    var view_change_counter = 0;

    function toggleFocusClass(row, isActive) {
        var activeClass = 'digits-row_active';
        if (isActive) {
            row.addClass(activeClass);
        } else {
            row.removeClass(activeClass);
        }
    }

    jQuery(document).on('focusin', '.dig-mobile_field,.digits_countrycode', function (e) {
        var par = jQuery(this).closest('.digits-form_input_row');
        toggleFocusClass(par, true);
    });

    jQuery(document).on('focusout', '.dig-mobile_field,.digits_countrycode', function (e) {
        var par = jQuery(this).closest('.digits-form_input_row');
        toggleFocusClass(par, false);
    });

    jQuery(document).on('keyup change focusin', '.dig-mobile_field', function (e) {
        var $this = jQuery(this);
        var par = jQuery(this).closest('.digits-form_input_row');

        if (!$this.data('padding-left'))
            $this.data('padding-left', $this.css('padding-right'));


        if (show_countrycode_field($this)) {
            par.find(".digits_countrycodecontainer").css({"display": "inline-block"}).find('.digits_countrycode').trigger('keyup');
        } else {
            var leftPadding = $this.data('padding-left');
            par.find(".digits_countrycodecontainer").hide();
            $this.css({"padding-left": leftPadding});
        }
        if (!$this.attr('placeholder')) {
            setTimeout(function () {
                $this.attr('placeholder', $this.data('placeholder'));
            }, 400);
        }
    }).trigger('change');

    jQuery(document).on('keyup change focusin', '.digits_countrycode', function (e) {
        var $this = jQuery(this);
        var size = $this.val().length + 1;
        if (size < 2) size = 2;
        $this.attr('size', size);
        var code = $this.val();
        if (code.trim().length == 0) {
            $this.val("+");
        }
        var par = $this.closest('.digits-form_input_row');

        par.find('.dig-mobile_field').stop().animate({"padding-left": $this.outerWidth() + "px"}, 'fast');

    });


    jQuery(document).on('click', '.digits_skip_now', function (e) {
        var $this = jQuery(this);
        var form = $this.closest('form');
        trigger_form_submit(form);
        return false;
    })

    //digits_login_step
    var isFormLoading = false;
    jQuery(document).on('click', '.digits-form_submit', function (e) {
        e.preventDefault();
        if (isFormLoading) {
            return false;
        }

        var $this = jQuery(this);
        var form = $this.closest('form');
        var validate = validate_form(form);
        isFormLoading = true;
        if (!validate) {
            isFormLoading = false;
            return false;
        }
        digits_form_submit(form);
        return false;
    });

    function update_form_title(form, section) {
        var heading_section = form.find('.digits-form_heading .digits-form_heading_text');
        var heading_text = heading_section.data('text');
        if (section) {
            var update_title_inp = section.find('.main-section-title');
            if (update_title_inp.length) {
                heading_text = update_title_inp.last().html();
            }
        }
        heading_section.html(heading_text);
    }

    function digits_recaptcha_error(res) {
        hideLoader();
    }

    var digits_form = null;

    function digits_recaptcha_callback(token) {
        digits_form.find('.invi-recaptcha').last().attr('data-solved', 1);
        digits_form_submit(digits_form)
    }

    function digits_form_submit(form) {
        digits_form = form;

        var wrapper = form.find('.digits-form_tab_wrapper');
        var container = wrapper.find('.digits-form_tab_container:visible');

        var new_pass = form.find('.new_password');
        if (dig_script.strong_pass == 1 && new_pass.length) {
            var new_pass_val = new_pass.val();
            if (new_pass_val.length > 0) {
                try {
                    var strength = wp.passwordStrength.meter(new_pass_val, ['black', 'listed', 'word'], new_pass_val);
                    if (strength != null && strength < 3) {
                        showDigNoticeMessage(dig_script.useStrongPasswordString);
                        isFormLoading = false;
                        return false;
                    }
                } catch (e) {

                }
            }
        }

        showLoader();

        var recaptcha = form.find('.invi-recaptcha').last();
        if (recaptcha.length > 0 && !recaptcha.data('solved')) {
            var captcha_type = recaptcha.data('ctype');
            if (captcha_type === 'v3') {
                grecaptcha.ready(function() {

                    grecaptcha.execute(recaptcha.data('sitekey')).then(function (token) {
                        if(!token){
                            hideLoader();
                            showDigErrorMessage('Error, verifying captcha. Please contact admin for more info!');
                            return;
                        }
                        var grecaptcha_resp = digits_form.find('input[name="g-recaptcha-response"]');
                        if(!grecaptcha_resp.length) {
                            digits_form.append('<input name="g-recaptcha-response" type="hidden" />');
                        }
                        digits_form.find('input[name="g-recaptcha-response"]').val(token)

                        digits_recaptcha_callback(token);
                    }).catch(function (error) {
                        console.log(error);
                    });
                });

            } else {
                var widget_id = grecaptcha.render(recaptcha.attr('id'),
                    {
                        'callback': digits_recaptcha_callback,
                        'error-callback': digits_recaptcha_error,
                    });
                grecaptcha.execute(widget_id);
            }
            return false;
        }

        var form_data = form.serializeArray();

        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                var data = res.data;
                isFormLoading = false;
                if (res.success) {

                    if (data.html) {
                        var html = jQuery(data.html);
                        wrapper.append(html);

                        var tab_item = html.find('.digits-form_tab-item');

                        tab_item.first().trigger('click');
                        container.hide();

                        update_form_title(form, wrapper);


                        form.removeClass('digits_form_index_section');
                        form.find('.digits_form_back').removeClass('digits_hide_back');

                        if (data.input_info_html) {
                            html.find('.digits-form_input_info').append(data.input_info_html);
                        }

                        if (data.firebase) {
                            process_firebase(form);
                        } else {
                            if (!tab_item.first().find('.dig_process_data').length) {
                                hideLoader();
                            }
                        }

                        var country_code = html.find('.country_code_flag');
                        if (country_code.length) {
                            country_code.trigger('update_flag');
                        }
                        update_fields(html);
                    } else if (data.process) {
                        process_data(form, data);
                    } else if (data.verify_firebase) {
                        verify_firebase(form);
                    }
                } else {

                    if (data.reload) {
                        location.reload();
                    }

                    if (data.notice) {
                        showDigNoticeMessage(data.message);
                    } else {
                        showDigErrorMessage(data.message);
                    }
                    hideLoader();
                }
            },
            error: function (res) {
                showDigErrorMessage(dig_script.ErrorPleasetryagainlater);
                isFormLoading = false;
                hideLoader();
            }
        });
    };

    window.digitsSecureFormSubmit = digits_form_submit;

    function process_data(form, data) {
        if (!data.process) {
            return false;
        }
        var delay = 0;
        showLoader();
        if (data.process_type === 'login') {
            if (data.login_reg_success_msg == 1) {
                delay = 500;
                showDigSuccessMessage(data.message);
            }
        }

        if (data.show_message) {
            showDigSuccessMessage(data.message);
        }

        if (data.delay) {
            delay = data.delay;
        }
        var redirect = data.redirect;
        digits_redirect_to(form, redirect, delay);
    }

    jQuery(document).on('click', '.digits_start_device_auth', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var tab = $this.closest('.digits-form_tab_body');
        var form = tab.closest('form');
        tab.find('.platform_value').val('');
        authenticate_platform(form.serializeArray(), tab);
        return false;
    });

    jQuery(document).on('change', '.digits_otp_input-field', function (e) {
        var $this = jQuery(this);

        var type = $this.attr('name');
        var form = $this.closest('form');
        var tab_body = $this.closest('.digits-form_tab_body');
        var change_elem_name = tab_body.data('change');
        if (change_elem_name && change_elem_name.length) {
            var change_elem = form.find('[name="' + change_elem_name + '"]');

            if (change_elem && change_elem.length) {
                change_elem.val(type);
            }
        }
    })

    jQuery(document).on('click', '.digits-form_otp_selector,.digits-form_resend_otp', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var type = $this.data('type');
        var form = $this.closest('form');

        showLoader();

        var form_data = form.serializeArray();

        var tab_body_elem = $this.closest('.digits-form_tab_body');
        if ($this.hasClass('digits-form_resend_otp')) {
            tab_body_elem = form.find('[data-id="' + $this.data('id') + '"]').first();
            form_data.push({name: "otp_resend", value: true});
        }

        var tab_body = tab_body_elem.closest('.digits-form_tab_body');

        var change_elem_name = tab_body.data('change');
        if (change_elem_name && change_elem_name.length) {
            var change_elem = form.find('[name="' + change_elem_name + '"]');

            if (change_elem && change_elem.length) {
                change_elem.val(type);
            }
        }

        var container_id = false;

        var getParent = form.closest('.digits_ui');
        if (getParent.length) {
            container_id = getParent.attr('id');
        }
        if (!container_id) {
            container_id = form.attr('id');
        }
        if (!container_id) {
            var custom_id = form.find('.digits_container_id');
            if (custom_id.length) {
                container_id = custom_id.first().val();
            }
        }
        form_data.push({name: "container", value: container_id});
        form_data.push({name: "sub_action", value: type});

        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                var data = res.data;
                if (res.success) {
                    if (data.html && !$this.data('disable_update')) {
                        var html = jQuery(data.html);
                        tab_body.html(html);
                        tab_body.find('input[type="text"]').focus();
                        form.find('.digits-form_submit-btn').show();

                    }

                    if (data.input_info_html) {
                        tab_body.find('.digits-form_input_info').append(data.input_info_html);
                    }
                    update_form_footer(tab_body);


                    if (data.auto_fill) {
                        digits_wait_for_sms(form, tab_body);
                    }

                    if (data.resend_timer) {
                        digits_resend_timer(form, tab_body, data, type);
                    }

                    if (data.check_remote_status) {
                        start_auto_check();
                    }
                    if (data.otp_token_key) {
                        tab_body.find('.otp_token_key').val(data.otp_token_key);
                    }

                    if (data.firebase) {
                        process_firebase(form);
                    } else {
                        hideLoader();
                    }
                } else {
                    hideLoader();
                    if (data.notice) {
                        showDigNoticeMessage(data.message);
                    } else {
                        showDigErrorMessage(data.message);
                    }
                }
            },
            error: function (res) {
                showDigErrorMessage(dig_script.ErrorPleasetryagainlater);
                hideLoader();
            }
        });
        return false;
    })

    var resetreCaptchaWidget = false;

    function process_firebase(form) {
        var country_code_field = form.find('.digits_countrycode').last();
        if (!country_code_field || !country_code_field.length) {
            country_code_field = form.find('.countrycode').last();
        }

        var country_code_field_val = country_code_field.val();

        var mobile_field = form.find('.mobile_field').last();

        if (!mobile_field || !mobile_field.length) {
            mobile_field = form.find('[name="mobile/email"]');
        }

        var mobile_field_val = mobile_field.val();


        if (country_code_field_val.length === 0 || mobile_field_val.length === 0) {
            showDigErrorMessage(dig_script.InvalidMobileNumber);
            return;
        }

        var phoneNumber = country_code_field_val + mobile_field_val;

        if (resetreCaptchaWidget) {
            grecaptcha.reset(window.recaptchaWidgetId);
        }

        var appVerifier = window.recaptchaVerifier;
        firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier)
            .then(function (confirmationResult) {
                resetreCaptchaWidget = true;
                hideLoader();
                window.confirmationResult = confirmationResult;
            }).catch(function (error) {
            if (error.message === 'TOO_LONG' || error.message === 'TOO_SHORT') {
                showDigErrorMessage(dig_script.InvalidMobileNumber);
            } else {
                showDigErrorMessage(error.message);
            }
            hideLoader();
        });
    }

    function verify_firebase(form) {
        var otp_field = form.find('.otp_input:visible').last();
        var otp = otp_field.val();
        window.confirmationResult.confirm(otp)
            .then(function (result) {
                firebase.auth().currentUser.getIdToken(true).then(function (idToken) {
                    window.verifyingCode = false;
                    window.confirmationResult = null;
                    var container = form.find('.digits-tab_active');
                    container.find(".dig_ftok_fbase").remove();
                    container.append('<input type="hidden" name="firebase_token" value="' + idToken + '" class="dig_ftok_fbase" />');
                    trigger_form_submit(form);
                }).catch(function (error) {
                    loader.hide();
                    showDigErrorMessage(error);
                });

            }).catch(function (error) {
            loader.hide();
            showDigErrorMessage(dig_script.InvalidOTP);
        });
    }


    if (dig_script.dig_dsb == 1) return;
    var is_waiting = false

    function digits_wait_for_sms(form, tab) {
        if ('OTPCredential' in window) {
            if (is_waiting) {
                return;
            }
            is_waiting = true;
            navigator.credentials.get({otp: {transport: ['sms']}})
                .then(function (otp) {
                    var code = otp.code;
                    tab.find('.otp_input:visible').val(code);
                    trigger_form_submit(form);
                })
                .catch(function (error) {
                    console.log(error);
                });
        }
    }

    function digits_resend_timer(form, container, data, type) {
        if (!data.resend_timer) {
            return false;
        }
        var resend_id = container.find('.digits-form_resend_otp').data('id');
        var resendTime = data.resend_timer;
        var resend_elem = form.find('[data-id="' + resend_id + '"]');
        resend_elem.addClass('digits_resend_disabled');
        if (type.length) {
            resend_elem.attr('data-type', type);
        }
        var time_span = resend_elem.find("span");
        resend_elem.show();
        time_span.show();

        var view_counter = view_change_counter;
        time_span.text(convToMMSS(resendTime));
        var counter = 0;

        var interval = setInterval(function () {
            counter++;

            if (view_counter !== view_change_counter) {
                view_counter = view_change_counter;
                resend_elem = form.find('[data-id="' + resend_id + '"]');
                if (resend_elem.length > 0) {
                    time_span = resend_elem.find("span");
                } else {
                    clearInterval(interval);
                    return false;
                }
            }

            if (counter >= resendTime) {
                clearInterval(interval);
                resend_elem.removeClass("digits_resend_disabled").find("span").hide();
                counter = 0;
            } else {
                var rem = resendTime - counter;
                time_span.text(convToMMSS(rem));
            }
        }, 1000);

    }


    jQuery(document).on('click', '.digits-form_tab-item', function (e) {
        e.preventDefault();
        var $this = jQuery(this);

        var activeClass = 'digits-tab_active';

        var index = $this.index();
        var container = $this.closest('.digits-form_tab_container');
        var tab_view_container = container.find('.digits-form_body_wrapper');


        $this.parent().find('.' + activeClass).removeClass(activeClass);
        $this.addClass(activeClass);

        tab_view_container.find('.' + activeClass).removeClass(activeClass);
        var active_tab = tab_view_container.find('.digits-form_tab_body:eq(' + index + ')');
        active_tab.addClass(activeClass)
        if ($this.data('change')) {
            var change_elem = $this.data('change');
            var form = $this.closest('form');
            var step_value = $this.data('value');
            var step_action_name = active_tab.find('.step_action_name');
            if (step_action_name.length) {
                step_value = step_action_name.val();
            }
            form.find('[name="' + change_elem + '"]').val(step_value);
        }

        if (active_tab.find('.platform_authenticate').length) {
            authenticate_platform(form.serializeArray(), active_tab);
        }

        if (active_tab.find('.auto-click').length) {
            var auto_click = active_tab.find('.auto-click');
            var check_trigger = auto_click.attr('data-triggered');
            if (!check_trigger) {
                auto_click.attr('data-triggered', 1);
                active_tab.find('.auto-click').first().click();
            }
        }
        active_tab.find('.country_code_flag').trigger('update_flag');
        process_view_change(form, active_tab);
        return false;
    });

    function process_view_change(form, tab) {
        if (tab.find('.digits-tab_active').length) {
            tab = tab.find('.digits-tab_active');
        }

        update_form_footer(tab);
        tab.find('input:visible:not(.countrycode)').first().focus().trigger('change');

        var submit_button = form.find('.digits-form_submit-btn');
        if (tab.find('.hide_submit').length) {
            submit_button.hide();
        } else {
            submit_button.show();
        }
        view_change_counter++;

        resposition_ui();
    }

    function update_form_footer(tab) {
        var form_footer = tab.closest('form').find('.digits-form_footer');
        form_footer.empty();

        var footer_content = tab.find('.digits-form_footer_content');
        if (footer_content.length) {
            form_footer.append(footer_content.html());
        }

    }

    function update_reg_fields(form, tab) {

    }

    function process_request() {

    }

    jQuery(document).on('click', '.digits_remote_device_auth', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var form = $this.closest('form');
        var form_data = form.serializeArray();
        var auth_box = $this.closest('.digits_secure_login_auth_wrapper');
        showLoader();

        if ($this.data('remove')) {
            form_data.push({name: "sub_action", value: 'remove_remote_device_auth'});
        } else {
            form_data.push({name: "sub_action", value: 'start_remote_device_auth'});
        }

        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                var data = res.data;
                if (res.success) {
                    if (data.html) {
                        var html = jQuery(data.html);
                        auth_box.empty().html(html);
                    }
                    if (data.check_remote_status) {
                        start_auto_check();
                    }
                } else {
                    if (data.notice) {
                        showDigNoticeMessage(data.message);
                    } else {
                        showDigErrorMessage(data.message);
                    }
                }
                hideLoader();
            },
            error: function (res) {
                showDigErrorMessage(dig_script.ErrorPleasetryagainlater);
                hideLoader();
            }
        });
        return false;
    })

    jQuery(document).on('click', '.digits-form_toggle_login_register', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var wrapper = $this.closest('.digits-form_wrapper');
        var login_class = 'digloginpage';
        var register_class = 'register';
        var login = wrapper.find('.' + login_class);
        var register = wrapper.find('.' + register_class);
        var forgot = wrapper.find('.forgot');
        var active_elem = false;
        if ($this.hasClass('show_register')) {
            login.hide();
            forgot.hide();
            active_elem = register;
        } else {
            forgot.hide();
            register.hide();
            active_elem = login;
        }
        active_elem.show();
        active_elem.find('.mobile_field').trigger('change');
        active_elem.find('input:visible:not(.countrycode)').first().focus();
        resposition_ui();

        return false;
    });

    function resposition_ui() {
        setTimeout(function () {
            jQuery(window).trigger('digits_reposition');
        });
    }

    jQuery(document).on('click', '.digits-form_show_forgot_password', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var wrapper = $this.closest('.digits-form_wrapper');

        var login_class = 'digloginpage';
        var forgot = wrapper.find('.forgot');

        var login = wrapper.find('.' + login_class);

        var login_info = login.find('.digits-form_tab_container').first();

        forgot.find('.digits-form_tab_container').first().empty().html(login_info.html());

        login_info.find('input').each(function () {
            var name = jQuery(this).attr('name');
            if (name) {
                var value = jQuery(this).val();
                forgot.find('input[name="' + name + '"]').val(value);
            }
        });

        login.hide();
        forgot.show();


        forgot.find('.digits_form_back').removeClass('digits_hide_back').attr('data-show_form', login_class);
        process_view_change(forgot, forgot);
        return false;
    })

    jQuery(document).on('click', '.digits_form_back', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var form = $this.closest('form');
        var wrapper = form.find('.digits-form_tab_wrapper');
        var containers = wrapper.find('.digits-form_tab_container');
        var can_hide_back = true;

        form.find('.reset_on_back').val('');
        if (containers.length > 1) {
            var last_tab = containers.last();

            var tab_item = last_tab.find('.digits-form_tab-item').first();
            if (tab_item.data('change')) {
                var change_elem = tab_item.data('change');
                form.find('[name="' + change_elem + '"]').val('');
            }
            last_tab.remove();
            containers = wrapper.find('.digits-form_tab_container');
            var last_container = containers.last();
            last_container.show();

            process_view_change(form, last_container);

            update_form_title(form, last_container);
        } else if ($this.attr('data-show_form')) {
            can_hide_back = false;
            var show_form = $this.attr('data-show_form');
            form.hide();
            form.closest('.digits-form_wrapper').find('form.' + show_form).show();
        }

        if (containers.length === 1 && can_hide_back) {
            $this.addClass('digits_hide_back');
            form.addClass('digits_form_index_section');
            update_form_title(form, false);
        }

    });

    function validate_form(form) {
        var error = false;
        var requiredTextElement = '';


        form.find('input,textarea,select').each(function () {

            var $this = jQuery(this);
            if ($this.is(":hidden")) {
                return;
            }
            if (jQuery(this).attr('required') || jQuery(this).attr('data-req')) {


                var dtype = $this.attr('dtype');

                if (dtype && dtype == 'range') {
                    var range = $this.val().split('-');
                    if (!range[1]) {
                        error = true;
                        $this.addClass('dig_input_error').closest('.digits-input-wrapper').append(requiredTextElement).closest('.digits-form_input_row').addClass('input-error');
                        $this.val('');
                    }
                }
                if ($this.attr('date')) {
                    var is_error = false;
                    if (dtype == 'time') {
                        var validTime = $this.val().match(/^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/);
                        if (!validTime) {
                            is_error = true;
                        }
                    } else if (dtype != 'range') {
                        var date = new Date($this.val());

                        if (!isDateValid(date)) {
                            is_error = true;
                        }
                    } else {
                        var date1 = new Date(range[0]);
                        var date2 = new Date(range[1]);
                        if (!isDateValid(date1) || !isDateValid(date2)) {
                            is_error = true;
                        }
                    }
                    if (is_error) {
                        error = true;
                        $this.addClass('dig_input_error').closest('.digits-input-wrapper').append(requiredTextElement).closest('.digits-form_input_row').addClass('input-error');
                        $this.val('');
                    }
                } else if ($this.is(':checkbox') || $this.is(':radio')) {

                    if (!$this.is(':checked') && !form.find('input[name="' + $this.attr('name') + '"]:checked').val()) {
                        error = true;
                        $this.addClass('dig_input_error').closest('.minput').addClass('input-error').append(requiredTextElement);
                    }

                } else {
                    var value = $this.val();
                    if (value == null || value.length == 0 || (value == -1 && $this.is("select"))) {
                        error = true;
                        if ($this.is("select")) {
                            $this.addClass('dig_input_error').next().addClass('dig_input_error').append(requiredTextElement).closest('.digits-form_input_row').addClass('input-error');
                        } else {
                            $this.addClass('dig_input_error').closest('.digits-input-wrapper').append(requiredTextElement).closest('.digits-form_input_row').addClass('input-error');
                            $this.trigger('focus');
                        }
                    }
                }

            }
        });

        if (form.find('.dig_input_error').length == 1) {
            if (form.find(".dig_opt_mult_con_tac").find('.dig_input_error').length > 0) {
                showDigErrorMessage(dig_script.accepttac);
                return false;
            }
        }

        if (error) {
            showDigNoticeMessage(dig_script.fillAllDetails);
            return false;
        }

        if (form.attr('wait')) {
            showDigNoticeMessage(form.attr('wait'));
            return false;
        }
        if (form.attr('error')) {
            showDigErrorMessage(form.attr('error'));
            return false;
        }

        return true;
    }

    function isDateValid(date) {
        return date.getTime() === date.getTime();
    }

    function showLoader() {
        hideDigMessage();
        loader.fadeIn();
    }

    function hideLoader() {
        loader.fadeOut();
    }

    function authenticate_platform(form_data, active_tab) {
        if (active_tab.find('[name="remote_device_auth"]').length) {
            hideLoader();
            start_auto_check();
            return false;
        }
        showLoader();

        form_data.push({name: "sub_action", value: 'generate_device_key'});

        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                hideLoader();
                process_generate_device_key_request(res, active_tab, false);
            },
            error: function (res) {
                showDigErrorMessage(dig_script.ErrorPleasetryagainlater);
                hideLoader();
            }
        });
        return false;
    }

    var generate_form_data = false;

    function process_generate_device_key_request(res, active_tab, form_data) {
        generate_form_data = form_data;
        var data = res.data;
        if (res.success) {
            if (data.token) {
                var token = data.token;
                digits_device_auth(token, active_tab, authenticate_key, 'get')
            }
        } else {
            if (data.notice) {
                showDigNoticeMessage(data.message);
            } else {
                showDigErrorMessage(data.message);
            }
        }
    }

    function authenticate_key(cred, active_tab, options) {
        cred = encodeURIComponent(JSON.stringify(cred));
        if (is_remote_request) {
            generate_form_data.cred = cred;
            process_auto_login(generate_form_data);
            return;
        }
        var form = active_tab.closest('form');
        active_tab.find('.platform_value').val(cred);
        trigger_form_submit(form);
    }

    function digits_redirect_to(form, redirect_location, delay) {
        setTimeout(function () {

            if (redirect_location == -1 || redirect_location == -2) {
                if (jQuery('.dig-box').is(':visible')) {
                    redirect_location = -1;
                }

                var referrer = document.referrer;
                if (referrer) {
                    var is_account_page = jQuery('#customer_login').length;
                    var is_same = document.referrer.indexOf(location.protocol + "//" + location.host) === 0;
                    if (is_same && (is_account_page || redirect_location == -2)) {
                        window.history.back();
                        return;
                    }
                    if (redirect_location == -2) {
                        document.location.href = "/";
                        return;
                    }
                }
                parse_redirect_url(window.location.href);

            } else {
                parse_redirect_url(redirect_location);
            }

        }, delay);
    }

    function update_fields(body) {
        digits_select(body.find(".digits-form_input_row").find('select'));
        body.find('.digits_register')
            .find('.digits-form_input_row input,.digits-form_input_row textarea')
            .each(function () {
                var inp = jQuery(this);
                var row = inp.closest('.digits-form_input_row');
                var label = row.find('label');
                if (label.length) {
                    var label_text = jQuery.trim(label.text());
                    inp.attr('placeholder', label_text);
                }
            })
    }

    function digits_select($elem) {
        $elem.each(function () {
            var $this = jQuery(this);
            var parent = $this.closest('form');
            $this.untselect({
                dir: dig_script.direction,
                width: '100%',
                //templateSelection: digits_select_format,
                escapeMarkup: function (m) {
                    return m;
                },
                minimumResultsForSearch: 8,
                dropdownParent: parent,
                dropdownCssClass: "digits-select-dropdown digits-form-dropdown digits_select",
                theme: "default digits-select digits-form-select"
            });
        });
    }

    jQuery(window).on('update_digits', function () {
        update_fields(jQuery('body'));
    }).trigger('update_digits');

    var is_remote_request = false;

    function check_auto_login(wait_status) {
        if (auto_login_processed) {
            return;
        }
        var params = new URLSearchParams(window.location.search);
        var method = params.get('method');
        var auth_key = params.get('auth_key');
        var auth_token = params.get('auth_token');
        var wait = params.get('wait');

        if (wait_status && wait) {
            return;
        }
        auto_login_processed = true;

        if (auth_key && auth_token) {
            if (method === 'direct_email_login' || method === 'verify_email' || method === 'remote_device_auth') {
                var form_data = {
                    method: method,
                    auth_key: auth_key,
                    auth_token: auth_token
                }
                is_remote_request = true;
                process_auto_login(form_data);
            }
        }
    }

    jQuery(window).on('digits_auto_login', function () {
        check_auto_login(false);
    });

    function parse_redirect_url(redirect) {
        if (!/(http(s?)):\/\//i.test(redirect)) {
            redirect = window.location.protocol + redirect;
        }
        var url = new URL(redirect);
        var params = new URLSearchParams(url.search);
        params.delete('method')
        params.delete('auth_key')
        params.delete('auth_token');
        params.delete('login');
        params.delete('type');
        params.delete('wait');
        params = params.toString();
        var suffix = '';
        if (params.length > 0) {
            suffix = '?' + params;
        }
        window.location.href = url.origin + url.pathname + suffix
    }

    function remove_email_verify_query() {
        parse_redirect_url(window.location.href);
    }

    check_auto_login(true);

    function process_auto_login(form_data) {
        showLoader();
        form_data['action'] = 'digits_user_remote_action';
        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                var data = res.data;
                var delay = 3000;
                if (res.success) {

                    if (data.message) {
                        showDigSuccessMessage(data.message);

                        setTimeout(function () {
                            parse_redirect_url(window.location.href);
                        }, delay)
                    } else if (data.process_login) {
                        process_email_login(data);
                    } else if (data.process_remote_auth_login) {
                        process_generate_device_key_request(res, false, form_data);
                    } else if (data.body_html) {
                        var html = jQuery(data.body_html)
                        jQuery('body').append(html);
                        html.find('[name="form_data"]').val(JSON.stringify(form_data));
                        hideLoader();
                    }
                } else {
                    if (data.message) {
                        if (data.notice) {
                            showDigNoticeMessage(data.message);
                        } else {
                            showDigErrorMessage(data.message);
                        }
                    }
                    setTimeout(function () {
                        remove_email_verify_query();
                    })

                }
            },
            error: function (res) {
                hideLoader();
            }
        });
    }

    function process_email_login(data) {
        var form_id = data.form_id;
        var verify_token = data.email_verify;
        var wrapper = jQuery('#' + form_id);
        wrapper.show();
        var popup_wrapper = wrapper.find('.digits_popup_wrapper');
        if (popup_wrapper.length) {
            popup_wrapper.show();
        }
        var input = wrapper.find('input[name="digits_login_email_token"]');
        input.val(verify_token);
        var form = input.closest('form');
        if (!form.hasClass('digits_original')) {
            form.addClass('digits-tp_style');
        }

        digits_form_submit(form);
    }

    var isBlur = false;

    function pause_method_status_request() {
        isBlur = true;
    }

    window.addEventListener('blur', pause_method_status_request);
    window.addEventListener('focus', check_login_status);

    function check_login_status() {
        isBlur = false;
        if (jQuery('.digits_otp_input-field').not('.disable_auto_read').is(":visible")) {
            process_method_status_request(false);
        }
    }

    var method_status_interval = false;
    var method_status_duration = 1750;

    function start_auto_check() {
        cancel_method_status_interval_handler();
        start_method_status_handler();
    }

    function trigger_form_submit(form) {
        var btn = form.find('.digits-form_submit-btn');
        if (!btn || !btn.length) {
            btn = form.find('[type="submit"]');
        }
        btn.prop("onclick", null).trigger('click');
    }

    function process_method_status_request(poll) {
        var check_elem = jQuery('.digits_auto_check:visible');
        if (!check_elem.length) {
            return;
        }
        var form = check_elem.closest('form');
        var form_data = form.serializeArray();
        form_data.push({name: "check_status", value: '1'});
        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                var data = res.data;
                if (res.success) {
                    var status = data.status;
                    if (status === 'completed') {
                        var wrapper = check_elem.closest('.digits_secure_login_auth_wrapper');
                        if (data.verification_code) {
                            wrapper.find('.otp_input:visible').val(data.verification_code);
                        } else {
                            var change_class = wrapper.data('change');
                            wrapper.find('.' + change_class).val('remote');
                        }
                        trigger_form_submit(form);
                    } else {
                        if (poll) {
                            start_method_status_handler();
                        }
                    }

                } else {
                    if (data.message) {
                        showDigErrorMessage(data.message);
                    }
                }
                if (data.reload) {
                    location.reload();
                }

                if (data.redirect_to) {
                    setTimeout(function () {
                        parse_redirect_url(data.redirect_to);
                    })
                }
            },
            error: function (res) {
            }
        });
    }

    function send_method_status_request() {
        cancel_method_status_interval_handler();
        process_method_status_request(true);
    }

    function start_method_status_handler() {
        method_status_interval = setTimeout(send_method_status_request, method_status_duration);
    }

    function cancel_method_status_interval_handler() {
        clearTimeout(method_status_interval);
    }


    jQuery(document).on('click', '.digits_approval_sbm_btn', function (e) {
        e.preventDefault();
        var $this = jQuery(this);
        var form = $this.closest('form');
        var show_class = $this.data('show');
        var box = $this.closest('.digits_approval_box');

        if (show_class) {
            $this.closest('.digits_approval_container').hide();
            box.find('.' + show_class).show();
            return false;
        }
        showLoader();
        var action_type = $this.data('action');

        var form_data = JSON.parse(form.find('[name="form_data"]').val());
        var nonce = form.find('[name="digits_email_approval"]').val();
        form_data['nonce'] = nonce;
        form_data['action_type'] = action_type;
        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: form_data,
            success: function (res) {
                hideLoader();
                var data = res.data;
                isFormLoading = false;
                if (res.success) {
                    if (data.message) {
                        showDigSuccessMessage(data.message)
                    }
                    if ($this.data('redirect-home')) {
                        remove_email_verify_query();
                        return false;
                    }
                } else {
                    showDigErrorMessage(data.message);
                }
                if (data.redirect_to) {
                    setTimeout(function () {
                        parse_redirect_url(data.redirect_to);
                    })
                }
            }
        });
        return false;
    })


    jQuery(document).on('click', '.digits_resend_email_verification', function (e) {
        e.preventDefault();
        showLoader();
        var $this = jQuery(this);
        var data = {
            'action': 'digits_resend_email_verification',
        };
        data['nonce'] = $this.data('nonce');
        data['user'] = $this.data('user');
        jQuery.ajax({
            type: 'post',
            url: dig_script.ajax_url,
            data: data,
            success: function (res) {
                hideLoader();
                var data = res.data;
                if (res.success) {
                    if (data.message) {
                        showDigSuccessMessage(data.message)
                    }
                } else {
                    showDigErrorMessage(data.message);
                }
            }
        });

        return false;
    });

    function convToMMSS(timeInSeconds) {
        var sec_num = parseInt(timeInSeconds, 10);
        var hours = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (minutes < 10) {
            minutes = "0" + minutes;
        }
        if (seconds < 10) {
            seconds = "0" + seconds;
        }
        return "(" + minutes + ':' + seconds + ")";
    }


    jQuery(document).on('focus blur', '.digits_password_inp_row input', function (e) {
        var $this = jQuery(this);
        var container = $this.closest('.digits_password_inp_row');
        if (e.type === 'focusout') {
            container.removeClass('show-eye');
        } else {
            container.addClass('show-eye');
        }

    })

    var eyeResetTimer = null;
    jQuery(document).on('click', '.digits_password_eye', function (e) {
        var closedEye = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>';
        var openedEye = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>';
        var $this = jQuery(this);
        var eye_line = $this.find('.digits_password_eye-line');
        var eye = $this.find('svg');
        var input = $this.closest('.digits_password_inp_row').find('input');

        var input_type = 'password';
        clearTimeout(eyeResetTimer)
        if (!$this.hasClass('eye-closed')) {
            input_type = 'text';
            $this.addClass('eye-closed')
            eye_line.show().removeClass('digits_password_eye-opened-line').addClass('digits_password_eye-closed-line')
            eye.html(closedEye);
        } else {
            input_type = 'password';
            $this.removeClass('eye-closed');
            eye_line.removeClass('digits_password_eye-closed-line').addClass('digits_password_eye-opened-line')
            eye.html(openedEye);
            eyeResetTimer = setTimeout(function () {
                eye_line.hide().removeClass('digits_password_eye-opened-line')
            }, 120);
        }
        input.attr('type', input_type)
        return false;
    });

})

function digits_hide_loader() {
    var loader = jQuery(".dig_load_overlay").first();
    loader.fadeOut();
}

function digits_device_auth(options, form, callback, type) {
    var public_key = options;
    if (options.public_key) {
        public_key = options.public_key;
    }

    if (window.location.protocol === "http:") {
        window.location.href = window.location.href.replace('http:', 'https:');
        return;
    }

    public_key = digits_preparePublicKeyOptions(public_key);

    if (type === 'create') {
        navigator.credentials.create({publicKey: public_key})
            .then(function (cred) {
                cred = digits_preparePublicKeyCredentials(cred);
                callback(cred, form, options);
            }).catch(function (error) {
            showDigErrorMessage("Error");
            console.log(error);
            digits_hide_loader();
        });
    } else {
        navigator.credentials.get({publicKey: public_key})
            .then(function (cred) {
                cred = digits_preparePublicKeyCredentials(cred);
                callback(cred, form, options);
            }).catch(function (error) {
            console.log(error);
            digits_hide_loader();
        });
    }

}

function digits_base64UrlDecode(input) {
    input = input
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    var pad = input.length % 4;
    if (pad) {
        input += new Array(5 - pad).join('=');
    }

    return window.atob(input);
};

function digits_preparePublicKeyOptions(publicKey) {
    publicKey.challenge = Uint8Array.from(
        digits_base64UrlDecode(publicKey.challenge),
        function (c) {
            return c.charCodeAt(0);
        }
    );
    if (publicKey.user !== undefined) {
        publicKey.user.id = Uint8Array.from(
            window.atob(publicKey.user.id),
            function (c) {
                return c.charCodeAt(0);
            }
        )
    }
    if (publicKey.excludeCredentials !== undefined) {
        publicKey.excludeCredentials = publicKey.excludeCredentials.map(
            function (data) {
                data['id'] = Uint8Array.from(
                    digits_base64UrlDecode(data.id),
                    function (c) {
                        return c.charCodeAt(0);
                    }
                )
                return data;
            }
        );
    }

    if (publicKey.allowCredentials !== undefined) {
        publicKey.allowCredentials = publicKey.allowCredentials.map(
            function (data) {
                data['id'] = Uint8Array.from(
                    digits_base64UrlDecode(data.id),
                    function (c) {
                        return c.charCodeAt(0);
                    }
                )
                return data;
            }
        );
    }

    return publicKey;
}

function digits_arrayToBase64String(buffer) {
    var binary = '';
    var bytes = new Uint8Array(buffer);
    var len = bytes.byteLength;
    for (var i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function digits_preparePublicKeyCredentials(data) {

    var publicKeyCredential = {
        id: data.id,
        type: data.type,
        rawId: digits_arrayToBase64String(new Uint8Array(data.rawId)),
        response: {
            clientDataJSON: digits_arrayToBase64String(
                new Uint8Array(data.response.clientDataJSON)
            ),
        },
    };

    if (data.response.attestationObject !== undefined) {
        publicKeyCredential.response.attestationObject = digits_arrayToBase64String(
            new Uint8Array(data.response.attestationObject)
        );
    }

    if (data.response.authenticatorData !== undefined) {
        publicKeyCredential.response.authenticatorData = digits_arrayToBase64String(
            new Uint8Array(data.response.authenticatorData)
        );
    }

    if (data.response.signature !== undefined) {
        publicKeyCredential.response.signature = digits_arrayToBase64String(
            new Uint8Array(data.response.signature)
        );
    }

    if (data.response.userHandle !== undefined) {
        publicKeyCredential.response.userHandle = digits_arrayToBase64String(
            new Uint8Array(data.response.userHandle)
        );
    }

    return publicKeyCredential;
};