<?php

namespace DigitsSettingsHandler;


use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

SecureAccountSetup::instance();

final class SecureAccountSetup
{

    protected static $_instance = null;

    public function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        add_action('wp_ajax_digits_setup_2fa_modal', [$this, 'setup_2fa_modal']);
        add_action('wp_ajax_digits_setup_3fa_modal', [$this, 'setup_3fa_modal']);
    }

    public function setup_2fa_modal()
    {
        $this->validate_request('digits_setup_2fa_modal');
        $data = array();
        try {
            $data['html'] = $this->setup_fa_modal(2);
            wp_send_json_success($data);
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
        wp_send_json_success($data);
    }

    private function validate_request($action)
    {
        check_ajax_referer($action);
        if (!is_user_logged_in()) {
            wp_send_json_error(array("message" => __("Please login to continue!")));
        }
    }

    /**
     * @throws \Exception
     */
    public function setup_fa_modal($step_no)
    {

        ob_start();

        $title = __('Setup %s-Factor Authentication', 'digits');
        $title = sprintf($title, $step_no);

        $user_id = get_current_user_id();
        $available_methods = SecureAccountHandler::instance()->getUserAvailableSetupMethods($user_id, $step_no, true);

        $action = 'digits_enable_account_' . $step_no . 'fa';
        ?>
        <form class="digits-setup_additional_step">
            <div class="digits_secure_modal-title">
                <?php echo esc_attr($title); ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_row">
                    <?php
                    foreach ($available_methods as $available_method) {
                        $label = $this->get_auth_as_step_label($available_method, $step_no);
                        ?>
                        <div class="digits-form_input">
                            <div class="digits-input_checkbox">
                                <label>
                                    <span class="digits-inp-checkbox_icon"></span>
                                    <input class="hide_inp_checkbox" name="auth_methods[]"
                                           value="<?php echo esc_attr($available_method); ?>" type="checkbox">
                                    <?php
                                    echo esc_attr($label);
                                    ?>
                            </div>

                        </div>
                        <?php
                    }
                    ?>
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
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>"/>
            <?php wp_nonce_field($action); ?>
        </form>
        <?php
        return ob_get_clean();
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

    public function get_auth_as_step_label($key, $step_no)
    {
        $auths = array(
            'password' => array('label' => __('Use Password', 'digits')),
            'email_otp' => array('label' => __('Use Email', 'digits')),
            'sms_otp' => array('label' => __('Use SMS', 'digits')),
            'whatsapp_otp' => array('label' => __('Use WhatsApp ', 'digits')),
            '2fa_app' => array('label' => __('2FA App (Google Auth, Authy, etc)', 'digits')),
            'platform' => array('label' => __('Use Device', 'digits')),
            'cross-platform' => array('label' => __('Use Security Key', 'digits')),

        );
        $auth = $auths[$key];
        $label = $auth['label'];
        return $label;
    }

    public function setup_3fa_modal()
    {
        $this->validate_request('digits_setup_3fa_modal');
        $data = array();
        try {
            $data['html'] = $this->setup_fa_modal(3);
            wp_send_json_success($data);
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
    }

}