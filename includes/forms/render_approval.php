<?php

if (!defined('ABSPATH')) {
    exit;
}


function digits_render_approval_form($details)
{
    $login_from = esc_attr($details['region']);
    $login_via = esc_attr($details['browser']);
    $request_info = __('<span class="digits_desc_light">Are you trying to login to your account from </span>%s<span class="digits_desc_light"> via your </span>%s?', 'digits');
    $request_info = sprintf($request_info, '<span class="digits_desc_highlight">' . $login_from . '</span>', '<span class="digits_desc_highlight">' . $login_via . '</span>');
    ?>
    <form>
        <div class="digits_approval_box">
            <div class="digits_approval_container">
                <div class="digits_approval_type_desc">
                    <?php echo $request_info; ?>
                    <br/><br/>
                    <?php echo esc_attr__('Please take action on the request', 'digits'); ?>
                </div>
                <div class="digits_approval_button_bar">
                    <button class="digits-form_button digits_approval_sbm_btn digits-form_submit-btn digits-green_submit"
                            type="submit" data-action="approve">
            <span class="digits-form_button-text">
                <?php esc_attr_e('Please Approve, it\'s me', 'digits'); ?>
            </span>
                        <span class="digits-form_button_ic"></span>
                    </button>
                    <button class="digits-form_button digits_approval_sbm_btn digits-form_submit-btn digits-red_submit"
                            type="button" data-show="digits_approval_sec_step">
            <span class="digits-form_button-text">
                <?php esc_attr_e('No, it\'s not me', 'digits'); ?>
            </span>
                    </button>
                </div>
            </div>
            <div class="digits_approval_container digits_display_none digits_approval_sec_step">
                <div class="digits_approval_type_desc">
            <span class="digits_desc_light">
                <?php esc_attr_e('Do you want us to block the device from trying again?', 'digits'); ?>
            </span>
                </div>
                <div class="digits_approval_button_bar">
                    <button class="digits-form_button digits_approval_sbm_btn digits-form_submit-btn digits-green_submit"
                            type="submit" data-action="block">
            <span class="digits-form_button-text">
                <?php esc_attr_e('Yes, block the device', 'digits'); ?>
            </span>
                        <span class="digits-form_button_ic"></span>
                    </button>
                    <button class="digits-form_button digits_approval_sbm_btn digits-form_submit-btn digits-red_submit"
                            type="button" data-redirect-home="1"  data-action="deny">
            <span class="digits-form_button-text">
                <?php esc_attr_e('No, don\'t block', 'digits'); ?>
            </span>
                    </button>
                </div>
            </div>
        </div>
        <input name="form_data" value="" type="hidden"/>
        <?php
        wp_nonce_field('digits_email_approval', 'digits_email_approval')
        ?>
    </form>
    <?php
}