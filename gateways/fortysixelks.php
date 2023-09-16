<?php

namespace SMSGateway;


class FortySixElks
{
    // docs at: https://46elks.com/guides/sending-sms

    public static function sendSMS($gateway_fields, $mobile, $message, $test_call)
    {
        $username = $gateway_fields['username'];
        $password = $gateway_fields['password'];
        $sender = $gateway_fields['sender'];

        return self::process_sms($username, $password, $sender, $mobile, $message, $test_call);
    }

    public static function process_sms($username, $password, $sender, $mobile, $message, $test_call)
    {
        $curl = curl_init();
        $post_params = array(
            'from' => $sender,
            'to' => $mobile,
            'message' => $message,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            array('Content-type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ));
        curl_setopt($curl, CURLOPT_URL, 'https://api.46elks.com/a1/sms');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_params));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

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
