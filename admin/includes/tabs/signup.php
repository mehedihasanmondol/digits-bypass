<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_auth_register()
{
    $user_can_register = get_option('dig_enable_registration', 1);

    $mobInUname = get_option("dig_mobilein_uname", 0);

    $defaultuserrole = get_option('defaultuserrole', "customer");

    if (!get_role($defaultuserrole)) {
        $defaultuserrole = 'subscriber';
    }
    $dig_use_strongpass = get_option('dig_use_strongpass', 0);


    $skip_otp_verification = get_option('dig_reg_skip_otp_verification', 0);

    $dig_reg_verify_email = get_option('dig_reg_verify_email', 1);

    $dig_allow_login_without_email_verify = get_option('dig_allow_login_without_email_verify', 1);
    ?>
    <div class="dig_admin_head"><span><?php _e('Signup Settings', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div>
                <table class="form-table">
                    <tr id="enableregistrationrow">
                        <th scope="row"><label class="top-10"><?php _e('Enable Signup', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_enable_registration', $user_can_register); ?>
                            <!--                <p class="dig_ecr_desc"><?php /*_e('This function only works on Digits Login/Signup Modal and Page', 'digits'); */ ?></p>-->
                        </td>
                    </tr>

                    <tr>
                        <th scope="row" style="vertical-align:top;"><label
                                    for="defaultuserrole"><?php _e('Default User Role', 'digits'); ?></label></th>
                        <td>
                            <select name="defaultuserrole" id="defaultuserrole">
                                <?php

                                foreach (wp_roles()->roles as $rkey => $rvalue) {

                                    if ($rkey == $defaultuserrole) {
                                        $sel = 'selected=selected';
                                    } else {
                                        $sel = '';
                                    }
                                    echo '<option value="' . $rkey . '" ' . $sel . '>' . $rvalue['name'] . '</option>';
                                }

                                ?>
                            </select>

                            <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('The default role which will be assigned to new user created.', 'digits'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Username Generation', 'digits'); ?> </label></th>
                        <td>
                            <select name="dig_mobilein_uname">
                                <option value="3" <?php if ($mobInUname == 3) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Email', 'digits'); ?></option>
                                <option value="2" <?php if ($mobInUname == 2) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('Random Numbers', 'digits'); ?></option>
                                <option value="1" <?php if ($mobInUname == 1) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Phone Number (with just country code)', 'digits'); ?></option>
                                <option value="4" <?php if ($mobInUname == 4) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Phone Number (with + and country code)', 'digits'); ?></option>
                                <option value="5" <?php if ($mobInUname == 5) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Phone Number (without country code)', 'digits'); ?></option>

                                <option value="6" <?php if ($mobInUname == 6) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Phone Number (with 0)', 'digits'); ?></option>

                                <option value="0" <?php if ($mobInUname == 0) {
                                    echo 'selected="selected"';
                                } ?>><?php _e('From Name', 'digits'); ?></option>

                            </select>
                        </td>
                    </tr>

                    <tr id="enabledisablestrongpasswordrow">
                        <th scope="row"><label
                                    class="top-10"><?php _e('Enable Strong Password', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_enable_strongpass', $dig_use_strongpass); ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><label
                                    class="top-10"><?php _e('Create Account without OTP verification', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_reg_skip_otp_verification', $skip_otp_verification); ?>
                            <!--                <p class="dig_ecr_desc"><?php /*_e('This function only works on Digits Login/Signup Modal and Page', 'digits'); */ ?></p>-->
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label
                                    class="top-10"><?php _e('Verify Email', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php digits_input_switch('dig_reg_verify_email', $dig_reg_verify_email); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label
                                    class="top-10"><?php _e('Block login for users with unverified email', 'digits'); ?> </label>
                        </th>
                        <td>
                            <?php


                            if (strtolower($dig_allow_login_without_email_verify) == 'on' || $dig_allow_login_without_email_verify == 1) {
                                $dig_allow_login_without_email_verify = 0;
                            }else{
                                $dig_allow_login_without_email_verify = 1;
                            }

                            digits_input_switch('dig_allow_login_without_email_verify', $dig_allow_login_without_email_verify);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <input type="hidden" name="dig_custom_field_data" value="1"/>

            <div class="dig_admin_sec_head dig_admin_sec_head_margin"><span><?php _e('Form Fields', 'digits'); ?></span>
            </div>


            <?php
            $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));


            $dig_sortorder = get_option("dig_sortorder");
            ?>

            <input type="hidden" id="dig_sortorder" name="dig_sortorder"
                   value='<?php echo esc_attr($dig_sortorder); ?>'/>

            <input type="hidden" id="dig_reg_custom_field_data" name="dig_reg_custom_field_data"
                   value='<?php echo esc_attr($reg_custom_fields); ?>'/>
            <table class="form-table dig-reg-fields <?php if (is_rtl()) {
                echo 'dig_rtl';
            } ?>" id="dig_custom_field_table">

                <tbody>
                <?php
                $dig_reg_field_details = digit_get_reg_fields();
                foreach (digit_default_reg_fields() as $reg_field => $values) {

                    $field_value = $dig_reg_field_details[$reg_field];
                    ?>
                    <tr id="dig_cs_<?php echo cust_dig_filter_string($values['id']); ?>">
                        <th scope="row"><label><?php _e($values['name'], "digits"); ?> </label></th>
                        <td class="dg_cs_td">
                            <div class="icon-drag icon-drag-dims dig_cust_field_drag dig_cust_default_fields_drag"></div>
                            <select name="<?php echo $reg_field; ?>"
                                    class="dig_custom_field_sel" <?php if (isset($values['ondis_disable'])) {
                                echo 'data-disable="' . $values['ondis_disable'] . '"';
                            } ?>>
                                <option value="2" <?php if ($field_value == 2) {
                                    echo 'selected';
                                } ?>><?php _e('Required', 'digits'); ?></option>
                                <option value="1" <?php if ($field_value == 1) {
                                    echo 'selected';
                                } ?>><?php _e('Optional', 'digits'); ?></option>
                                <option value="0" <?php if ($field_value == 0) {
                                    echo 'selected';
                                } ?>><?php _e('No', 'digits'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php
                }
                ?>


                <?php

                if (!empty($reg_custom_fields)) {

                    $reg_custom_fields = json_decode($reg_custom_fields, true);

                    $digits_fields = digits_get_all_custom_fields();
                    foreach ($reg_custom_fields as $key => $values) {


                        $label = $values['label'];
                        $field_key = cust_dig_filter_string($values['meta_key']);

                        $type = digits_strtolower($values['type']);
                        if (!isset($digits_fields[$type])) {
                            continue;
                        }
                        $field = $digits_fields[$type];

                        ?>
                        <tr id="dig_cs_<?php echo esc_attr($field_key); ?>"
                            class="dig_field_type_<?php echo digits_strtolower($values['type']); ?>"
                            dig-lab="<?php echo esc_attr($values['meta_key']); ?>">
                            <th scope="row"><label><?php echo $label; ?> </label></th>
                            <td>
                                <div class="dig_custom_field_list">
                                    <span><?php
                                        if (!empty($field['required_label'])) {
                                            echo esc_attr($field['required_label']);
                                        } else {
                                            echo dig_requireCustomToString($values['required']);
                                        }
                                        ?></span>
                                    <div class="dig_icon_customfield">
                                        <div class="icon-shape icon-shape-dims dig_cust_field_delete"></div>
                                        <div class="icon-drag icon-drag-dims dig_cust_field_drag"></div>
                                        <div class="icon-gear icon-gear-dims dig_cust_field_setting"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>

                <tfoot>
                <tr>
                    <th></th>
                    <td>
                        <div id="dig_add_new_reg_field"><?php _e('ADD FIELD', 'digits'); ?></div>
                        <?php do_action("dig_cf_add_new_btn"); ?>
                    </td>
                </tr>
                </tfoot>
            </table>
            <?php

            do_action("after_dig_custom_section", digit_default_reg_fields(), $reg_custom_fields);
            ?>
        </div>

        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $text = __('These settings only apply if you are using our Native Form, 3rd party plugin or theme forms. <br /><br /> These settings <b>do not</b> apply for forms built with Digits Builder addon. ', 'digits');
            digits_settings_show_hint($text);
            ?>

        </div>
    </div>
    <?php
}


add_action('digits_setting_modal', 'digits_form_signup_add_fields_modal');

function digits_form_signup_add_fields_modal()
{
    ?>
    <div class="dig_side_bar">
        <div class="digits_admin_add_field_modal">
            <div class="digits_admin_add_field_modal_wrapper">

                <div class="dig_sb_head"><?php _e('Field Type', 'digits'); ?></div>
                <div class="dig_sb_content">

                    <div class="dig_sb_select_field">
                        <?php
                        $dig_custom_fields = digits_customfieldsTypeList();
                        foreach ($dig_custom_fields as $fieldname => $type) {
                            if (isset($type['hidden']) && $type['hidden'] == 1) {
                                continue;
                            }
                            $fieldname = esc_attr($fieldname);
                            ?>

                            <div class="dig_sb_field_types dig_sb_field_list"
                                 id="dig_cust_list_type_<?php echo $fieldname; ?>" data-val='<?php echo $fieldname; ?>'
                                 data-configure_fields='<?php echo json_encode($type); ?>'>
                                <?php _e($type['name'], 'digits'); ?>
                            </div>

                            <?php

                        }
                        do_action('dig_custom_fields_list');

                        echo '<div class="dig_dsc_cusfield">' . __('WordPress / WooCommerce Fields', 'digits') . '</div>';
                        foreach (digits_presets_custom_fields() as $custom_field) {
                            ?>
                            <div class="dig_sb_field_wp_wc_types dig_sb_field_list"
                                 id="dig_cust_list_type_<?php echo esc_attr($custom_field['type']); ?>"
                                 data-val='<?php echo esc_attr($custom_field['type']); ?>'
                                 data-values='<?php echo json_encode($custom_field['values']); ?>'
                                 data-configure_fields='<?php echo json_encode($dig_custom_fields[$custom_field['type']]); ?>'>
                                <?php _e($custom_field['values']['label'], 'digits'); ?>
                            </div>
                            <?php
                            do_action('dig_custom_preset_fields_list');
                        }
                        ?><br/>
                    </div>

                    <div class="dig_fields_options">
                        <div class="dig_fields_options_main">
                            <input type="hidden" data-type="" id="dig_custom_field_data_type"/>
                            <div class="dig_sb_field" data-req="1" id="dig_field_label">
                                <div class="dig_sb_field_label">
                                    <label for="custom_field_label"><?php _e('Label', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <input type="text" id="custom_field_label" name="label"/>
                                </div>

                                <div class="dig_sb_field_tac dig_sb_extr_fields dig_sb_field_tac_desc">
                                    <?php _e('Enclose the word(s) between [t] and [/t] for terms and condition and [p] and [/t] for privacy policy.', 'digits'); ?>
                                    <br/><br/>
                                    <?php _e('For example "I Agree [t]Terms and Conditions[/t] & [p]Privacy Policy[/t]"', 'digits'); ?>
                                </div>
                                <?php do_action('dig_custom_fields_label_desc'); ?>
                            </div>

                            <div class="dig_sb_field" id="dig_field_required" data-req="1">
                                <div class="dig_sb_field_label">
                                    <label><?php _e('Required Field', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <select name="required">
                                        <option value="1"><?php _e('Yes', 'digits'); ?></option>
                                        <option value="0"><?php _e('No', 'digits'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="dig_sb_field" id="dig_field_meta_key" data-req="1">
                                <div class="dig_sb_field_label">
                                    <label for="custom_field_meta_key"><?php _e('Meta Key', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <input type="text" id="custom_field_meta_key" name="meta_key"/>
                                </div>
                            </div>
                            <div class="dig_sb_field" id="dig_field_custom_class" data-req="0">
                                <div class="dig_sb_field_label">
                                    <label for="custom_field_class"><?php _e('Custom Class', 'digits'); ?></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <input type="text" id="custom_field_class" name="custom_class"/>
                                </div>
                            </div>

                            <div class="dig_sb_field" id="dig_field_options" data-req="1" data-list="1">
                                <div class="dig_sb_field_label">
                                    <label><?php _e('Options', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <ul id="dig_field_val_list"></ul>

                                <div class="dig_sb_field_add_opt">
                                    <input type="text" class="dig_sb_field_list_input"
                                           placeholder="<?php _e('Add a Option', 'digits'); ?>"/>
                                </div>
                            </div>


                            <div class="dig_sb_field dig_sb_field_tac dig_sb_extr_fields" data-req="1">
                                <div class="dig_sb_field_label">
                                    <label for="dig_csf_tac_link"><?php _e('Terms & Conditions Link', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <input type="text" id="dig_csf_tac_link" name="tac_link"/>
                                </div>
                            </div>

                            <div class="dig_sb_field dig_sb_field_tac dig_sb_extr_fields" data-req="0">
                                <div class="dig_sb_field_label">
                                    <label for="dig_csf_tac_privacy_link"><?php _e('Privacy Link', 'digits'); ?></label>
                                </div>
                                <div class="dig_sb_field_input">
                                    <input type="text" id="dig_csf_tac_privacy_link" name="tac_privacy_link"/>
                                </div>
                            </div>


                            <div class="dig_sb_field dig_sb_extr_fields dig_sb_field_user_role" id="dig_field_roles"
                                 data-req="1" data-list="2">
                                <div class="dig_sb_field_label">
                                    <label><?php _e('User Roles', 'digits'); ?><span
                                                class="dig_sb_required">*</span></label>
                                </div>
                                <ul>


                                    <?php
                                    global $wp_roles;
                                    foreach ($wp_roles->roles as $key => $value):
                                        $key = esc_attr($key);
                                        digits_input_checkbox('', $key, [], $value['name']);
                                    endforeach; ?>

                                </ul>
                            </div>


                            <?php do_action('dig_custom_fields_options'); ?>


                        </div>


                        <div id="dig_cus_field_footer">
                            <div class="dig_admin_blue dig_cus_field_done"><?php _e('Save', 'digits'); ?></div>
                            <div class="dig_admin_cancel"><?php _e('Back', 'digits'); ?></div>
                        </div>

                    </div>


                </div>

            </div>
        </div>
    </div>
    <?php
}