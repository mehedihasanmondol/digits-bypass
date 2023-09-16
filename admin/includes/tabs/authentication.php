<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_config_otp()
{
    $dig_mob_otp_resend_time = get_option('dig_mob_otp_resend_time', 30);

    $dig_mob_otp_resend_time_two = get_option('dig_mob_otp_resend_time_2', 60);
    $dig_mob_otp_resend_time_three = get_option('dig_mob_otp_resend_time_3', 120);

    ?>
    <div class="dig_admin_head"><span><?php _e('OTP Settings', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table">

                <tr>
                    <th scope="row" style="vertical-align:top;"><label
                                for="dig_mob_otp_resend_time"><?php _e('OTP Resend Time', 'digits'); ?></label>
                    </th>
                    <td>
                        <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                            <input dig-min="51" type="number" name="dig_mob_otp_resend_time"
                                   value="<?php echo $dig_mob_otp_resend_time; ?>"
                                   step="1"
                                   placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                            <span style="left:51px;"><?php _e('seconds wait time after 1<sup>st</sup> attempt', 'digits'); ?></span>
                        </div>

                        <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                            <input dig-min="51" type="number" name="dig_mob_otp_resend_time_2"
                                   value="<?php echo $dig_mob_otp_resend_time_two; ?>"
                                   step="1"
                                   placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                            <span style="left:51px;"><?php _e('seconds wait time after 2<sup>nd</sup> attempt', 'digits'); ?></span>
                        </div>
                        <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                            <input dig-min="51" type="number" name="dig_mob_otp_resend_time_3"
                                   value="<?php echo $dig_mob_otp_resend_time_three; ?>"
                                   step="1"
                                   placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                            <span style="left:51px;"><?php _e('seconds wait time after 3<sup>rd</sup> attempt', 'digits'); ?></span>
                        </div>
                    </td>
                </tr>


            </table>
        </div>
    </div>
    <?php
}


function digits_settings_api_email()
{
    $email_gateway = get_option('digit_email_gateway', 2);
    ?>
    <div class="dig_admin_head"><span><?php _e('Email Gateway', 'digits'); ?></span></div>
    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div class="dig_email_api_box digits_gateway_api_box <?php if ($email_gateway == -1) echo 'digits_gateway-disabled'; ?>">
                <table class="form-table digits_default_gateway_details">
                    <?php digit_select_gateway('name="digit_email_gateway" id="digit_email_gateway"', $email_gateway,
                        digits_getEmailGateWayArray(), 'email'); ?>

                    <?php
                    dig_show_gateway_api_fields(digits_getEmailGateWayArray(), $email_gateway, 'email');
                    ?>
                </table>

                <?php
                digits_otp_resend_time('email');
                digit_test_api_box('email');
                ?>
            </div>

        </div>
    </div>
    <?php
}

function digits_settings_api_whatsapp()
{
    $whatsapp_gateway = get_option('digit_whatsapp_gateway', -1);

    ?>
    <div class="dig_admin_head"><span><?php _e('WhatsApp Gateway', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div class="dig_whatsapp_api_box digits_gateway_api_box <?php if ($whatsapp_gateway == -1) echo 'digits_gateway-disabled'; ?>">
                <table class="form-table digits_default_gateway_details">
                    <?php digit_select_gateway('name="digit_whatsapp_gateway" id="digit_whatsapp_gateway"', $whatsapp_gateway,
                        getWhatsAppGateWayArray(), 'whatsapp'); ?>

                    <?php
                    dig_show_gateway_api_fields(getWhatsAppGateWayArray(), $whatsapp_gateway, 'whatsapp');
                    ?>
                    <?php
                    $dig_messagetemplate = get_option("dig_messagetemplate", digits_default_otp_template());
                    $whatsapp_messagetemplate = get_option('dig_whatsapp_messagetemplate', $dig_messagetemplate);
                    ?>
                    <tr class="digits_whatsapp_template digits_gateway_template">
                        <th scope="row" style="vertical-align:top;"><label
                                    for="dig_whatsapp_messagetemplate"><?php _e('WhatsApp Message Template', 'digits'); ?></label>
                        </th>
                        <td>
                    <textarea name="dig_whatsapp_messagetemplate" placeholder="Message Template" class="dig_inp_wid3"
                              required><?php echo $whatsapp_messagetemplate; ?></textarea>
                            <p class="dig_ecr_desc">
                                <?php _e('Site Name', 'digits'); ?> - {NAME}<br/>
                                <?php _e('Domain', 'digits'); ?> - {DOMAIN}<br/>
                                <?php _e('OTP', 'digits'); ?> - {OTP}
                            </p>

                        </td>
                    </tr>
                </table>
                <?php
                digits_otp_resend_time('whatsapp');
                digit_test_api_box();
                ?>
            </div>

        </div>
    </div>
    <?php
}

function digits_otp_resend_time($type)
{
    $key = "dig_{$type}_otp_resend_time";
    $dig_mob_otp_resend_time = get_option($key, 30);

    $dig_mob_otp_resend_time_two = get_option($key . '_2', 60);
    $dig_mob_otp_resend_time_three = get_option($key . '_3', 120);

    ?>
    <table class="form-table otp_table">

        <tr>
            <th scope="row" style="vertical-align:top;"><label><?php _e('OTP Resend Time', 'digits'); ?></label>
            </th>
            <td>
                <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                    <input dig-min="51" type="number" name="<?php echo $key; ?>"
                           value="<?php echo $dig_mob_otp_resend_time; ?>"
                           step="1"
                           placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                    <span style="left:51px;"><?php _e('seconds wait time after 1<sup>st</sup> attempt', 'digits'); ?></span>
                </div>

                <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                    <input dig-min="51" type="number" name="<?php echo $key; ?>_2"
                           value="<?php echo $dig_mob_otp_resend_time_two; ?>"
                           step="1"
                           placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                    <span style="left:51px;"><?php _e('seconds wait time after 2<sup>nd</sup> attempt', 'digits'); ?></span>
                </div>
                <div class="dig_gs_nmb_ovr_spn dig_admin_resend_row">
                    <input dig-min="51" type="number" name="<?php echo $key; ?>_3"
                           value="<?php echo $dig_mob_otp_resend_time_three; ?>"
                           step="1"
                           placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                    <span style="left:51px;"><?php _e('seconds wait time after 3<sup>rd</sup> attempt', 'digits'); ?></span>
                </div>
            </td>
        </tr>


    </table>
    <?php
}


function digits_settings_webauthn()
{
    $digits_enable_security_devices = get_option('digits_enable_security_devices', 1);
    $digits_allow_multiple_device = get_option('digits_allow_multiple_device', 1);

    ?>
    <div class="dig_admin_head"><span><?php _e('Biometrics / Security Keys', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label class="top-10" for="digits_enable_security_devices">
                            <?php _e('Enable Biometrics / Security Keys', 'digits'); ?>
                        </label>
                    </th>
                    <td>
                        <?php digits_input_switch('digits_enable_security_devices', $digits_enable_security_devices); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label class="top-10" for="digits_allow_multiple_device">
                            <?php _e('Allow users to have multiple devices', 'digits'); ?>
                        </label>
                    </th>
                    <td>
                        <?php digits_input_switch('digits_allow_multiple_device', $digits_allow_multiple_device); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $text = __('Security key is the most secure method for authentication. This option lets users use their hardware security keys like Yubikey or their device\'s biometrics to authenticate.', 'digits');
            digits_settings_show_hint($text);
            ?>

        </div>
    </div>
    <?php
}

