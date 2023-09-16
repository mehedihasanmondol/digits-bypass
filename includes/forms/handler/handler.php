<?php


use DigitsFormHandler\Handler;

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/process.php';
require_once dirname(__FILE__) . '/functions.php';
require_once dirname(__FILE__) . '/DigitsFormHandler.php';
require_once dirname(__FILE__) . '/UserRegistration.php';
require_once dirname(__FILE__) . '/notice_exception.php';
require_once dirname(__FILE__) . '/DigitsSignUpException.php';
require_once dirname(__FILE__) . '/firebase_exception.php';
require_once dirname(__FILE__) . '/rate_limit_exception.php';
require_once dirname(__FILE__) . '/UserActionHandler.php';
require_once dirname(__FILE__) . '/user.php';
require_once dirname(__FILE__) . '/redirection.php';
require_once dirname(__FILE__) . '/flow.php';

add_action('wp_ajax_nopriv_digits_forms_ajax', 'digits_forms_ajax');
add_action('wp_ajax_digits_forms_ajax', 'digits_forms_ajax');
function digits_forms_ajax()
{
    if (empty($_REQUEST['type'])) {
        return;
    }
    if (is_user_logged_in()) {
        wp_send_json_error(array('reload' => true));
    }
    $csrf = $_REQUEST['digits_form'];

    if (!wp_verify_nonce($csrf, 'digits_login_form') || empty($_REQUEST['digits_form'])) {
        wp_send_json_error(array('message' => __('Error, Please reload the page and try again!', 'digits')));
        die();
    }
    $type = $_REQUEST['type'];

    digits_form_check_disable($type);


    $handler = Handler::instance();
    $handler->setType($type);
    $handler->setData($_REQUEST);
    $handler->process();
}

function digits_form_check_disable($type)
{
    $users_can_register = get_option('dig_enable_registration', 1);
    $digforgotpass = get_option('digforgotpass', 1);
    if ($users_can_register == 0 && $type == 'register') {
        wp_send_json_error(array('message' => __('Registration is disabled!', 'digits')));
        die();
    }

    if ($digforgotpass == 0 && $type == 'forgot') {
        wp_send_json_error(array('message' => __('Forgot Password is disabled!', 'digits')));
        die();
    }
}


function digits_verify_recaptcha()
{
    $recaptcha_secret_key = get_option('digits_recaptcha_secret_key', '');

    $recaptcha_response = $_REQUEST['g-recaptcha-response'];

    $data = array('secret' => $recaptcha_secret_key, 'response' => $recaptcha_response);

    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    $check = json_decode($result);

    if (!empty($check->success)) {
        return true;
    } else {
        return false;
    }
}