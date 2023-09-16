<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'wc_checkout.php';

function digits_wc_merge_billing_phone()
{
    return true;
}

function digits_wc_update_wc_checkout_labels($fields)
{

    if (!digits_wc_merge_billing_phone()) return $fields;
    $fields['billing']['billing_email']['label'] = __("Email", "digits");
    $fields['billing']['billing_phone']['label'] = __("Mobile Number", "digits");

    unset($fields['account']['mobile/email']);
    return $fields;
}

add_filter('woocommerce_checkout_fields', 'digits_wc_update_wc_checkout_labels', 10);


add_action('wp_head', 'digits_wc_merge_hide_field');
function digits_wc_merge_hide_field()
{
    if (function_exists('is_checkout')) {
        if (is_checkout()) {
            if (!digits_wc_merge_billing_phone()) return;
            ?>
            <style>#billing_phone {
                    display: none;
                }</style><?php
        }
    }
}

add_filter('woocommerce_billing_fields', 'digits_wc_merge_remove_billing_phone_field', 100, 1);
function digits_wc_merge_remove_billing_phone_field($fields)
{
    $phone = get_user_meta(get_current_user_id(), 'digits_phone_no', true);
    if (!empty($phone)) {
        $country_code = get_user_meta(get_current_user_id(), 'digt_countrycode', true);
        $attr = ['countryCode' => $country_code];
        $fields['billing_phone']['custom_attributes'] = $attr;
        $fields['billing_phone']['default'] = $phone;
    }


    return $fields;
}

function digits_wc_merge_update_checkout_billing_field($value)
{
    $dig_sync_acc_bill_fields = get_option('dig_sync_acc_bill_phone', 0);
    if ($dig_sync_acc_bill_fields == 1) {
        if (is_checkout()) {
            if (is_user_logged_in()) {
                $phone = get_user_meta(get_current_user_id(), 'digits_phone_no', true);
                return $phone;
            }
        }
    }
    return $value;
}

add_filter('woocommerce_process_checkout_field_billing_phone', 'digits_wc_merge_update_checkout_billing_field');

add_filter('woocommerce_process_myaccount_field_billing_phone', 'digits_wc_merge_return_billing_phone', 100);
function digits_wc_merge_return_billing_phone($value)
{
    $dig_sync_acc_bill_fields = get_option('dig_sync_acc_bill_phone', 0);
    if ($dig_sync_acc_bill_fields == 1) {
        $user_id = get_current_user_id();
        $phone = get_user_meta($user_id, 'digits_phone_no', true);
        if (!empty($phone)) {
            update_user_meta($user_id, 'billing_phone', $phone);
        }
        return $phone;
    }
    return $value;
}

add_action('wc_digits_account_updated', 'digits_wc_merge_account_updated');
function digits_wc_merge_account_updated($user_id)
{
    $dig_sync_acc_bill_fields = get_option('dig_sync_acc_bill_phone', 0);
    if ($dig_sync_acc_bill_fields == 1) {
        $phone = get_user_meta($user_id, 'digits_phone_no', true);
        if (!empty($phone)) {
            update_user_meta($user_id, 'billing_phone', $phone);
        }
    }
}

