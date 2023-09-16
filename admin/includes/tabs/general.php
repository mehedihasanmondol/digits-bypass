<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_basic()
{
    $countryList = getCountryList();

    $currentCountry = get_option("dig_default_ccode", 'United States');
    $whiteListCountryCodes = get_option("whitelistcountrycodes");
    $blacklistcountrycodes = get_option("dig_blacklistcountrycodes");
    $dig_hide_countrycode = get_option('dig_hide_countrycode', 0);

    $dig_send_otp_together = get_digits_otp_immediately_methods();

    $dig_otp_size = get_option("dig_otp_size", 6);
    ?>
    <div class="dig_admin_head"><span><?php _e('Basic Settings', 'digits'); ?></span></div>
    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table" style="overflow: hidden">

                <tr>
                    <th scope="row" style="vertical-align:top;">
                        <label class="dig_label_top_18" for="dig_otp_size">
                            <?php _e('Immediately send OTP on', 'digits'); ?>
                        </label></th>
                    <td>
                        <?php
                        digits_input_checkbox('dig_send_otp_together', 'sms_otp', $dig_send_otp_together, __('SMS', 'digits'));
                        digits_input_checkbox('dig_send_otp_together', 'whatsapp_otp', $dig_send_otp_together, __('WhatsApp', 'digits'));
                        digits_input_checkbox('dig_send_otp_together', 'email_otp', $dig_send_otp_together, __('Email', 'digits'));
                        ?>
                        <p class="dig_ecr_desc">
                            <?php esc_attr_e('At the time of Login, the OTP will be sent automatically after clicking on "Continue" button on login form on all the routes selected above.', 'digits'); ?>
                        </p>
                    </td>
                </tr>


                <tr class="disotp">
                    <th scope="row" style="vertical-align:top;"><label
                                for="dig_otp_size"><?php _e('OTP size', 'digits'); ?>
                        </label></th>
                    <td>
                        <div class="dig_gs_nmb_ovr_spn">
                            <input dig-min="4" type="number" name="dig_otp_size"
                                   value="<?php echo esc_attr($dig_otp_size); ?>"
                                   id="dig_otp_size"
                                   placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="4" max="12"
                                   step="1" required/>
                            <span style="left:51px;"><?php _e('characters', 'digits'); ?></span>
                        </div>
                    </td>
                </tr>


                <tr>
                    <th scope="row"><label><?php _e('Default Country Code', 'digits'); ?> </label></th>
                    <td>
                        <select name="default_ccode" class="dig_inp_wid3 dig_inp_wid_wil">
                            <option value="-1">Disabled</option>
                            <?php
                            $valCon = "";
                            foreach ($countryList as $key => $value) {
                                $ac = "";


                                if ($currentCountry == $key) {
                                    $ac = "selected=selected";
                                }
                                echo '<option class="dig-cc-visible" ' . $ac . ' value="' . $key . '" country="' . digits_strtolower($key) . '">' . getTranslatedCountryName($key) . ' (+' . $value . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="whitelistcountrycodes"><?php _e('Country Codes Allowlist', 'digits'); ?></label>
                    </th>
                    <td>

                        <select name="whitelistcountrycodes[]" class="whitelistcountrycodeslist dig_multiselect_enable"
                                multiple="multiple">
                            <?php


                            foreach ($countryList as $key => $value) {
                                $ac = "";
                                if ($whiteListCountryCodes) {
                                    if (in_array($key, $whiteListCountryCodes)) {
                                        $ac = "selected=selected";
                                    }
                                }
                                echo '<option value="' . $key . '" ' . $ac . '>' . getTranslatedCountryName($key) . ' (+' . $value . ')</option>';
                            }


                            ?>
                        </select><br/>
                        <p class="dig_ecr_desc"><?php _e('Sign In/Sign Up will be allowed for phone numbers with these country codes. To allow Sign In/Sign Up for all country codes, leave this blank.', 'digits'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="blacklistcountrycodes"><?php _e('Country Codes Denylist', 'digits'); ?></label>
                    </th>
                    <td>

                        <select name="blacklistcountrycodes[]" class="blacklistcountrycodes dig_multiselect_enable"
                                multiple="multiple">
                            <?php


                            foreach ($countryList as $key => $value) {
                                $ac = "";
                                if ($blacklistcountrycodes) {
                                    if (in_array($key, $blacklistcountrycodes)) {
                                        $ac = "selected=selected";
                                    }
                                }
                                echo '<option value="' . $key . '" ' . $ac . '>' . getTranslatedCountryName($key) . ' (+' . $value . ')</option>';
                            }


                            ?>
                        </select><br/>
                        <p class="dig_ecr_desc"><?php _e('Sign In/Sign Up will be not allowed for phone numbers with these country codes. To allow Sign In/Sign Up for all country codes, leave this blank.', 'digits'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="phonenumberdenylist"><?php _e('Phone numbers Denylist', 'digits'); ?></label></th>
                    <td>

                        <select name="phonenumberdenylist[]"
                                class="dig_ignore_untselect phonenumberdenylist dig_multiselect_phone_dynamic_enable dig_sens_data"
                                multiple="multiple">
                            <?php
                            $dig_phonenumberdenylist = get_option("dig_phonenumberdenylist");

                            if (is_array($dig_phonenumberdenylist)) {
                                foreach ($dig_phonenumberdenylist as $value) {
                                    echo '<option value="' . $value . '" selected=selected>' . $value . '</option>';
                                }
                            }

                            ?>
                        </select><br/>
                        <p class="dig_ecr_desc"><?php _e('Sign In/Sign Up will be not allowed for these phone numbers.', 'digits'); ?></p>
                    </td>
                </tr>
            </table>
            <?php

            $digits_hidecountrycode_style = 'style="display:none;"';
            if (is_array($whiteListCountryCodes)) {
                if (count($whiteListCountryCodes) == 1) {
                    $digits_hidecountrycode_style = 'style="display:block;"';
                }
            }
            ?>
            <div id="digits_hidecountrycode" <?php echo $digits_hidecountrycode_style; ?>>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label class="top-10"><?php _e('Hide Country Code', 'digits'); ?> </label></th>
                        <td>
                            <?php digits_input_switch('dig_hide_countrycode', $dig_hide_countrycode); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php
}

function digits_settings_woocommerce()
{

    $dig_reqfieldbilling = get_option("dig_reqfieldbilling", 0);

    $enable_wc_autofill = get_option('dig_autofill_wc_billing', 1);
    $dig_redirect_wc_to_dig = get_option('dig_redirect_wc_to_dig', 0);
    $enable_createcustomeronorder = get_option('enable_createcustomeronorder');

    ?>

    <div class="dig_admin_head"><span><?php _e('WooCommerce Settings', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table">
                <tr>
                    <th scope="row"><label
                                class="no-top"><?php _e('Redirect WooCommerce account page to Digits login page', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_redirect_wc_to_dig', $dig_redirect_wc_to_dig); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="enable_createcustomeronorder"
                                           class="top-10"><?php _e('Create Customer Button', 'digits'); ?>
                        </label></th>
                    <td>
                        <?php digits_input_switch('enable_createcustomeronorder', $enable_createcustomeronorder); ?>
                        <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('Add customer on Add Order Page on dashboard using Modal', 'digits'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="dig_reqfieldbilling"><?php _e('Required field for billing info', 'digits'); ?>
                        </label></th>
                    <td>
                        <select name="dig_reqfieldbilling" id="dig_reqfieldbilling" class="dig_inp_wid3">
                            <option value="0" <?php if ($dig_reqfieldbilling == 0) {
                                echo 'selected=selected';
                            } ?> ><?php _e('Mobile Number and Email', 'digits'); ?></option>
                            <option value="1" <?php if ($dig_reqfieldbilling == 1) {
                                echo 'selected=selected';
                            } ?> ><?php _e('Mobile Number', 'digits'); ?></option>
                            <option value="2" <?php if ($dig_reqfieldbilling == 2) {
                                echo 'selected=selected';
                            } ?> ><?php _e('Email', 'digits'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="enable_autofillcustomerdetails"
                                           class="top-10"><?php _e('Autofill WooCommerce billing fields with user info', 'digits'); ?>
                        </label></th>
                    <td>
                        <?php digits_input_switch('enable_autofillcustomerdetails', $enable_wc_autofill); ?></td>
                </tr>


                <?php
                $enable_guest_checkout_verification = get_option('digits_enable_guest_checkout_verification', 0);
                $enable_billing_phone_verification = get_option('digits_enable_billing_phone_verification', 0);

                ?>
                <tr>
                    <th scope="row"><label for="digits_enable_guest_checkout_verification"
                                           class="top-10"><?php _e('Enable guest checkout billing phone verification', 'digits'); ?>
                        </label></th>
                    <td>

                        <select id="digits_enable_guest_checkout_verification"
                                name="digits_enable_guest_checkout_verification">
                            <option value="0" <?php if ($enable_guest_checkout_verification == 0) echo 'selected'; ?>>
                                <?php _e('Disable', 'digits'); ?>
                            </option>
                            <option value="cod" <?php if ($enable_guest_checkout_verification == 'cod') echo 'selected'; ?>>
                                <?php _e('For Cash on Delivery', 'digits'); ?>
                            </option>
                            <option value="all_methods" <?php if ($enable_guest_checkout_verification == 'all_methods') echo 'selected'; ?>>
                                <?php _e('For All Payment Methods', 'digits'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="digits_enable_billing_phone_verification"
                                           class="top-10"><?php _e('Enable checkout billing phone verification', 'digits'); ?>
                        </label></th>
                    <td>

                        <select id="digits_enable_billing_phone_verification"
                                name="digits_enable_billing_phone_verification">
                            <option value="0" <?php if ($enable_billing_phone_verification == 0) echo 'selected'; ?>>
                                <?php _e('Disable', 'digits'); ?>
                            </option>
                            <option value="cod" <?php if ($enable_billing_phone_verification == 'cod') echo 'selected'; ?>>
                                <?php _e('For Cash on Delivery', 'digits'); ?>
                            </option>
                            <option value="all_methods" <?php if ($enable_billing_phone_verification == 'all_methods') echo 'selected'; ?>>
                                <?php _e('For All Payment Methods', 'digits'); ?>
                            </option>
                        </select>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

function digits_settings_redirection()
{
    ?>
    <div class="dig_admin_head"><span><?php _e('Redirection Settings', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table dig_cs_re">
                <tr>
                    <th scope="row"><label
                                for="digits_myaccount_redirect"><?php _e('My Account Link', 'digits'); ?></label></th>
                    <td>

                        <input type="url" id="digits_myaccount_redirect" name="digits_myaccount_redirect"
                               value="<?php echo esc_attr(get_option("digits_myaccount_redirect")); ?>"
                               placeholder="<?php _e("URL", "digits"); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="digits_loginred"><?php _e('Login Redirect', 'digits'); ?></label></th>
                    <td>

                        <input type="url" id="digits_loginred" name="digits_loginred"
                               value="<?php echo esc_attr(get_option("digits_loginred")); ?>"
                               placeholder="<?php _e("URL", "digits"); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="digits_regred"><?php _e('Signup Redirect', 'digits'); ?></label></th>
                    <td>
                        <input type="url" id="digits_regred" name="digits_regred"
                               value="<?php echo esc_attr(get_option("digits_regred")); ?>"
                               placeholder="<?php _e("URL", "digits"); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="digits_forgotred"><?php _e('Forgot Password Redirect', 'digits'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="digits_forgotred" name="digits_forgotred"
                               value="<?php echo esc_attr(get_option("digits_forgotred")); ?>"
                               placeholder="<?php _e("URL", "digits"); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
                    </td>
                </tr>
                <tr class="dig_csmargn">
                    <th scope="row"><label for="digits_logoutred"><?php _e('Logout Redirect', 'digits'); ?></label></th>
                    <td>
                        <input type="url" id="digits_logoutred" name="digits_logoutred"
                               value="<?php echo esc_attr(get_option("digits_logoutred")); ?>"
                               placeholder="<?php _e("URL", "digits"); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $hint = esc_attr__('Redirection settings only work for WordPress Native, WooCommerce and Digits Native Forms.', 'digits');
            $hint .= "<br /><br />";
            $hint .= esc_attr__('You can also use user placeholders in redirection.', 'digits');
            digits_settings_show_hint($hint)
            ?>
        </div>
    </div>
    <?php
}

function digits_settings_miscellaneous()
{
    ?>
    <div class="dig_admin_head"><span><?php _e('Miscellaneous', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">

            <table class="form-table">
                <tr>
                    <?php
                    $brute_force_protection = get_option('digits_brute_force_protection', 1);
                    ?>
                    <th scope="row">
                        <label class="top-10" for="digits_brute_force_protection">
                            <?php _e('Enable Brute Force Protection', 'digits'); ?>
                        </label>
                    </th>
                    <td>

                        <?php digits_input_switch('digits_brute_force_protection', $brute_force_protection); ?>

                    </td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="brute_force_allowed_ip">
                            <?php _e('Brute Force Allow-listed IPs', 'digits'); ?>
                        </label></th>
                    <td>

                        <select name="brute_force_allowed_ip[]"
                                class="dig_ignore_untselect dig_multiselect_dynamic_enable dig_sens_data"
                                multiple="multiple">
                            <?php
                            $dig_brute_force_allowed_ip = get_option("dig_brute_force_allowed_ip");
                            $current_ip = digits_get_ip();
                            if (empty($dig_brute_force_allowed_ip) || !in_array($current_ip, $dig_brute_force_allowed_ip)) {
                                echo '<option value="' . esc_attr($current_ip) . '">' . $current_ip . '</option>';
                            }

                            if (is_array($dig_brute_force_allowed_ip)) {
                                foreach ($dig_brute_force_allowed_ip as $value) {
                                    $value = esc_attr($value);
                                    echo '<option value="' . $value . '" selected=selected>' . $value . '</option>';
                                }
                            }

                            ?>
                        </select><br/>
                        <p class="dig_ecr_desc"><?php _e('Add the IP(s) to not get blocked by our brute force detection system', 'digits'); ?></p>
                    </td>
                </tr>

                <tr>
                    <?php
                    $sameorigin_protection = get_option('digits_sameorigin_protection', 0);
                    ?>
                    <th scope="row">
                        <label class="top-10" for="digits_sameorigin_protection">
                            <?php _e('Allow Digits forms in iframe', 'digits'); ?>
                        </label>
                    </th>
                    <td>

                        <?php digits_input_switch('digits_sameorigin_protection', $sameorigin_protection); ?>

                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>
                            <?php _e('Export / Import Settings', 'digits'); ?>
                        </label>
                    </th>
                    <td>

                        <button id="digits_configuration_export" class="button"
                                type="button"><?php _e('Export', 'digits'); ?></button>
                        <button id="digits_configuration_import" class="button"
                                type="button"><?php _e('Import', 'digits'); ?></button>
                    </td>
                </tr>
                <tr>
                    <?php
                    $usage_sharing = get_option('digits_usage_data_sharing', 1);
                    $usage_id = get_option('digits_usage_data_sharing_id', false);
                    if (!$usage_id) {
                        $usage_id = md5(uniqid('digits'));
                        update_option('digits_usage_data_sharing_id', $usage_id);
                    }
                    ?>
                    <th scope="row">
                        <label class="top-10" for="digits_usage_data_sharing">
                            <?php _e('Usage Data Sharing', 'digits'); ?>
                        </label>
                    </th>
                    <td>
                        <?php digits_input_switch('digits_usage_data_sharing', $usage_sharing); ?>
                        <input type="hidden" name="random_id" value="<?php echo esc_attr($usage_id); ?>"/>
                        <p class="dig_ecr_desc"><?php _e('Help us improve Digits by opting in to share non-sensitive plugin data', 'digits'); ?></p>
                    </td>
                </tr>

            </table>

            <div class="dig_admin_sec_head dig_admin_sec_head_margin">
                <span><?php _e('Advanced Options', 'digits'); ?></span>
            </div>
            <?php
            $custom_css = get_option('digit_custom_css');
            $custom_css = stripslashes($custom_css);
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="dig_custom_css"><?php _e('Custom CSS', 'digits'); ?> </label></th>
                    <td><textarea name="digit_custom_css" rows="6"
                                  class="dig_inp_wid28"
                                  id="dig_custom_css"><?php echo esc_attr($custom_css); ?></textarea></td>
                </tr>
            </table>

        </div>
    </div>
    <?php
}

function digits_settings_export_import_modal()
{
    ?>
    <div class="dig_presets_modal dig_overlay_modal_content" id="dig_export_import_content">
        <div class="dig-flex_center">
            <div id="dig_presets_modal_box">
                <div id="dig_presets_modal_body" class="dig-admin-modal">
                    <div class="modal_head"></div>
                    <div class="modal_body">
                    <textarea class="dig_export_import_values"
                              placeholder="<?php _e('Paste your import code here...', 'digits') ?>"></textarea>
                    </div>
                    <div class="dig_ex_imp_bottom">
                        <button class="imp_exp_button button imp_exp_btn_fn" type="button"
                                attr-export="<?php _e('COPY', 'digits'); ?>"></button>
                        <div class="imp_exp_button imp_exp_cancel dig_presets_modal_head_close"
                             id="dig_presets_modal_head_close"><?php _e('CLOSE', 'digits'); ?></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php
}

add_action('digits_setting_modal', 'digits_settings_export_import_modal');