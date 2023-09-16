<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('digits_create_database', 'digits_create_req_logs_db');

function digits_create_req_logs_db()
{
    global $wpdb;


    $tb = $wpdb->prefix . 'digits_request_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tb (
                  request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		          phone VARCHAR(40) NOT NULL,
		          email VARCHAR(100) NOT NULL,
		          mode VARCHAR(100) NOT NULL,
		          request_type VARCHAR(100) NOT NULL,
		          user_agent VARCHAR(255) NULL,
		          ip VARCHAR(200) NOT NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          PRIMARY KEY  (request_id),
		          INDEX idx_phone (phone),
		          INDEX idx_email (email),
                   INDEX idx_ip (ip)
	            ) $charset_collate;";
        dbDelta(array($sql));
    }

    $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s";
    $query = $wpdb->prepare($query, $tb, 'gateway_id');
    $row = $wpdb->get_results($query);
    if (empty($row)) {
        $wpdb->query("ALTER TABLE $tb ADD message TEXT NULL,ADD gateway_id VARCHAR(255) NULL,ADD sub_gateway VARCHAR(255) NULL");
    }
}

function digits_add_request_log($phone, $mode, $request_type, $message, $gateway)
{
    global $wpdb;
    $table = $wpdb->prefix . 'digits_request_logs';
    $data = array();
    $data['ip'] = digits_get_ip();
    if (is_numeric($phone)) {
        $data['phone'] = $phone;
    } else {
        $data['email'] = $phone;
    }
    $data['mode'] = $mode;

    $data['request_type'] = $request_type;
    $data['message'] = $message;
    $data['sub_gateway'] = 0;
    $data['gateway_id'] = $gateway;

    $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    return $wpdb->insert($table, $data);
}


function digits_check_request($phone, $email)
{

    $brute_force_protection = get_option('digits_brute_force_protection', 1);
    if ($brute_force_protection == 0) {
        return true;
    }
    $ip = digits_get_ip();

    $brute_force_allowed_ip = get_option("dig_brute_force_allowed_ip");
    if (is_array($brute_force_allowed_ip) && in_array($ip, $brute_force_allowed_ip)) {
        return true;
    }

    $total_requests = 0;
    if (!empty($phone)) {
        $brute_key = 'phone';
        $requests = digits_count_req_in_time($brute_key, $phone, 12, 'hour', false);
        $total_requests = sizeof($requests);
    }
    if ($total_requests > 3) {
        /*count -> minute*/
        $gap_required = array(
            4 => 1,
            5 => 4,
            8 => 60,
            10 => 180,
            16 => 360
        );
        $last_request = reset($requests);
        $last_request_time = strtotime($last_request->time);
        $time_difference = (time() - $last_request_time) / 60;

        $block = true;
        if (isset($gap_required[$total_requests])) {
            $required_gap = $gap_required[$total_requests];
            if ($required_gap < $time_difference) {
                $block = false;
            }

        }
        if ($block) {
            return new WP_Error('limit_exceed', __('OTP limit has exceeded since you made too many attempts, Please try again after some time!', 'digits'));
        }
    }


    $limits = array(
        array(
            'duration_type' => 'day',
            'duration' => 1,
            'max' => 18,
            'type' => 'phone'
        ),
        array(
            'duration_type' => 'minute',
            'duration' => 10,
            'max' => 8,
            'type' => 'phone'
        ),
        array(
            'duration_type' => 'minute',
            'duration' => 10,
            'max' => 8,
            'type' => 'ip'
        ),
        array(
            'duration_type' => 'hour',
            'duration' => 1,
            'max' => 30,
            'type' => 'ip'
        ),
        array(
            'duration_type' => 'hour',
            'duration' => 2,
            'max' => 60,
            'type' => 'ip'
        ),
        array(
            'duration_type' => 'day',
            'duration' => 1,
            'max' => 100,
            'type' => 'ip'
        ),
        array(
            'duration_type' => 'day',
            'duration' => 15,
            'max' => 400,
            'type' => 'ip'
        ),
    );

    foreach ($limits as $limit) {
        $duration_type = $limit['duration_type'];
        $duration = $limit['duration'];
        $type = $limit['type'];
        $max = $limit['max'];

        $key = $type;

        if ($type == 'ip') {
            $value = $ip;
        } else {
            if ($type == 'phone') {
                $value = $phone;
            } else {
                $value = $email;
            }
        }
        if (empty($value)) {
            continue;
        }
        $count = digits_count_req_in_time($key, $value, $duration, $duration_type, true);

        if ($count > $max) {
            return new WP_Error('limit_exceed', __('OTP limit has exceeded since you made too many attempts, Please try again after some time!', 'digits'));
        }
    }
    return true;
}

function digits_count_req_in_time($key, $value, $days, $duration_type, $count = true)
{
    global $wpdb;
    $table = $wpdb->prefix . 'digits_request_logs';
    $days = absint($days);

    if (empty($days)) {
        return 0;
    }

    $key = filter_var($key, FILTER_SANITIZE_STRING);

    if ($duration_type == 'hour') {
        $diff = 'TIMESTAMPDIFF(HOUR, time, CURDATE())';
    } elseif ($duration_type == 'minute') {
        $diff = 'TIMESTAMPDIFF(MINUTE, time, CURDATE())';
    } else {
        $diff = 'DATEDIFF(CURDATE(), time)';
    }

    $select = "count(*)";
    if (!$count) {
        $select = "*";
    }
    $query = $wpdb->prepare("select " . $select . " from " . $table . " where " . $key . "='%s' AND " . $diff . " <= " . $days . " AND mode!=`email` ORDER BY time DESC", $value);

    if ($count) {
        $results = $wpdb->get_var($query);
    } else {
        $results = $wpdb->get_results($query);
    }
    return $results;
}