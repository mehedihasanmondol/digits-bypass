<?php

namespace DigitsSettingsHandler;


use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DigitsDeviceAuth;
use DigitsFormHandler\Handler;
use DigitsSessions;
use DigitsUserFormHandler\UserSettingsHandler;
use OTPHP\TOTP;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

SetupAccountAuths::instance();

final class SetupAccountAuths
{
    const TOTP_KEY = 'digits_totp';
    const TEMP_TOTP_KEY = 'temp_digits_totp';
    const TEMP_REMOTE_DEVICE_SETUP_KEY = 'temp_digits_setup_remote_device';
    const SETUP_STATUS = 'setup_pending';

    const TOTP_DURATION = 60;
    protected static $_instance = null;

    public function __construct()
    {
        add_action('wp_ajax_dig_setup_auth_app', [$this, 'setup_auth_app']);
        add_action('wp_ajax_dig_setup_bio_key_devices', [$this, 'setup_bio_key_devices']);

        add_action('wp_ajax_dig_remove_auth_app', [$this, 'remove_auth_app']);
        /*validate and save*/
        add_action('wp_ajax_dig_setup_2fa_otp_validation', [$this, 'process_setup_2fa_app']);

        add_action('wp_ajax_dig_auth_setup_device', [$this, 'create_auth_key']);
        add_action('wp_ajax_nopriv_dig_auth_setup_device', [$this, 'create_auth_key']);

        add_action('wp_ajax_digits_save_auth_key', [$this, 'save_auth_key']);
        add_action('wp_ajax_nopriv_digits_save_auth_key', [$this, 'save_auth_key']);

        add_action('wp_ajax_digits_setup_fa_email', [$this, 'add_account_email']);

        /*delete*/
        add_action('wp_ajax_digits_delete_security_device', [$this, 'ajax_delete_security_device']);


        add_action('wp_ajax_digits_check_remote_setup_status', [$this, 'check_remote_setup_status']);
    }

    public function render_email_setup()
    {
        ob_start();

        $user = wp_get_current_user();
        $email = $user->user_email;
        $user_id = get_current_user_id();

        if (!empty($email)) {
            wp_die("Error: Action not allowed!");
        }
        ?>
        <form class="digits-setup_email">
            <div class="digits_secure_modal-title">
                <?php esc_attr_e('Add Email Address', 'digits'); ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_row digits-form_input">
                    <input class="digits_secure_input digits_auto_focus" type="email"
                           name="email"
                           placeholder="<?php esc_attr_e('Email Address', 'digits'); ?>"
                           value=""/>
                </div>
            </div>
            <div class="digits_secure_modal-body">
                <button class="digits-form_button" type="submit">
                                    <span class="digits-form_button-text">
                                        <?php esc_attr_e('Continue', 'digits'); ?>
                                    </span>
                    <span class="digits-form_button_ic"></span>
                </button>
            </div>
            <input type="hidden" name="action" value="digits_setup_fa_email"/>
            <?php wp_nonce_field('digits_setup_fa_email'); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public function add_account_email()
    {
        $this->validate_request('digits_setup_fa_email');

        $data = array();
        if (empty($_REQUEST['email']) || !isValidEmail($_REQUEST['email'])) {
            $data['message'] = __('Please enter a valid Email!', 'digits');
            wp_send_json_error($data);
        }

        $user_email = $_REQUEST['email'];
        $user = wp_get_current_user();
        $user_id = $user->ID;

        if (!empty($user->user_email)) {
            $data['message'] = __('Action not allowed!', 'digits');
            wp_send_json_error($data);
        }

        $args = array(
            'ID' => $user_id,
            'user_email' => $user_email
        );

        $update = wp_update_user($args);

        if ($update instanceof \WP_Error) {
            $data['message'] = $update->get_error_message();
            wp_send_json_error($data);
        }
        $data['message'] = 'Done';
        wp_send_json_success($data);

    }

    private function validate_request($action = 'digits_secure')
    {
        check_ajax_referer($action);
        if (!is_user_logged_in()) {
            wp_send_json_error(array("message" => __("Please login to continue!")));
        }
    }

    public function ajax_delete_security_device()
    {
        $device_id = $_REQUEST['device_id'];
        $device_type = $_REQUEST['device_type'];
        $device_name = $_REQUEST['device_name'];
        if (empty($device_id) || empty($device_type) || empty($device_name)) {
            return;
        }
        $this->validate_request($device_id . $device_type . '_delete');

        $user_id = get_current_user_id();

        $devices = \DigitsDeviceAuth::instance()->getUserSecurityDevicesType($user_id, $device_type);

        if (count($devices) <= 1) {
            $check = $this->is_method_in_use($user_id, $device_type, $device_name);
            if ($check instanceof WP_Error) {
                wp_send_json_error(array('message' => $check->get_error_message()));
            }
        }

        DigitsDeviceAuth::instance()->deleteUserSecurityDevice($user_id, $device_id);

        wp_send_json_success(array('message' => __('Device deleted successfully!', 'digits')));
    }

    /**
     *  Constructor.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function is_method_in_use($user_id, $method, $label)
    {
        $label = esc_attr($label);
        $available_steps = [2, 3];
        foreach ($available_steps as $step_no) {
            $user_preferred_steps = UserSettingsHandler::getUserFaPreferredMethods($user_id, $step_no);
            if (in_array($method, $user_preferred_steps)) {
                $error = __('Error while removing %s, as it is in use in %s-Factor Authentication!', 'digits');
                $error = sprintf($error, $label, $step_no);
                return new WP_Error('in_use', $error);
            }
        }
        return false;
    }

    public function setup_auth_app()
    {
        $this->validate_request('digits_auth_app_setup_shortcode');
        $data = array();
        $data['html'] = $this->render_auth_app_setup();
        wp_send_json_success($data);
    }

    public function render_auth_app_setup()
    {
        ob_start();

        $user_id = get_current_user_id();


        $totp = UserAccountInfo::instance()->get_user_totp($user_id, true);
        $key = $totp->getSecret();

        $totp_uri = $totp->getProvisioningUri();
        $url = digits_create_qr($totp_uri);

        ?>
        <form class="digits-setup_2fa_app">
            <div class="digits_secure_modal-title">
                <?php esc_attr_e('Setup 2FA App', 'digits'); ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_modal_text_opacity">
                    <?php esc_attr_e('Scan QR with Google Auth App, Authy, etc, or enter the key manually', 'digits'); ?>
                </div>
                <div class="digits_secure_row">
                    <div class="digits_secure_row_qr_code">
                        <?php echo $url; ?>
                    </div>
                </div>
                <div class="digits_secure_row digits-form_input digits_secure_floating">
                    <span class="digits_secure_floating_label"><?php esc_attr_e('Key:', 'digits'); ?></span>
                    <input class="default_cursor digits_secure_input dig_copy_inp" type="text"
                           value="<?php esc_attr_e($key); ?>"
                           readonly/>
                </div>
                <div class="digits_secure_row digits-form_input digits_display_none">
                    <input class="digits_secure_input digits_secure_2fa_otp" type="text"
                           autocomplete="off"
                           name="2fa_otp"
                           placeholder="<?php esc_attr_e('Enter code shown on 2FA app', 'digits'); ?>" value=""
                           maxlength="6"/>
                </div>
            </div>
            <div class="digits_secure_modal-body">
                <button class="digits-form_button" type="submit">
                                    <span class="digits-form_button-text">
                                        <?php esc_attr_e('Continue', 'digits'); ?>
                                    </span>
                    <span class="digits-form_button_ic"></span>
                </button>
            </div>
            <input type="hidden" name="action" value="dig_setup_2fa_otp_validation"/>
            <?php wp_nonce_field('digits_setup_2fa'); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public function setup_bio_key_devices()
    {
        $this->validate_request('dig_setup_bio_key_devices_shortcode');
        $this->_setup_bio_key_devices();
    }

    public function _setup_bio_key_devices()
    {
        $data = array();
        $data['html'] = $this->render_device_setup('all');
        $data['process_modal'] = true;
        $data['is_remote'] = $this->is_remote_phone_setup();
        $data['remote_nonce'] = wp_create_nonce('remote_status_check');
        wp_send_json_success($data);
    }

    public function render_device_setup($device_type)
    {
        if (!dig_securityKeysEnabled()) {
            return;
        }

        ob_start();

        $url = false;
        if (!$this->is_remote_phone_setup()) {

            $key_methods = array(
                'platform' => array(
                    'label' => __('Built-in Biometric Sensor', 'digits'),
                    'desc' => __('Check and follow your device\'s biometrics popup', 'digits')
                ),
                'cross-platform' => array(
                    'label' => __('USB Security Key', 'digits'),
                    'desc' => __('Check and follow your device\'s browser popup', 'digits')
                ),
            );

            $desc = __('Check and follow your device\'s biometrics popup or use Yubikey', 'digits');

            if ($device_type != 'all') {
                $key_methods = [$device_type => $key_methods[$device_type]];
                $desc = $key_methods[$device_type]['desc'];
            }

            $selected_method = array_keys($key_methods)[0];

            $title = __('Add Security Key', 'digits');

            DigitsSessions::delete(self::TEMP_REMOTE_DEVICE_SETUP_KEY);
        } else {
            $title = __('Phone\'s Fingerprint or Face ID', 'digits');
            $desc = __('You will be able to use the same device for login in future', 'digits');

            $token = Handler::generate_token(32);
            DigitsSessions::update(self::TEMP_REMOTE_DEVICE_SETUP_KEY, self::SETUP_STATUS, 3600, $token);
            $url = add_query_arg(
                array('device_token' => $token, 'action_type' => 'device_auth', 'callback' => 'setup_device'),
                home_url());

        }

        $device_name = isset($_REQUEST['device_name']) ? $_REQUEST['device_name'] : '';
        ?>
        <form class="digits-setup_security_key digits_secure_wrapper">
            <div class="digits_secure_modal-title">
                <?php
                echo esc_attr($title);
                ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_modal_text_opacity">
                    <?php echo esc_attr($desc); ?>
                </div>
                <?php
                if (!empty($key_methods)) {
                    ?>
                    <div class="digits_secure_row digits_secure_flex_radio digits-form_input digits_secure_device_type">
                        <?php

                        foreach ($key_methods as $key_method => $key_details) {
                            $checked = $key_method == $selected_method;
                            ?>
                            <div class="digits-input_radio <?php if ($checked) echo 'digits-form_checked' ?>">
                                <label>
                                    <span class="digits-inp-radio_icon"></span>
                                    <input class="hide_inp_radio" name="device_type"
                                           value="<?php echo esc_attr($key_method); ?>" <?php if ($checked) echo 'checked' ?>
                                           type="radio"/>
                                    <?php echo esc_attr($key_details['label']); ?>
                                </label>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>

                <div class="digits_secure_row">
                    <?php
                    if (!$this->is_remote_phone_setup()) {
                        ?>
                        <div class="digits_secure_fingerprint_container digits_secure_setup_box">
                            <div class="digits_secure_fingerprint_wrapper">
                                <div class="digits_secure_fingerprint_icon"></div>
                                <div class="digits_secure_phone_wrapper digits_secure_setup_phone">
                                    <div class="digits_secure_phone_icon">
                                    </div>
                                    <?php
                                    esc_attr_e('Use Phone', 'digits');
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="digits_secure_fingerprint_container digits_secure_setup_box">
                            <div class="digits_secure_phone-registered">
                                <div class="digits_secure_phone-success_ic"></div>
                                <div class="digits_secure_phone-success_text">
                                    <?php echo esc_attr(__('Device Registered', 'digits')); ?>
                                </div>
                            </div>
                            <div class="digits_secure_phone_qr_wrap">
                                <div class="digits_secure_phone_qr_container">
                                    <div class="digits_secure_qr_code digits_phone_scanner">
                                        <?php
                                        echo digits_create_qr($url);
                                        ?>
                                    </div>
                                    <div class="digits_secure_qr_code_hint">
                                        <?php echo esc_attr(__('Scan the QR code with your phone', 'digits')); ?>
                                        <input type="hidden" name="remote_phone_setup" value="1"/>
                                        <input type="hidden" name="remote_phone_setup_token"
                                               value="<?php echo esc_attr($token); ?>"/>
                                    </div>
                                    <div class="digits_secure_close-sic digits_secure_remove_phone_setup"
                                         data-remove="1"></div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                </div>
                <div class="digits_secure_row digits-form_input">
                    <input class="digits_secure_input digits_auto_focus" type="text"
                           name="device_name"
                           value="<?php echo esc_attr($device_name); ?>"
                           placeholder="<?php esc_attr_e('Device\'s Nickname', 'digits'); ?>"
                    />
                    <input type="hidden" name="digits_setup_phone" value=""/>
                </div>
            </div>
            <div class="digits_secure_modal-body">
                <button class="digits-form_button" type="submit">
                                    <span class="digits-form_button-text">
                                        <?php esc_attr_e('Continue', 'digits'); ?>
                                    </span>
                    <span class="digits-form_button_ic"></span>
                </button>
            </div>
            <input type="hidden" name="action" value="dig_auth_setup_device"/>
            <?php wp_nonce_field('dig_auth_setup_device'); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public function is_remote_phone_setup()
    {
        return !empty($_REQUEST['digits_setup_phone']) && $_REQUEST['digits_setup_phone'] == 1;
    }

    public function check_remote_setup_status()
    {
        $this->validate_request('remote_status_check');

        $device_status = DigitsSessions::get(self::TEMP_REMOTE_DEVICE_SETUP_KEY);
        if (empty($device_status)) {
            wp_send_json_error(array("message" => __('Session expired, please try again!', 'digits')));
        }
        $status = 'pending';

        if ($device_status != self::SETUP_STATUS) {
            $status = 'registered';
        }

        wp_send_json_success(array('status' => $status));
    }

    public function create_auth_key()
    {
        if (
            !empty($_REQUEST['device_token']) &&
            (!empty($_REQUEST['action_type']) && $_REQUEST['action_type'] == 'device_auth') &&
            (!empty($_REQUEST['callback']) && $_REQUEST['callback'] == 'setup_device')
        ) {
            $token_info = $this->get_remote_request_token();
            $user_id = $token_info->user_id;
            $device_name = 'remote';
            $device_type = 'platform';
        } else {
            $this->validate_request('dig_auth_setup_device');
            $device_type = $_REQUEST['device_type'];
            $device_name = $_REQUEST['device_name'];
            $user_id = get_current_user_id();
        }

        $this->_create_auth_key($user_id, $device_name, $device_type);
    }

    public function get_remote_request_token()
    {
        $identifier_id = $_REQUEST['device_token'];
        $token_info = DigitsSessions::get_from_identifier($identifier_id, true);

        if (empty($token_info)) {
            wp_send_json_error(array("message" => __('Request expired, please try again!', 'digits')));
        }

        if ($token_info->data_key != self::TEMP_REMOTE_DEVICE_SETUP_KEY) {
            wp_send_json_error(array("message" => __('Unexpected error occurred, please try again!', 'digits')));
        }

        if ($token_info->data_value != self::SETUP_STATUS) {
            wp_send_json_error(array("message" => __('You had already authenticated device, please check device setup wizard for more info!', 'digits')));
        }

        return $token_info;

    }

    public function _create_auth_key($user_id, $device_name, $device_type)
    {

        $allow_multiple_devices = get_option('digits_allow_multiple_device', 1);

        if (!dig_securityKeysEnabled()) {
            wp_send_json_error(array("message" => __('You don\'t have permission to setup keys!', 'digits')));
        }

        if (!empty($_REQUEST['digits_setup_phone'])) {
            $this->_setup_bio_key_devices();
            die();
        }


        $devices = \DigitsDeviceAuth::instance()->listUserSecurityDevices($user_id);

        if (sizeof($devices) >= 1 && $allow_multiple_devices != 1) {
            wp_send_json_error(array("message" => __('You cannot have multiple devices!', 'digits')));
        }

        if (empty($device_name)) {
            wp_send_json_error(array("message" => __('Device name cannot be empty!', 'digits')));
        }
        $device_name = esc_attr($device_name);

        if (!empty($_REQUEST['remote_phone_setup'])) {
            $this->process_remote_device_setup($device_name);
            die();
        }


        if (empty($device_type)) {
            wp_send_json_error(array("message" => __('Select a device type!', 'digits')));
        }
        $allowed_devices = array('cross-platform', 'platform');
        if (!in_array($device_type, $allowed_devices)) {
            wp_send_json_error(array("message" => __('Please select a valid device type!', 'digits')));
        }

        $data = array();
        $data['public_key'] = DigitsDeviceAuth::create_new_device_public_key($user_id, $device_name, $device_type);
        $data['nonce'] = wp_create_nonce('digits_save_auth_key');
        $data['action'] = 'digits_save_auth_key';

        wp_send_json_success($data);
    }

    public function process_remote_device_setup($device_name)
    {

        $device_data = DigitsSessions::get(self::TEMP_REMOTE_DEVICE_SETUP_KEY);
        if (empty($device_data)) {
            wp_send_json_error(array("message" => __('Session expired, please try again!', 'digits')));
        }

        if ($device_data == self::SETUP_STATUS) {
            wp_send_json_error(array("message" => __('Please register your device!', 'digits')));
        }

        $device_data = unserialize(base64_decode($device_data));

        $device_data['device_name'] = $device_name;

        $user_id = get_current_user_id();
        $register = DigitsDeviceAuth::instance()->add_user_new_device($device_data, $user_id);

        $result = array();
        $result['reload'] = true;
        $result['message'] = sprintf(__('You\'ve successfully added %s!', 'digits'), $register);

        DigitsSessions::delete(self::TEMP_REMOTE_DEVICE_SETUP_KEY);
        wp_send_json_success($result);
    }

    public function save_auth_key()
    {
        $auth_data = $_REQUEST['cred'];

        $add_to_user_account = false;

        if (!empty($_REQUEST['remote_setup_token'])) {
            $token_info = $this->get_remote_request_token();
        } else {
            $this->validate_request('digits_save_auth_key');
            $add_to_user_account = true;
        }

        if (empty($auth_data)) {
            wp_send_json_error(array('message' => __('Error', 'digits')));
        }


        $register = DigitsDeviceAuth::instance()->process_register_new_device($auth_data, $add_to_user_account);

        if ($register instanceof \WP_Error) {
            wp_send_json_error(array('message' => $register->get_error_message()));
        }

        if (!$add_to_user_account) {
            $identifier_id = $token_info->identifier_id;
            DigitsSessions::update_identifier_value($identifier_id, $register);
            wp_send_json_success(array('message' => __('You\'ve successfully registered device!', 'digits')));
        }

        wp_send_json_success(array('message' => sprintf(__('You\'ve successfully added %s!', 'digits'), $register)));
    }

    public function nonce_field()
    {
        wp_nonce_field('digits_secure');
    }

    public function remove_auth_app()
    {
        $this->validate_request('digits_remove_auth_app_setup_shortcode');
        try {
            $user_id = get_current_user_id();
            $method = '2fa_app';
            $label = '2FA App';
            $check = $this->is_method_in_use($user_id, $method, $label);

            if ($check instanceof WP_Error) {
                wp_send_json_error(array('message' => $check->get_error_message()));
            }

            $totp = UserAccountInfo::instance()->get_user_totp($user_id, true);
            if (empty($totp)) {
                wp_send_json_error(array('message' => __('Error!', 'digits')));
            } else {
                delete_user_meta($user_id, self::TOTP_KEY);
            }
            wp_send_json_success(array('message' => __('You\'ve successfully removed 2FA App!', 'digits')));
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function process_setup_2fa_app()
    {
        $this->validate_request('digits_setup_2fa');

        $otp = $_REQUEST['2fa_otp'];
        if (empty($otp) || strlen($otp) != 6) {
            wp_send_json_error(array('message' => __('OTP should be of 6 digit!', 'digits')));
        }

        $user_id = get_current_user_id();

        if (!empty($user_id)) {
            $totp = UserAccountInfo::instance()->get_user_totp($user_id, true);
            if ($totp->verify($otp)) {
                \DigitsSessions::delete_user_key($user_id, self::TEMP_TOTP_KEY);
                update_user_meta($user_id, self::TOTP_KEY, $totp->getSecret());
                wp_send_json_success(array('message' => __('You\'ve successfully enabled 2FA App!', 'digits')));
            } else {
                wp_send_json_error(array('message' => __('Please enter a valid OTP!', 'digits')));
            }
        }
    }


}