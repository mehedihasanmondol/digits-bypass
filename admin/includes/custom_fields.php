<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_presets_custom_fields()
{
    return array(
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Last Name', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'last_name'
            )
        ),
        array(
            'type' => 'user_role',
            'values' => array(
                'label' => __('User Role', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'user_role'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Display Name', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'display_name'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Company', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_company'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Address Line 1', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_address_1'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Address Line 2', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_address_2'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('City', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_city'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('State', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_state'
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Country', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_country',
            )
        ),
        array(
            'type' => 'text',
            'values' => array(
                'label' => __('Postcode / ZIP', 'digits'),
                'required' => 1,
                'custom_class' => '',
                'meta_key' => 'billing_postcode'
            )
        ),


    );
}

function digits_customfieldsTypeList()
{

    return array(
        'text' => array(
            'name' => __('Text', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'text'
        ),
        'textarea' => array(
            'name' => __('TextArea', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'textarea'
        ),
        'number' => array(
            'name' => __('Number', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'number'
        ),
        'dropdown' => array(
            'name' => __('DropDown', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 1,
            'slug' => 'dropdown'
        ),
        'checkbox' => array(
            'name' => __('CheckBox', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 1,
            'slug' => 'checkbox'
        ),
        'radio' => array(
            'name' => __('Radio', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 1,
            'slug' => 'radio'
        ),
        'tac' => array(
            'name' => __('Terms & Conditions', 'digits'),
            'force_required' => 1,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'tac',
            'pref_label' => 'I Agree [t]Terms and Conditions[/t] & [p]Privacy Policy[/t]'
        ),
        'break' => array(
            'name' => __('Form Break', 'digits'),
            'required_label' => __('Form Break', 'digits'),
            'force_required' => 1,
            'meta_key' => 0,
            'options' => 0,
            'slug' => 'break'
        ),
        'form_step_title' => array(
            'name' => __('Step Title', 'digits'),
            'required_label' => __('Step Title', 'digits'),
            'force_required' => 1,
            'meta_key' => 0,
            'options' => 0,
            'slug' => 'form_step_title'
        ),
        'captcha' => array(
            'name' => __('Captcha', 'digits'),
            'force_required' => 1,
            'meta_key' => 0,
            'options' => 0,
            'slug' => 'captcha'
        ),
        'recaptcha' => array(
            'name' => __('ReCaptcha', 'digits'),
            'force_required' => 1,
            'meta_key' => 0,
            'options' => 0,
            'slug' => 'recaptcha'
        ),
        'user_role' => array(
            'name' => __('User Role', 'digits'),
            'force_required' => 1,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'user_role',
            'hidden' => 1,
            'user_role' => 1
        ),
        'date' => array(
            'name' => __('Date', 'digits'),
            'force_required' => 0,
            'meta_key' => 1,
            'options' => 0,
            'slug' => 'date'
        ),
    );

}

function digit_customfields()
{

}

function digits_strtolower($str)
{
    return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
}