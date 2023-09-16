<?php

namespace SMSGateway;

require_once 'utils.php';

class Wavy {
    public static $chunks = 999;
    public static $supports_bulk = true;
    public static $bulk_type = 'FIXED_MESSAGE';

    // docs at: https://docs-latam.wavy.global/documentacion-tecnica/api-integraciones/sms-api
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $username = $gateway_fields['username'];
        $authentication_token = $gateway_fields['authentication_token'];

        $last_sent_or_results = self::process_sms($username, $authentication_token, [0 => [$mobile => $message]], $test_call);
        if ($test_call) return $last_sent_or_results[0];

        if ($last_sent_or_results === -1) {
            return false;
        }
        return true;
    }

    public static function process_sms($username, $authentication_token, $messages, $test_call) {
        $curl = curl_init();
        $chunked_messages = array_chunk($messages, self::$chunks);
        $results = [];
        $failed_sent = [];
        $fixed_message = '';

        foreach($chunked_messages as $message_batch) {
            $mobiles = [];
            $destinations = [];

            foreach($message_batch as $id => $message_descriptor) {
                foreach($message_descriptor as $mobile => $message) {
                    $fixed_message = $message;
                    $mobiles[] = $mobile;
                    $destinations[] = ['destination' => $mobile];
                }
            }

            $data = array(
                'messages' => $destinations,
                'defaultValues' => ['messageText' => $fixed_message],
            );

            curl_setopt($curl, CURLOPT_URL, 'https://api-messaging.movile.com/v1/send-bulk-sms');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'username: ' . $username,
                    'authenticationtoken: ' . $authentication_token
                )
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curl_error = curl_errno($curl);
            curl_close($curl);

            if($test_call) {
                $results[] = $result;
            }

            $is_success = 200 <= $code && $code < 300;

            if ($is_success && $curl_error !== 0) {
            } else {
                $failed_sent += $mobiles;
            }
        }

        if($test_call) return $results;

        return \last_sent_from_failed($messages, $failed_sent);
    }

    public static function sendBulkSMS($gateway_fields, $messages, $test_call) {
        $username = $gateway_fields['username'];
        $authentication_token = $gateway_fields['authentication_token'];

        return self::process_sms($username, $authentication_token, $messages, $test_call);
    }
}
