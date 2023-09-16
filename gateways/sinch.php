<?php

namespace SMSGateway;

require_once 'utils.php';

class Sinch {
    public static $chunks = 50;
    public static $supports_bulk = true;
    public static $bulk_type = 'FIXED_MESSAGE';

    // docs at: https://developers.sinch.com/docs/sms-rest-batches-endpoint
    public static function sendSMS($gateway_fields, $mobile, $message, $test_call) {
        $bearer_token = $gateway_fields['bearer_token'];
        $service_plan_id = $gateway_fields['service_plan_id'];
        $sender = $gateway_fields['sender'];

        $last_sent_or_results = self::process_sms($bearer_token, $service_plan_id, $sender, [0 => [$mobile => $message]], $test_call);
        if ($test_call) return $last_sent_or_results[0];

        if ($last_sent_or_results === -1) {
            return false;
        }
        return true;
    }

    public static function process_sms($bearer_token, $service_plan_id, $sender, $messages, $test_call) {
        $curl = curl_init();
        $chunked_messages = array_chunk($messages, self::$chunks);
        $results = [];
        $failed_sent = [];
        $fixed_message = '';

        foreach($chunked_messages as $message_batch) {
            $mobiles = [];

            foreach($message_batch as $id => $message_descriptor) {
                foreach($message_descriptor as $mobile => $message) {
                    $fixed_message = $message;
                    $mobiles[] = $mobile;
                }
            }

            $data = array(
                'to' => [$mobiles],
                'text' => $fixed_message,
                'from' => $sender,
            );

            curl_setopt($curl, CURLOPT_URL, 'https://eu.sms.api.sinch.com/xms/v1/' . $service_plan_id . '/batches');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $bearer_token
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
        $bearer_token = $gateway_fields['bearer_token'];
        $service_plan_id = $gateway_fields['service_plan_id'];
        $sender = $gateway_fields['sender'];

        return self::process_sms($bearer_token, $service_plan_id, $sender, $messages, $test_call);
    }
}
