<?php

if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . 'block/block.php';

add_shortcode('df-form', 'df_digits_form');
add_shortcode('df-form-login', 'df_digits_form_login');
add_shortcode('df-form-signup', 'df_digits_form_signup');
add_shortcode('df-form-forgot-password', 'df_digits_form_forgot_password');

function df_digits_form()
{
    $values = array('render_form' => 1, 'type' => 'login-register');
    return df_digits_form_render($values);
}

function df_digits_form_login()
{
    $values = array('render_form' => 4, 'type' => 'login');
    return df_digits_form_render($values);
}

function df_digits_form_signup()
{
    $values = array('render_form' => 2, 'type' => 'register');
    return df_digits_form_render($values);
}

function df_digits_form_forgot_password()
{
    $values = array('render_form' => 3, 'type' => 'forgot');
    return df_digits_form_render($values);
}

function df_digits_form_render($values)
{
    if (is_user_logged_in()) {
        return '';
    }

    ob_start();
    $values['login_redirect'] = -2;
    $values['redirect_to'] = -2;
    _df_digits_form_render($values);
    $data = ob_get_contents();
    ob_end_clean();

    return $data;
}


function digits_new_ui_form($details)
{
    $attrs = '';

    global $post;
    if (!empty($details['id'])) {
        $attrs = 'id="digits_protected_' . esc_attr($details['id']) . '"';
    } else {
        if (!empty($post)) {
            $attrs = 'id="digits_protected_' . $post->ID . '"';
        }
    }

    $style = digits_new_form_create_style();
    $style['no_logo'] = true;
    $details['style'] = $style;
    ?>
    <div class="digits_ui" <?php echo $attrs; ?>>
        <div class="digits_embed-form">
            <?php

            digits_render_new_form($details);
            ?>
        </div>
    </div>
    <?php
}

function _df_digits_form_render($values)
{
    $digits_use_new_form_style = digits_use_new_form_style();
    if ($digits_use_new_form_style) {
        digits_new_ui_form($values);
        return;
    }

    $color = get_option('digit_color');
    $page_type = 1;

    if (isset($color['type'])) {
        $page_type = $color['type'];
    }
    ?>
    <div class="dig_lrf_box digits_modal_box dig-elem dig_pgmdl_2 dig_show_label digits_form_shortcode_render">
        <div class="dig_form">
            <?php
            if ($page_type == 2) {
                dig_verify_otp_box();
            }
            $dig_cust_forms = apply_filters('dig_hide_forms', 0);
            if ($dig_cust_forms === 0) {
                digits_forms($values);
            } else {
                do_action('digits_custom_form');
            }
            ?>
        </div>

    </div>
    <?php
}