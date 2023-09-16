<?php

namespace DigitsSettingsHandler;


use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

ShortcodeSecureAccount::instance();

final class ShortcodeSecureAccount
{
    protected static $_instance = null;

    public function __construct()
    {
        $this->add_shortcodes();
    }

    public function add_shortcodes()
    {
        add_shortcode('df-account-manage', [$this, 'shortcode_account_manage']);
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

    public function shortcode_account_manage()
    {
        if (!is_user_logged_in()) {
            return '';
        }
        ob_start();
        $this->render_account_manage();
        return ob_get_clean();
    }

    public function render_account_manage()
    {
        $user_id = get_current_user_id();
        ?>
        <div class="digits_account_security_dashboard-container digits_font">
            <div class="digits_account_security_dashboard">
                <div class="digits_account_security_dashboard-head">
                    <?php echo esc_attr(__('Account Security Dashboard', 'digits')); ?>
                </div>
                <div class="digits_account_security_dashboard-body">
                    <div class="digits_account_security_dashboard-totp_setup">
                        <?php echo do_shortcode('[df-totp-setup]'); ?>
                    </div>
                    <div class="digits_account_security_dashboard-keys_setup">
                        <?php echo do_shortcode('[df-biometrics-setup icons=1]'); ?>
                    </div>
                    <?php
                    $available_steps = [2, 3];
                    foreach ($available_steps as $step_no) {
                        $shortcode = "[df-{$step_no}fa-setup]";
                        $render = do_shortcode($shortcode);

                        if (empty($render)) {
                            continue;
                        }
                        ?>
                        <div class="digits_account_security_dashboard-manage_fa">
                            <?php echo $render; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="digits_account_security_dashboard-footer">
                    <?php if (UserSettingsHandler::isUser2FaEnabled($user_id)) {
                        ?>
                        <div class="digits_account_security-secure">
                            <span class="digits_account_security-secure_icon"></span>
                            <?php esc_attr_e('Secured', 'digits'); ?>
                        </div>
                        <?php
                    } ?>
                </div>
            </div>
        </div>
        <?php
        $this->enqueue_scripts();
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('secure_account',
            get_digits_asset_uri('/assets/css/secure_account.min.css'), array(),
            digits_version(), 'all');
    }

    public function nonce_field($action = 'digits_secure')
    {
        wp_nonce_field($action);
    }
}
