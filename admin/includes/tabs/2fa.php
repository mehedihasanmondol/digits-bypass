<?php

use DigitsFormHandler\UserFlow;

if (!defined('ABSPATH')) {
    exit;
}

function digits_settings_login_flow()
{


    ?>
    <div class="dig_admin_head">
        <span><?php _e('2-factor Authentication', 'digits'); ?></span>
    </div>

    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">

        </div>
    </div>
    <?php
}

function digits_admin_login_allowed_methods()
{
    $digits_user_based_flow_enable = get_option('digits_user_based_flow_enable', false);

    $auth_flow = get_option('digits_auth_flow', false);

    $user_based_flow = UserFlow::instance()->get_flow_string();

    if (empty($auth_flow)) {
        $auth_flow = json_encode(digits_admin_default_auth_flow());
    } else {
        $auth_flow = stripslashes($auth_flow);
    }
    $dig_enable_2fa_auth = true;
    $dig_enable_3fa_auth = false;
    ?>
    <div class="digits_admin_login_auth_editor">
        <table class="form-table">
            <tr>
                <th scope="row"><label class="top-10">
                        <?php _e('User / UserRole Based Login Flow', 'digits'); ?>
                    </label>
                </th>
                <td>
                    <?php digits_input_switch('digits_user_based_flow_enable', $digits_user_based_flow_enable); ?>
                </td>
            </tr>
        </table>
        <script id="digits_admin_auth-template" type="text/template">
            <div class="digits_admin_user_auth_steps_wrapper">
                <table class="form-table digits_admin_auth_basic_info">
                    <tr>
                        <th scope="row"><label class="top-10">
                                <?php _e('User / User Role', 'digits'); ?>
                            </label>
                        </th>
                        <td class="digits_admin_auth_user_selector">
                            <select data-nonce="<?php esc_attr_e(wp_create_nonce('digits_flow_user_list')); ?>"
                                    data-source="digits_flow_user_list"
                                    class="digits_flow_user_select digits_multiselect_enable"
                                    placeholder="<?php esc_attr_e('Type Username or UserRole', 'digits'); ?>"
                                    multiple="multiple">

                            </select>
                        </td>
                    </tr>
                    <tr class="digits_checkbox_row">
                        <th scope="row"><label class="top-10">
                                <?php _e('2-Factor Authentication (2FA)', 'digits'); ?>
                            </label>
                        </th>
                        <td class="digits_admin_toggle_auth_step" data-step="2">
                            <?php digits_input_switch('dig_enable_2fa_auth', $dig_enable_2fa_auth); ?>
                        </td>
                    </tr>

                    <tr class="digits_checkbox_row">
                        <th scope="row"><label class="top-10">
                                <?php _e('3-Factor Authentication (3FA)', 'digits'); ?>
                            </label>
                        </th>
                        <td class="digits_admin_toggle_auth_step" data-step="3">
                            <?php digits_input_switch('dig_enable_3fa_auth', $dig_enable_3fa_auth); ?>
                        </td>
                    </tr>
                </table>
                <div class="digits_admin_auth_step_wrapper">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label class="top-10">
                                    <?php _e('Authentication (1st Step)', 'digits'); ?>
                                </label>
                            </th>
                            <td class="digits_admin_auth_step_1" data-step="1">
                                <?php
                                digits_admin_auth_available_steps()
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label class="top-10">
                                    <?php _e('Authentication (2nd Step)', 'digits'); ?>
                                </label>
                            </th>
                            <td class="digits_admin_auth_step_2" data-step="2">
                                <?php
                                digits_admin_auth_available_steps()
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label class="top-10">
                                    <?php _e('Authentication (3rd Step)', 'digits'); ?>
                                </label>
                            </th>
                            <td class="digits_admin_auth_step_3" data-step="3">
                                <?php
                                digits_admin_auth_available_steps()
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </script>
        <div class="digits_admin_user_auth_steps digits_admin_login_flow_bx" data-type="universal"
             data-change="1" <?php if (!empty($digits_user_based_flow_enable)) echo 'style="display: none;"'; ?>>
        </div>

        <div class="digits_admin_user_based_auth_steps"
             data-change="1" <?php if (empty($digits_user_based_flow_enable)) echo 'style="display: none;"'; ?>>

            <div id="digits_admin_ub_flow_all_users"
                 class="digits_admin_ub_login_flow_box digits_admin_ub_login_flow_all_users">
                <div class="digits_admin_ub_login_flow_head">
                    <div class="digits_admin_ub_login_flow_label_wrapper">
                        <div class="digits_admin_ub_login_flow_label">
                            <?php echo esc_attr__('All Users', 'digits'); ?>
                        </div>
                    </div>
                    <div class="digits_admin_ub_login_flow_act">
                        <div class="icon-shape icon-shape-dims digits_flow_delete"></div>
                        <div class="icon-shape-dims digits_admin_up_down_ic digits_rearrange"></div>
                        <div class="icon-shape-dims digits_admin_chevron_ic digits_flow_toggle_active"></div>
                    </div>
                </div>
                <div class="digits_admin_ub_login_flow_body digits_admin_login_flow_bx" data-type="user"
                     style="display: none;">
                </div>
            </div>

            <div class="digits_admin_ub_login_flows" id="digits_admin_ub_login_flows">

            </div>
            <div class="digits_admin_add_login_flow">
                <?php echo esc_attr__('Add Login Flow', 'digits'); ?>
            </div>
        </div>

        <input type="hidden" id="digits_auth_flow" name="digits_auth_flow"
               value="<?php echo esc_attr($auth_flow); ?>"/>

        <input type="hidden" id="digits_auth_user_based_flow" name="digits_auth_user_based_flow"
               value="<?php echo esc_attr($user_based_flow); ?>"/>
    </div>
    <?php
    do_action('digits_admin_user_flow');

    wp_register_script('digits-auth-flow', get_digits_asset_uri('/admin/assets/js/auth_flow.min.js'), array(
        'jquery',
    ), digits_version(), true);

    $obj = array(
        'ajax_url' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('digits-auth-flow', 'digauthflow', $obj);
    wp_enqueue_script('digits-auth-flow');
}

function digits_admin_default_auth_flow()
{
    $auth_flow = array(
        '1fa' => ['enable' => true, 'methods' => ['password', 'email_otp', 'sms_otp', 'whatsapp_otp']],
        '2fa' => ['enable' => true, 'methods' => ['2fa_app']],
        '3fa' => ['enable' => false, 'methods' => []]
    );
    return $auth_flow;
}

function digits_all_auth_steps()
{
    return [
        'password' => __('Password', 'digits'),
        'email_otp' => __('Email OTP', 'digits'),
        'sms_otp' => __('SMS OTP', 'digits'),
        'whatsapp_otp' => __('WhatsApp OTP', 'digits'),
        '2fa_app' => __('Authenticator App (like Google Auth, Authy, etc.)', 'digits'),
        'cross-platform' => __('Physical Key (like Yubikey etc)', 'digits'),
        'platform' => __('Device Biometrics', 'digits'),
    ];
}

function digits_admin_auth_available_steps()
{
    $methods = digits_all_auth_steps();
    ?>
    <div class="digits_auth_available_steps">
        <?php
        foreach ($methods as $key => $label) {
            $values = [];
            $name = 'digits_auth_step_type';
            digits_input_checkbox($name, $key, $values, $label);
        }
        ?>
    </div>
    <?php
}


function digits_admin_get_user_flow()
{
    $auth_flow = get_option('digits_auth_flow', false);

    if (!empty($auth_flow)) {
        $auth_flow = json_decode(stripslashes($auth_flow), true);
    } else {
        $auth_flow = digits_admin_default_auth_flow();
    }

    return $auth_flow;
}

add_action('wp_ajax_digits_flow_user_list', 'digits_flow_user_list');
function digits_flow_user_list()
{
    check_ajax_referer('digits_flow_user_list', 'nonce', true);

    if (!current_user_can('manage_options') || !is_user_logged_in()) {
        die();
    }
    $search = esc_attr(sanitize_text_field($_REQUEST['search']));

    $data = array();

    $user_roles_opt = array();

    $user_roles = array();
    foreach (wp_roles()->roles as $rkey => $rvalue) {

        $role_name = $rvalue['name'];
        if (!empty($search) && stripos($rkey, $search) === false && stripos($role_name, $search) === false) {
            continue;
        }


        $user_roles[] = array(
            'id' => 'ug_' . $rkey,
            'text' => $role_name);
    }

    if (!empty($user_roles)) {

        $user_roles_opt['text'] = esc_attr__('User Role', 'digits');
        $user_roles_opt['children'] = $user_roles;
        $data['results'][] = $user_roles_opt;
    }

    $query = array(
        'fields' => array('ID', 'user_login'),
        'number' => 30
    );


    if (!empty($search) && strlen($search) >= 2) {
        $query['search'] = '*' . $search . '*';
        $query['search_columns'] = array(
            'user_login',
            'display_name',
            'user_email',
        );


        $find_users = new WP_User_Query($query);

        $user_opt = array();
        $users_result = array();

        foreach ($find_users->get_results() as $user) {
            $users_result[] = array(
                'id' => 'user_id_' . $user->ID,
                'text' => $user->user_login);
        }

        if (!empty($users_result)) {
            $user_opt['text'] = esc_attr__('Users', 'digits');
            $user_opt['children'] = $users_result;
            $data['results'][] = $user_opt;
        }
    }

    wp_send_json($data);
}