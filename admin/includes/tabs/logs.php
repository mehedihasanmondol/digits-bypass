<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_message_logs()
{
    $nonce = wp_create_nonce('digits_admin_message_logs');
    ?>
    <div class="digits_log_table_container">
        <div class="digits_log_table_heading">
            <?php esc_attr_e('Digits Logs', 'digits'); ?>
        </div>
        <table id="digits_message_logs" data-nonce="<?php echo esc_attr($nonce); ?>">
            <thead>
            <tr>
                <th><?php esc_attr_e('Date & Time', 'digits'); ?></th>
                <th><?php esc_attr_e('To', 'digits'); ?></th>
                <th><?php esc_attr_e('Route', 'digits'); ?></th>
                <th><?php esc_attr_e('Action', 'digits'); ?></th>
                <th><?php esc_attr_e('Content', 'digits'); ?></th>
            </tr>
            </thead>
        </table>
    </div>
    <?php
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.20/af-2.3.4/b-1.6.1/b-colvis-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/cr-1.5.2/fc-3.3.0/fh-3.1.6/kt-2.5.1/r-2.2.3/rg-1.1.1/rr-1.2.6/sc-2.0.1/sl-1.3.1/datatables.min.js', array(
        'jquery'
    ), null);

    wp_register_script('digits-admin-message-logs', get_digits_asset_uri('/admin/assets/js/logs.min.js'), array(
        'jquery',
        'datatables',
    ), digits_version(), true);

    $obj = array(
        'ajax_url' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('digits-admin-message-logs', 'digmeslog', $obj);
    wp_enqueue_script('digits-admin-message-logs');

    ?>
    <style>
        #wpcontent {
            background-color: #fafafa;
        }
    </style>
    <?php

}

add_action('wp_ajax_digits_message_log_data', 'digits_message_log_data');
function digits_message_log_data()
{
    if (!current_user_can('manage_options')) {
        die();
    }
    if (!wp_verify_nonce($_REQUEST['nonce'], 'digits_admin_message_logs')) {
        die();
    }

    global $wpdb;

    $start = absint($_REQUEST['start']);
    $end = $start + absint($_REQUEST['length']);

    $table = $wpdb->prefix . 'digits_request_logs';
    $sql = "SELECT COUNT(*) FROM $table";
    $total_entries = $wpdb->get_var($sql);

    $query = "SELECT * FROM $table ORDER BY request_id DESC LIMIT %d,%d";
    $query = $wpdb->prepare($query, $start, $end);
    $logs = $wpdb->get_results($query);

    $results = array();
    $data = array();

    $gateway_names = [];
    foreach ($logs as $log) {
        if (!empty($log->email)) {
            $to = $log->email;
        } else {
            $to = $log->phone;
        }
        $mode = $log->mode;

        $route = digits_log_get_mode_label($mode);
        if ($mode == 'sms') {
            $gateway_id = $log->gateway_id;
            if (!empty($gateway_id)) {
                if (isset($gateway_names[$mode][$gateway_id])) {
                    $gateway_name = $gateway_names[$mode][$gateway_id];
                } else {
                    $gateway_name = digits_log_get_gateway_name($gateway_id);
                    $gateway_names[$mode][$gateway_id] = $gateway_name;
                }
                if (!empty($gateway_name)) {
                    $route = sprintf('%s (%s)', $route, $gateway_name);
                }
            }
        }


        $date = new DateTime($log->time);
        $date = date_format($date, "d M 'y h:i A");

        $action = '';
        if (!empty($log->request_type)) {
            $action = ucfirst($log->request_type);
        }
        $data[] = [
            'date_time' => $date,
            'to' => $to,
            'route' => $route,
            'action' => $action,
            'content' => $log->message,
        ];
    }

    $results['recordsTotal'] = $total_entries;
    $results['recordsFiltered'] = $total_entries;
    $results['data'] = $data;
    wp_send_json($results);
}


function digits_log_get_mode_label($mode)
{
    $mode = strtolower($mode);
    $modes = [
        'sms' => __('SMS', 'digits'),
        'whatsapp' => __('WhatsApp', 'digits'),
        'email' => __('Email', 'digits'),
    ];
    return $modes[$mode];
}

function digits_log_get_gateway_name($gateway_no)
{
    $smsgateways = getGateWayArray();
    foreach ($smsgateways as $gateway_key => $gateway) {
        if ($gateway['value'] == $gateway_no) {
            if (isset($gateway['label'])) {
                return $gateway['label'];
            }
            return $gateway_key;
        }
    }
    return '';
}

