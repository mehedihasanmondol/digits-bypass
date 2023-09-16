<?php

namespace SMSGateway;


class Esendex
{
    // docs at: https://developers.esendex.com/api-reference#messagedispatcher
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call)
    {

        return self::process_sms($gateway_fields, $mobile, $message, $test_call);
    }

    public static function process_sms($gateway_fields, $mobile, $message, $test_call)
    {
        $account_reference = $gateway_fields['account_reference'];
        $username = $gateway_fields['username'];
        $password = $gateway_fields['password'];
        $sender = $gateway_fields['sender'];

        $curl = curl_init();
        $token = base64_encode($username . ':' . $password);
        $data = array(
            'accountreference' => $account_reference,
            'messages' => array(array(
                'from' => $sender,
                'to' => $mobile,
                'body' => $message,
            )),
        );

        curl_setopt($curl, CURLOPT_URL, 'https://api.esendex.com/v1.0/messagedispatcher');
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $token,
            )
        );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_errno($curl);
        curl_close($curl);

        if ($test_call) return $result;

        if ($curl_error !== 0) {
            return false;
        }

        $is_success = 200 <= $code && $code < 300;

        return $is_success;
    }
}
