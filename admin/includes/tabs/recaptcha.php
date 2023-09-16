<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_recaptcha()
{

    $recaptcha_site_key = get_option('digits_recaptcha_site_key', '');
    $recaptcha_secret_key = get_option('digits_recaptcha_secret_key', '');
    $recaptcha_type = get_option('digits_recaptcha_type', 'v3');
    ?>
    <div class="dig_admin_head">
        <span><?php _e('reCAPTCHA', 'digits'); ?></span>
    </div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <div class="dig_admin_section">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_site_key"><?php _e("Site Key", "digits"); ?></label>
                        </th>
                        <td>
                            <input type="text" id="recaptcha_site_key" name="digits_recaptcha_site_key" class="regular-text"
                                   value="<?php echo esc_attr($recaptcha_site_key); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_secret_key"><?php _e("Secret Key", "digits"); ?></label>
                        </th>
                        <td>
                            <input type="text" id="recaptcha_secret_key" name="digits_recaptcha_secret_key"
                                   class="regular-text"
                                   value="<?php echo esc_attr($recaptcha_secret_key); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_type"><?php _e("reCAPTCHA Type", "digits"); ?></label>
                        </th>
                        <td>

                            <select name="digits_recaptcha_type" id="recaptcha_type">
                                <option value="v3" <?php if ($recaptcha_type == 'v3') echo 'selected'; ?>>
                                    <?php esc_attr_e('v3', 'digits'); ?>
                                </option>
                                <option value="checkbox" <?php if ($recaptcha_type == 'checkbox') echo 'selected'; ?>>
                                    <?php esc_attr_e('Checkbox (v2)', 'digits'); ?>
                                </option>
                                <option value="invisible" <?php if ($recaptcha_type == 'invisible') echo 'selected'; ?>>
                                    <?php esc_attr_e('Invisible (v2)', 'digits'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php
}
