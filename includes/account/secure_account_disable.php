<?php

namespace DigitsSettingsHandler;


use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

SecureAccountDisable::instance();

final class SecureAccountDisable
{

    protected static $_instance = null;

    public function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        add_action('wp_ajax_digits_disable_2fa_modal', [$this, 'disable_account_2fa_modal']);
        add_action('wp_ajax_digits_disable_3fa_modal', [$this, 'disable_account_3fa_modal']);


        add_action('wp_ajax_digits_disable_account_2fa', [$this, 'disable_account_2fa']);
        add_action('wp_ajax_digits_disable_account_3fa', [$this, 'disable_account_3fa']);
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

    public function disable_account_2fa()
    {
        $this->validate_request('digits_disable_account_2fa');
        $this->process_disable_account_fa(2);
    }

    private function validate_request($action)
    {
        check_ajax_referer($action);
        if (!is_user_logged_in()) {
            wp_send_json_error(array("message" => __("Please login to continue!")));
        }
    }

    public function process_disable_account_fa($step_no)
    {
        try {
            $user_id = get_current_user_id();
            $this->can_disable_auth_step($user_id, $step_no);

            $this->disable_account_fa($user_id, $step_no);

        } catch (\Exception $e) {
            $data = array();
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
    }

    /**
     * @throws \Exception
     */
    public function can_disable_auth_step($user_id, $step_no)
    {
        if (!UserSettingsHandler::isUserFaEnabled($user_id, $step_no)) {
            $error = __('%d-Factor Authentication is not enabled!', 'digits');
            $error = sprintf($error, $step_no);
            throw new \Exception($error);
        }

        if ($step_no == 2) {
            if (UserSettingsHandler::isUser3FaEnabled($user_id)) {
                $error = __('You need to first disable 3-Factor Authentication!', 'digits');
                throw new \Exception($error);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function disable_account_fa($user_id, $step_no)
    {
        $password = $_REQUEST['password'];
        if (empty($password)) {
            throw new \Exception(__('Please enter a valid Password!', 'digits'));
        }
        $user = get_user_by('ID', $user_id);

        $check = wp_check_password($password, $user->user_pass, $user_id);

        if (!$check) {
            throw new \Exception(__('Please enter a valid Password!', 'digits'));
        }
        UserSettingsHandler::removeUserFaMethod($user_id, $step_no);

        $data = array();
        $message = __('%d-Factor Authentication is now turned off!', 'digits');
        $message = sprintf($message, $step_no);
        $data['message'] = $message;
        wp_send_json_success($data);
    }

    public function disable_account_3fa()
    {
        $this->validate_request('digits_disable_account_3fa');
        $this->process_disable_account_fa(3);
    }

    public function disable_account_2fa_modal()
    {
        $this->validate_request('digits_disable_2fa_modal');
        $this->disable_user_fa_modal(2);
    }

    public function disable_user_fa_modal($step_no)
    {
        try {
            $this->_disable_user_fa_modal($step_no);
        } catch (\Exception $e) {
            $data = array();
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
    }

    public function _disable_user_fa_modal($step_no)
    {
        $user_id = get_current_user_id();

        $this->can_disable_auth_step($user_id, $step_no);
        $data = array();
        $data['html'] = $this->render_password_modal($step_no);
        wp_send_json_success($data);

    }

    public function render_password_modal($step_no)
    {
        ob_start();

        $heading = __('Disable %s-Factor Authentication', 'digits');
        $heading = sprintf($heading, $step_no);
        $action = "digits_disable_account_{$step_no}fa";

        $auth_type = 'password';

        $desc = __('Enter your password to continue', 'digits');
        ?>
        <form class="digits-disable_password digits-turn_off_submit_form">
            <div class="digits_secure_modal-title">
                <?php echo esc_attr($heading); ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_modal_text_opacity">
                    <?php echo esc_attr($desc); ?>
                </div>
                <div class="digits_secure_row digits-form_input">
                    <input class="digits_secure_input digits_auto_focus"
                           autocomplete="current-password"
                           type="password"
                           name="password"
                           placeholder="<?php esc_attr_e('Password', 'digits'); ?>"
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
            <input type="hidden" name="auth_type" value="<?php echo esc_attr($auth_type); ?>"/>
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>"/>
            <?php wp_nonce_field($action); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public function disable_account_3fa_modal()
    {
        $this->validate_request('digits_disable_3fa_modal');
        $this->disable_user_fa_modal(3);
    }

}