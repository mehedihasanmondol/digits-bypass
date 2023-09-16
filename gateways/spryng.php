<?php

namespace SMSGateway;


class Spryng {
    // docs at: https://docs.spryngsms.com/?version=latest
    // supports bulk fixed message many recipients
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $bearer_token = $gateway_fields['bearer_token'];
        $sender = $gateway_fields['sender'];

        return self::process_sms($bearer_token, $sender, $mobile, $message, $test_call);
    }

    public static function process_sms($bearer_token, $sender, $mobile, $message, $test_call){
        $curl = curl_init();

        $data = array(
            'body' => $message,
            'encoding' => 'auto',
            'originator' => $sender,
            'recipients' => [$mobile],
        );

        curl_setopt($curl, CURLOPT_URL, 'https://rest.spryngsms.com/v1/messages?with%5B%5D=recipients');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $bearer_token,
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
