<?php

if (!defined('ABSPATH')) {
    exit;
}

function digit_shortcodes($showbuttons = true)
{

    ?>
    <div class="dig_admin_head"><span><?php esc_attr_e('Shortcodes', 'digits'); ?></span></div>

 <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
    <?php


    $shortcodes = array(
        array('heading' => __("Digits Native Form", "digits")),
        array('label' => __("Login/Signup Form", "digits"), 'value' => 'df-form'),
        array('label' => __("Login Form", "digits"), 'value' => 'df-form-login'),
        array('label' => __("Signup Form", "digits"), 'value' => 'df-form-signup'),
        array('label' => __("Forgot Password Form", "digits"), 'value' => 'df-form-forgot-password'),
        array('label' => __("Edit Phone Number", "digits"), 'value' => 'df-edit-phone'),

        array('heading' => __("Account Security", "digits")),

        array('label' => __("Account Security Dashboard", "digits"), 'value' => 'df-account-manage'),

        array('label' => __("2FA App (Google Auth, Authy, etc) Setup", "digits"), 'value' => 'df-totp-setup'),
        array('label' => __("Biometrics & Security Key Devices Setup", "digits"), 'value' => 'df-biometrics-setup'),
        array('label' => __("2-Factor Authentication Setup", "digits"), 'value' => 'df-2fa-setup'),
        array('label' => __("3-Factor Authentication Setup", "digits"), 'value' => 'df-3fa-setup'),

        array('heading' => __("Menu Items", "digits")),

        array('label' => __("Login/Signup Page", "digits"), 'value' => 'dm-page'),
        array('label' => __("Login/Signup Modal", "digits"), 'value' => 'dm-modal'),
        array('label' => __("Login Page", "digits"), 'value' => 'dm-login-page'),
        array('label' => __("Login Modal", "digits"), 'value' => 'dm-login-modal'),

        array('label' => __("Sign Up Page", "digits"), 'value' => 'dm-signup-page'),
        array('label' => __("Sign Up Modal", "digits"), 'value' => 'dm-signup-modal'),

        array('label' => __("Forgot Password Page", "digits"), 'value' => 'dm-forgot-password-page'),
        array('label' => __("Forgot Password Modal", "digits"), 'value' => 'dm-forgot-password-modal'),

        array('label' => __("Logout", "digits"), 'value' => 'dm-logout'),

    );

    $i = 0;
    ?>
    <?php
    foreach ($shortcodes as $key => $shortcode) {
        $i++;
        if (isset($shortcode['heading'])) {
            if ($i > 1) {
                echo '</table></div>';
            }
            ?>
                <div class="dig_admin_section">
            <div class="dig_admin_sec_head"><span><?php echo $shortcode['heading']; ?></span></div>
            <?php
            echo '<table class="form-table">';
        } else {
            $id = 'digits_' . $shortcode['value'] . '_short';
            ?>
            <tr>
                <th scope="row"><label
                            for="<?php echo esc_attr($id); ?>"><?php echo $shortcode['label']; ?> </label>
                </th>
                <td>
                    <div class="digits_shortcode_tbs">
                        <input type="text" id="<?php echo esc_attr($id); ?>"
                               value="[<?php echo esc_attr($shortcode['value']) ?>]"
                               readonly/>
                        <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2326263A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-copy'%3E%3Crect x='9' y='9' width='13' height='13' rx='2' ry='2'%3E%3C/rect%3E%3Cpath d='M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1'%3E%3C/path%3E%3C/svg%3E">
                    </div>
                </td>
            </tr>
            <?php
        }
    }
    echo '</table></div>';
    ?>

</div></div>
    <?php
    if ($showbuttons) {
        ?>
        <p class="digits-setup-action step">
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=ready'); ?>"
               class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></a>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=apisettings'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
        <?php
    }
}

function digit_shortcodes_translations()
{

    $dig_replace_otp_word = get_option('dig_replace_otp_word', '');
    ?>

    <div class="dig_admin_head"><span><?php _e('Translations', 'digits'); ?></span></div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label
                                    for="dig_replace_otp_word"><?php esc_attr_e('Replace "OTP" everywhere', "digits"); ?> </label>
                        </th>
                        <td>
                            <input type="text" id="dig_replace_otp_word" name="dig_replace_otp_word"
                                   class="regular-text"
                                   value="<?php echo $dig_replace_otp_word; ?>"/>
                        </td>
                    </tr>
                </table>
            </div>
            <br /><br />

            <div class="dig_admin_sec_head">
                <span><?php _e('Menu items', 'digits'); ?></span>
            </div>
            <?php

            $diglogintrans = get_option("diglogintrans", __("Login / Register", "digits"));
            $digregistertrans = get_option("digregistertrans", __("Register", "digits"));
            $digforgottrans = get_option("digforgottrans", __("Forgot your Password?", "digits"));
            $digmyaccounttrans = get_option("digmyaccounttrans", __("My Account", "digits"));
            $diglogouttrans = get_option("diglogouttrans", __("Logout", "digits"));

            $digonlylogintrans = get_option("digonlylogintrans", __("Login", "digits"));
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="diglogintrans"><?php _e("Login / Register", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="diglogintrans" name="diglogintrans" class="regular-text"
                               value="<?php echo $diglogintrans; ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="digonlylogintrans"><?php _e("Login", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="digonlylogintrans" name="digonlylogintrans" class="regular-text"
                               value="<?php echo $digonlylogintrans; ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="digregistertrans"><?php _e("Register", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="digregistertrans" name="digregistertrans" class="regular-text"
                               value="<?php echo $digregistertrans; ?>" required/>
                    </td>
                </tr>


                <tr>
                    <th scope="row"><label for="digforgottrans"><?php _e("Forgot", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="digforgottrans" name="digforgottrans" class="regular-text"
                               value="<?php echo $digforgottrans; ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="digmyaccounttrans"><?php _e("My Account", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="digmyaccounttrans" name="digmyaccounttrans" class="regular-text"
                               value="<?php echo $digmyaccounttrans; ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="diglogouttrans"><?php _e("Logout", "digits"); ?> </label></th>
                    <td>
                        <input type="text" id="diglogouttrans" name="diglogouttrans" class="regular-text"
                               value="<?php echo $diglogouttrans; ?>" required/>
                    </td>
                </tr>


            </table>
        </div>
        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $link = 'https://help.unitedover.com/digits/kb/plugin-text-translation/';

            $text = __('Translation of whole plugin can be done through POT file present in the plugin languages folder. You\'ll need to upload .MO and .PO files to the languages folder of this plugin. The easiest way to translate is to use <i>Loco Translate</i> WordPress plugin.', 'digits');
            $text .= '<a href="' . esc_attr($link) . '" target="_blank"> ' . __('Need More Help?', 'digits') . '</a>';
            digits_settings_show_hint($text);

            ?>

        </div>
    </div>
    <?php
}