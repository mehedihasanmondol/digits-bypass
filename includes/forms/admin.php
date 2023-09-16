<?php

if (!defined('ABSPATH')) {
    exit;
}

function update_digits_design_settings()
{
    $data = array('dig_new_forms');
    foreach ($data as $setting) {
        if (isset($_REQUEST[$setting])) {
            $value = $_REQUEST[$setting];
            update_option($setting, $value);
        }
    }

    $options = digits_admin_email_style();
    $email_style = array();
    foreach ($options as $option => $label) {
        $value = 'digits_form_email_' . $option;
        $email_style[$option] = $_REQUEST[$value];
    }
    update_option('digits_form_email_style', $email_style);


    $options = digits_admin_theme_options();
    $theme_value = digits_theme_values();

    $themes = array('light', 'dark');
    foreach ($themes as $theme) {
        foreach ($options as $option => $label) {
            $value = 'theme_' . $theme . '_' . $option;
            $theme_value[$theme][$option] = $_REQUEST[$value];
        }
    }

    update_option('digits_form_theme_style', $theme_value);

    update_option('dig_form_theme', $_REQUEST['dig_form_theme']);
}

add_action('digits_save_settings_data', 'update_digits_design_settings');
function digit_customize($wiz)
{
    $dig_enable_new_design = get_option('dig_new_forms', 1);


    $theme = get_option('dig_form_theme', 'automatic');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label class="top-10"><?php _e('Enable New Design', 'digits'); ?> </label></th>
            <td>
                <?php digits_input_switch('dig_new_forms', $dig_enable_new_design); ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="form_theme"><?php _e('Theme', 'digits'); ?> </label>
            </th>
            <td>
                <select name="dig_form_theme">
                    <?php
                    $available_themes = array('automatic', 'light', 'dark');
                    foreach ($available_themes as $available_theme) {
                        ?>
                        <option
                            value="<?php echo $available_theme; ?>" <?php if ($available_theme == $theme) echo 'selected'; ?>>
                            <?php echo ucfirst($available_theme); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>

    </table>

    <?php
    $theme_value = digits_theme_values();
    digits_admin_theme_style(__('Light', 'digits'), 'theme_light_', $theme_value['light'], digits_admin_theme_options());

    digits_admin_theme_style(__('Dark', 'digits'), 'theme_dark_', $theme_value['dark'], digits_admin_theme_options());


    $digits_admin_email_style = get_option('digits_form_email_style', array());
    digits_admin_theme_style(__('Email', 'digits'), 'digits_form_email_', $digits_admin_email_style, digits_admin_email_style());
}

function digits_admin_email_style()
{
    return array(
        'logo' => array('label' => __('Logo', 'digits'), 'type' => 'image'),
    );
}

function digits_admin_theme_options()
{
    return array(
        'logo' => array('label' => __('Logo', 'digits'), 'type' => 'image'),
        'primary_color' => array('label' => __('Primary Color', 'digits'), 'type' => 'color'),
        'accent_color' => array('label' => __('Accent Color', 'digits'), 'type' => 'color'),
        'title_color' => array('label' => __('Title Color', 'digits'), 'type' => 'color'),
        'field_bg_color' => array('label' => __('Field Background Color', 'digits'), 'type' => 'color'),
        'form_bg_color' => array('label' => __('Background Color', 'digits'), 'type' => 'color'),
        'button_text_color' => array('label' => __('Button Text Color', 'digits'), 'type' => 'color'),
    );
}

function digits_admin_theme_style($heading, $prefix, $values, $options)
{

    ?>
    <div class="dig_admin_sec_head dig_admin_sec_head_margin"><span><?php echo $heading; ?></span></div>
    <table class="form-table">
        <?php
        foreach ($options as $option => $details) {
            $label = $details['label'];
            $key = $prefix . $option;
            $value = $values[$option] ?? '';
            ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo $key; ?>"><?php echo $label; ?></label>
                </th>
                <td>
                    <?php
                    if ($details['type'] == 'image') {
                        ?>
                        <?php
                        $remstyle = "";
                        if (empty($value)) {
                            $imagechoose = __("Select", 'digits');
                            $remstyle = 'style="display:none;"';
                        } else {
                            $imagechoose = __("Remove", 'digits');
                        }
                        $wid = "";
                        if (is_numeric($value)) {
                            $wid = wp_get_attachment_url($value);
                        }
                        ?>
                        <div class='image-preview-wrapper'>
                            <img class='image-preview_left_modal' src='<?php if (is_numeric($value)) {
                                echo $wid;
                            } else {
                                echo $value;
                            } ?>'
                                 style="max-height:100px;">
                        </div>

                        <input type="text" name="<?php echo $key; ?>"
                               value='<?php if (is_numeric($value)) {
                                   if ($wid) {
                                       echo $wid;
                                   }
                               } else {
                                   echo $value;
                               } ?>' placeholder="<?php _e("URL", "digits"); ?>"
                               class="image_attachment_id_left_modal dig_url_img"/>

                        <button type="button" class="dig_change_image button dig_img_chn_btn dig_imsr"
                        ><?php echo $imagechoose; ?></button>
                        <?php
                    } else {
                        ?>
                        <input name="<?php echo $key; ?>" type="text" class="bg_color" value="<?php echo $value; ?>"
                               autocomplete="off"
                               required data-alpha="true"/>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <?php
}

function digits_theme_values()
{
    $default_light = array(
        'logo' => '',
        'primary_color' => '#1C2434',
        'title_color' => '#2D333D',
        'field_bg_color' => '#F8F8F8',
        'accent_color' => '#9CF5FF',
        'form_bg_color' => '#FFFFFF',
        'button_text_color' => '#FFFFFF',
    );

    $default_dark = array(
        'logo' => '',
        'primary_color' => '#FFFFFF',
        'title_color' => '#FFFFFF',
        'field_bg_color' => 'rgba(248, 248, 248, 0.06)',
        'accent_color' => '#9CF5FF',
        'form_bg_color' => '#1C2434',
        'button_text_color' => '#1C2434',
    );
    $digits_theme = get_option('digits_form_theme_style');
    if (!empty($digits_theme)) {
        return $digits_theme;
    }
    return array('light' => $default_light, 'dark' => $default_dark);
}