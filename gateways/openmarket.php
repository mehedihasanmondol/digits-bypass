<?php

namespace SMSGateway;


class OpenMarket {
    // docs at: https://www.openmarket.com/docs/Content/apis/v4http/things-to-know.htm#Basic
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $account_reference = $gateway_fields['account_reference'];
        $username = $gateway_fields['username'];
        $password = $gateway_fields['password'];
        $sender = $gateway_fields['sender'];

        return self::process_sms($username, $password, $sender, $mobile, $message, $test_call);
    }

    public static function process_sms($username, $password, $mobile, $message, $test_call) {
        $curl = curl_init();
        $token = base64_encode($username . ':' . $password);
        $data = array(
            'mobileTerminate' => array(
                'interaction' => 'one-way',
                'destination' => array(
                    'address' => $mobile,
                ),
                'source' => array(
                    'ton' => 1,
                    'address' => $sender,
                ),
                'message' => array(
                    'content' => $message,
                    'type' => 'text',
                )
            ),
        );

        curl_setopt($curl, CURLOPT_URL, 'https://smsc.openmarket.com/sms/v4/mt');
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $token,
            )
        );
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, true));

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
