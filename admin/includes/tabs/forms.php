<?php


function show_protected_by_digits()
{
    return get_option('show_protected_by_digits', 1);
}

function digits_settings_auth_general()
{
    $show_asterisk = get_option('dig_show_asterisk', 0);
    $wp_login_inte = get_option("dig_wp_login_inte", 0);
    $login_reg_success_msg = get_option('login_reg_success_msg', 1);

    $dig_mobile_no_formatting = get_option('dig_mobile_no_formatting', 1);

    $dig_mobile_no_placeholder = get_option('dig_mobile_no_placeholder', 1);


    $wp_login_hide = get_option("dig_wp_login_hide", 0);

    $show_labels = get_option('dig_show_labels', 0);

    $show_protected_by = show_protected_by_digits();
    ?>
    <div class="dig_admin_head"><span><?php _e('Forms General', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table">
                <tr>
                    <th scope="row"><label class="top-10"><?php _e('Show Protected by Digits', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('show_protected_by_digits', $show_protected_by); ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label><?php _e('Mobile Number Formatting', 'digits'); ?> </label></th>
                    <td>
                        <select name="dig_mobile_no_formatting">
                            <option value="2" <?php if ($dig_mobile_no_formatting == 2) {
                                echo 'selected="selected"';
                            } ?>><?php _e('Local', 'digits'); ?></option>
                            <option value="1" <?php if ($dig_mobile_no_formatting == 1) {
                                echo 'selected="selected"';
                            } ?>><?php _e('International', 'digits'); ?></option>
                            <option value="0" <?php if ($dig_mobile_no_formatting == 0) {
                                echo 'selected="selected"';
                            } ?>><?php _e('No', 'digits'); ?></option>
                        </select>
                        <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This function only works on Digits Native Forms', 'digits'); ?></p>

                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                class="top-10"><?php _e('Enable /wp-login.php Integration', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_wp_login_inte', $wp_login_inte); ?>
                    </td>
                </tr>


                <tr>
                    <th scope="row"><label
                                class="top-10"><?php _e('Redirect /wp-login.php to Digits', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_wp_login_hide', $wp_login_hide); ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                class="top-10"><?php _e('Show Mobile Number Placeholder', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_mobile_no_placeholder', $dig_mobile_no_placeholder); ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                class="top-10"><?php _e('Show Field Labels', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_show_labels', $show_labels); ?>
                    </td>
                </tr>

                <tr id="showasteriskrow">
                    <th scope="row"><label
                                class="top-10"><?php _e('Show asterisk (*) on required fields', 'digits'); ?> </label>
                    </th>
                    <td>
                        <?php digits_input_switch('dig_show_asterisk', $show_asterisk); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="login_reg_success_msg"
                                class="top-10"><?php _e('Login/Registration Success Message', 'digits'); ?></label>
                    </th>
                    <td>
                        <?php digits_input_switch('login_reg_success_msg', $login_reg_success_msg); ?>

                        <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This function only works on Digits Native Forms', 'digits'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label
                                for="digits_form_font_family">
                            <?php _e('Font Family', 'digits'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="digits_form_font_family" name="digits_form_font_family">
                            <?php
                            require_once dirname(__FILE__) . '/fonts.php';
                            $digits_form_font_family = digits_get_font_family();
                            $digits_font = digits_font_list();
                            foreach ($digits_font as $font_group => $font_list) {
                                $group_name = ucfirst($font_group);
                                ?>
                                <optgroup label="<?php echo esc_attr($group_name); ?>">
                                    <?php
                                    foreach ($font_list as $font) {
                                        $font_key = $font_group . '@' . $font;
                                        $selected = '';
                                        if ($digits_form_font_family == $font_key) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($font_key); ?>" <?php echo $selected; ?>>

                                            <?php echo ucfirst($font); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </optgroup>
                                <?php
                            }

                            ?>
                        </select>
                        <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This function only works on Digits Native Forms', 'digits'); ?></p>
                    </td>
                </tr>

            </table>
        </div>
    </div>
    <?php
}

function digits_settings_auth_login()
{
    $digforgotpass = get_option('digforgotpass', 1);
    $dig_overwrite_forgotpass_link = get_option('dig_overwrite_forgotpass_link', 1);

    $dig_third_party_more_secure = get_option('dig_third_party_more_secure', 1);


    $dig_only_allow_secure_logins = get_option('dig_only_allow_secure_logins', 0);
    ?>
    <div class="dig_admin_head"><span><?php _e('Login Settings', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label
                                    class="top-10"><?php _e('Make Third Party Login Forms More Secure', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_third_party_more_secure', $dig_third_party_more_secure); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label
                                    class="top-10"><?php _e('Allow Logins only from Digits Secure Form', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_only_allow_secure_logins', $dig_only_allow_secure_logins); ?>
                        </td>
                    </tr>
                    <tr class="enabledisableforgotpasswordrow">
                        <th scope="row"><label class="top-10"><?php _e('Enable Forgot Password', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_enable_forgotpass', $digforgotpass); ?>

                            <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This function only works on Digits Native Forms', 'digits'); ?></p>
                        </td>
                    </tr>

                    <tr class="enabledisableforgotpasswordrow">
                        <th scope="row"><label
                                    class="top-10"><?php _e('Use Digits form as default Forgot Password form', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_overwrite_forgotpass_link', $dig_overwrite_forgotpass_link); ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="dig_admin_sec_head dig_admin_sec_head_margin"><span><?php _e('Form Fields', 'digits'); ?></span>
            </div>

            <table class="form-table">
                <?php
                $dig_login_field_details = digit_get_login_fields();
                foreach (digit_default_login_fields() as $login_field => $values) {
                    if ($login_field == 'dig_login_captcha') {
                        continue;
                    }
                    $field_value = $dig_login_field_details[$login_field];
                    ?>
                    <tr>
                        <th scope="row"><label class="top-10"><?php _e($values['name'], "digits"); ?> </label></th>
                        <td>
                            <?php digits_input_switch($login_field, $field_value); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <?php
                    $captcha = get_option('dig_login_captcha', 0);
                    ?>
                    <th scope="row"><label><?php _e('Captcha', "digits"); ?> </label></th>
                    <td>
                        <select name="dig_login_captcha"
                                class="dig_custom_field_sel">
                            <option value="0" <?php if ($captcha == 0) {
                                echo 'selected';
                            } ?>><?php _e('Disable', 'digits'); ?></option>
                            <option value="2" <?php if ($captcha == 2) {
                                echo 'selected';
                            } ?>><?php _e('ReCaptcha', 'digits'); ?></option>
                            <option value="1" <?php if ($captcha == 1) {
                                echo 'selected';
                            } ?>><?php _e('Simple Captcha', 'digits'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <?php
                    $remember_me = get_option('dig_login_rememberme', 1);
                    ?>
                    <th scope="row"><label><?php _e('Remember Me', "digits"); ?> </label></th>
                    <td>
                        <select name="dig_login_rememberme"
                                class="dig_custom_field_sel">
                            <option value="2" <?php if ($remember_me == 2) {
                                echo 'selected';
                            } ?>><?php _e('Always', 'digits'); ?></option>
                            <option value="1" <?php if ($remember_me == 1) {
                                echo 'selected';
                            } ?>><?php _e('Yes (Show Checkbox)', 'digits'); ?></option>
                            <option value="0" <?php if ($remember_me == 0) {
                                echo 'selected';
                            } ?>><?php _e('No', 'digits'); ?></option>
                        </select>
                    </td>
                </tr>

            </table>
            <div class="dig_admin_sec_head dig_admin_sec_head_margin_top">
            </div>
            <?php
            digits_admin_login_allowed_methods();
            ?>

        </div>
        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $hint = __('With User / UserRole based login flow you can define unique login methods based on user roles or any particular users.', 'digits');
            $hint .= "<br /><br />";
            $hint .= __('For example, you can only let admin user role to login using OTP and all other user roles should login using password', 'digits');
            $hint .= "<br /><br />";
            $hint .= '<b>' . __('Note:', 'digits') . '&nbsp;</b>';
            $hint .= __('If Firebase is being used as the SMS gateway, users can only receive an SMS OTP if they log in with their phone number. This means that attempting to log in using an email or username will not trigger the SMS OTP for security reasons.', 'digits');
            digits_settings_show_hint($hint);
            ?>


        </div>
    </div>
    <?php
}


function digits_settings_form_style()
{
    ?>
    <div class="dig_admin_head">
        <span><?php _e('Native Form Style', 'digits'); ?></span><span class="dig_admin_tag dig_admin_tag_new"><?php esc_attr_e('New', 'digits'); ?></span>
    </div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <?php
            digit_customize(false);
            ?>
        </div>
    </div>
    <?php
}

function digits_settings_old_form_style()
{
    ?>
    <div class="dig_admin_head">
        <span><?php _e('Native Form Style', 'digits'); ?></span><span class="dig_admin_tag dig_admin_tag_old"><?php esc_attr_e('Deprecated', 'digits'); ?></span>
    </div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <?php
            digit_old_customize(false);
            ?>
        </div>
        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $text = __('Old style pages will soon be deprecated, so its recommended to change to new styling as soon as possible.', 'digits');
            $text .= '<br /><br />';
            $text .= __('With old styling you will also be missing on some cool new features.', 'digits');
            digits_settings_show_hint($text);
            ?>

        </div>
    </div>
    <?php

}

add_action('digits_box_wrapper', 'digits_sandbox_view');
function digits_sandbox_view($style)
{
    $license_type = dig_get_option('dig_license_type', 2);
    if ($license_type == 1) {
        return;
    }
    ?>
    <div>
        <div style="pointer-events: none;position: absolute; bottom: 44px; right: 40px; ">
            <div style="
            border:1px solid var(--dfield_bg);
            color: var(--dtitle);
            background: var(--dform_bg);
            display: flex;
            flex-direction:row;
            border-radius: 16px;
            box-shadow: 0px 3px 6px #7E39FF05;
            padding: 17px 14px;
            ">
                <div style="margin-left: 2px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                        <g id="badge-logo" transform="translate(-1254 -763)">
                            <rect id="Rectangle_233" data-name="Rectangle 233" width="32" height="32"
                                  transform="translate(1254 763)" fill="rgba(255,255,255,0)"/>
                            <g id="logo-final" transform="translate(1254.484 765.482)">
                                <g id="Group_382" data-name="Group 382" transform="translate(0 0.516)">
                                    <g id="Group_381" data-name="Group 381">
                                        <path id="Path_110" data-name="Path 110"
                                              d="M283.3,439.844l1.934-5.962a69.3,69.3,0,0,1,9.7,4.061c-.516-5.06-.806-8.541-.838-10.442h6.091c-.1,2.772-.419,6.22-.967,10.41a72.39,72.39,0,0,1,9.894-4.029l1.934,5.962a52.707,52.707,0,0,1-10.442,2.353,68.937,68.937,0,0,1,7.219,7.928L302.8,453.7a105.215,105.215,0,0,1-5.737-9.024,69.476,69.476,0,0,1-5.447,9.024l-4.963-3.577a92.076,92.076,0,0,1,6.961-7.928C289.971,441.52,286.555,440.714,283.3,439.844Z"
                                              transform="translate(-283.3 -427.5)" fill="#ffc700"/>
                                    </g>
                                </g>
                                <g id="Group_384" data-name="Group 384" transform="translate(2.288 0)">
                                    <g id="Group_383" data-name="Group 383">
                                        <path id="Path_111" data-name="Path 111"
                                              d="M310.382,453.166l-.29-.387c-1.515-2.063-3.32-4.867-5.35-8.315a62.353,62.353,0,0,1-5.092,8.315l-.29.387-5.737-4.125.322-.387c2.772-3.416,4.9-5.866,6.349-7.316-3.287-.645-6.446-1.386-9.411-2.192l-.483-.129,2.224-6.9.483.161a76.889,76.889,0,0,1,8.96,3.674c-.484-4.577-.709-7.8-.741-9.572V425.9h7.058v.483c-.065,2.578-.355,5.769-.838,9.54a70.294,70.294,0,0,1,9.153-3.642l.451-.161,2.224,6.865-.451.161a51.944,51.944,0,0,1-9.508,2.224,79.758,79.758,0,0,1,6.478,7.284l.322.387ZM304.71,442.5l.451.741c2.063,3.545,3.9,6.446,5.447,8.573l4.222-3A68.564,68.564,0,0,0,308,441.338l-.838-.709,1.1-.129a49.311,49.311,0,0,0,9.894-2.192l-1.644-5.028a79.538,79.538,0,0,0-9.379,3.835l-.806.419.129-.9c.516-3.9.838-7.187.935-9.862h-5.092c.065,1.966.355,5.286.838,9.894l.1.935-.806-.451a61.381,61.381,0,0,0-9.153-3.867l-1.611,5c3.062.806,6.349,1.547,9.733,2.224l.935.193-.709.645a83.157,83.157,0,0,0-6.575,7.477l4.158,3a71.893,71.893,0,0,0,5.124-8.573Z"
                                              transform="translate(-290.4 -425.9)" fill="#7e39ff"/>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>

                </div>

                <div style="margin-left: 12px">
                    <div style="font-size: 12px;"><?php esc_attr_e('This is a test site', 'digits'); ?></div>
                    <div style="font-size: 16px;font-weight: bold"><?php esc_attr_e('Build with Digits', 'digits'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div>
    <?php
}