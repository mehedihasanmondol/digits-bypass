<?php


if (!defined('ABSPATH')) {
    exit;
}


function get_digits_otp_immediately_methods()
{
    return get_option('dig_send_otp_together', array());
}


function digits_is_forgot_password_enabled()
{
    return get_option('digforgotpass', 1) == 1;
}

function digits_ajax_check_login_status()
{
    $response = array();
    $response['logged_in'] = is_user_logged_in();
    wp_send_json($response);
}

add_action('wp_ajax_digits_check_login_status', 'digits_ajax_check_login_status');
add_action('wp_ajax_nopriv_digits_check_login_status', 'digits_ajax_check_login_status');