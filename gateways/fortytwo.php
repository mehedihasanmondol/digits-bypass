<?php

namespace SMSGateway;


class FortyTwo {
    // docs at: https://www.fortytwo.com/developer-portal/amp/
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $authorization_token = $gateway_fields['authorization_token'];
        $sender = $gateway_fields['sender'];

        return self::process_sms($authorization_token, $sender, $mobile, $message, $test_call);
    }

    public static function process_sms($authorization_token, $sender, $mobile, $message, $test_call) {
        $curl = curl_init();
        // Don't send the same message twice a day
        $data = array(
            'destinations' => array(
                array('number' => $mobile)
            ),
            'sms_content' => array(
                'sender_id' => $sender,
                'message' => $message,
            ),
        );

        curl_setopt($curl, CURLOPT_URL, 'https://rest.fortytwo.com/1/im');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: Token " . $authorization_token,
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
