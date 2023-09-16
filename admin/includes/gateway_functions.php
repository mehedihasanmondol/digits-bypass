<?php

defined('ABSPATH') || exit;


Digits_Gateway::instance();

class Digits_Gateway
{
    protected static $_instance = null;


    /**
     *  Constructor.
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_filter('digits_group_gateways_list', array($this, 'group_gateways_list'));
        add_filter('digits_sms_gateways', array($this, 'custom_gateway_option'), 100);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function group_gateways_list($gateways)
    {
        $groups = array();

        $groups['starting_group'] = array();

        $default_group = __('Alphabetical Order');
        foreach ($gateways as $key => $gateway) {

            $group = isset($gateway['group']) ? $gateway['group'] : $default_group;

            $groups[$group][$key] = $gateway;
        }


        ksort($groups[$default_group]);
        return $groups;
    }

    public function custom_gateway_option($smsgateways)
    {

        $placeholder = 'to:{to}, message:{message}, sender:{sender_id}';
        $desc = '<i>' . __('Enter Parameters separated by "," and values by ":"') . '</i><br />';
        $desc .= 'To : {to}<br /> Message : {message}<br /> Sender ID : {sender_id}';

        $custom = array(
            'custom_gateway' => array(
                'value' => 900,
                'group' => esc_attr__('Custom Gateway'),
                'label' => esc_attr__('Custom'),
                'inputs' => array(
                    __('SMS Gateway URL') => array('text' => true, 'name' => 'gateway_url', 'placeholder' => 'https://www.example.com/send'),
                    __('HTTP Header') => array('textarea' => true, 'name' => 'http_header', 'rows' => 3, 'optional' => 1, 'desc' => esc_attr__('Headers separated by ","')),
                    __('HTTP Method') => array('select' => true, 'name' => 'http_method', 'options' => array('GET' => 'GET', 'POST' => 'POST')),
                    __('Gateway Parameters') => array('textarea' => true, 'name' => 'gateway_attributes', 'rows' => 6, 'desc' => $desc, 'placeholder' => $placeholder),
                    __('Send as Body Data') => array('select' => true, 'name' => 'send_body_data', 'options' => array('No' => 0, 'Yes' => 1)),
                    __('Encode Message') => array('select' => true, 'name' => 'encode_message', 'options' => array(__('URL Encode') => 1, __('URL Raw Encode') => 3, __('No') => 0, __('Convert To Unicode') => 2)),
                    __('Phone Number') => array('select' => true, 'name' => 'phone_number', 'options' => array(__('with only country code') => 2, __('with + and country code') => 1, __('without country code') => 3)),
                    __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                ),
            ),
        );

        return array_merge($smsgateways, $custom);
    }
}