<?php

if (!defined('ABSPATH')) {
    exit;
}


function digits_getEmailGateWayArray()
{
    $gateways = array(
        'wp_mail' => array(
            'value' => 2,
            'label' => 'WP Mail',
            'inputs' => array(
                'From Email' => array('text' => true, 'name' => 'from'),
            ),
        ),
        'sendgrid' => array(
            'value' => 3,
            'label' => 'SendGrid',
            'inputs' => array(
                'API Key' => array('text' => true, 'name' => 'api_key'),
                'From Email' => array('text' => true, 'name' => 'from'),
            ),
        ),
        'mailgun' => array(
            'value' => 4,
            'label' => 'Mailgun',
            'inputs' => array(
                'API Key' => array('text' => true, 'name' => 'api_key'),
                'Domain' => array('text' => true, 'name' => 'domain'),
                'From Email' => array('text' => true, 'name' => 'from'),
            ),
        ),
    );

    $gateways = apply_filters('digits_email_gateways', $gateways);
    return $gateways;
}



