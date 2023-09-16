<?php

namespace SMSGateway;


class Bandwidth {
    // docs at: https://dev.bandwidth.com
    // supports bulk with fixed message
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $api_token = $gateway_fields['api_token'];
        $api_secret = $gateway_fields['api_secret'];
        $application_id = $gateway_fields['application_id'];
        $account_id = $gateway_fields['account_id'];
        $sender = $gateway_fields['sender'];

        return self::process_sms(
            $api_token,
            $api_secret,
            $application_id,
            $account_id,
            $sender,
            $mobile,
            $message,
            $test_call
        );
    }

    public static function process_sms(
        $api_token,
        $api_secret,
        $application_id,
        $account_id,
        $sender,
        $mobile,
        $message,
        $test_call
    ) {
        $curl = curl_init();

        $data = array(
            'to' => [$mobile],
            'from' => $sender,
            'text' => $message,
            'content' => array('text' => $message),
        );

        curl_setopt($curl, CURLOPT_URL, 'https://messaging.bandwidth.com/api/v2/users/' . $account_id . '/messages');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERPWD, $api_token . ":" . $api_secret);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
            )
        );
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_errno($curl);
        curl_close($curl);

        if($test_call) return $result;

        if ($curl_error !== 0) {
            return false;
        }

        $is_success = 200 <= $code && $code < 300;

        return $is_success;
    }

}
