<?php

namespace DigitsSettingsHandler;


use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/secure_modals.php';
require_once dirname(__FILE__) . '/secure_account_setup.php';
require_once dirname(__FILE__) . '/UserAccount.php';
require_once dirname(__FILE__) . '/setup_account_security.php';
require_once dirname(__FILE__) . '/wauth/init.php';
require_once dirname(__FILE__) . '/secure_account_handler.php';
require_once dirname(__FILE__) . '/secure_account_disable.php';
require_once dirname(__FILE__) . '/shortcode_secure_account.php';

Secure::instance();

final class Secure
{
    protected static $_instance = null;

    /** @var SecureModals */
    public $secureModals = null;

    public function __construct()
    {
        $this->add_shortcodes();
        $this->secureModals = SecureModals::instance();
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

    public function add_shortcodes()
    {
        add_shortcode('df-totp-setup', [$this, 'shortcode_setup_totp']);
        add_shortcode('df-biometrics-setup', [$this, 'shortcode_setup_biometrics']);
        add_shortcode('df-2fa-setup', [$this, 'shortcode_setup_2fa']);
        add_shortcode('df-3fa-setup', [$this, 'shortcode_setup_3fa']);
        /*remote*/
        add_action('wp_head', [$this, 'remote_device_setup']);
    }

    public function shortcode_setup_totp($attrs)
    {
        if (!is_user_logged_in()) {
            return '';
        }
        ob_start();
        $this->render_setup_totp();
        return ob_get_clean();
    }

    public function shortcode_setup_2fa($attrs)
    {
        if (!is_user_logged_in()) {
            return '';
        }
        ob_start();
        $this->render_setup_fa_step(2);
        return ob_get_clean();
    }

    public function shortcode_setup_3fa($attrs)
    {
        if (!is_user_logged_in()) {
            return '';
        }
        ob_start();
        $this->render_setup_fa_step(3);
        return ob_get_clean();
    }

    public function shortcode_setup_biometrics($attrs)
    {
        if (!is_user_logged_in()) {
            return '';
        }
        ob_start();
        $show_icons = !empty($attrs['icons']);
        $this->render_setup_biometrics($show_icons);
        return ob_get_clean();
    }

    public function render_setup_totp()
    {
        $user_id = get_current_user_id();
        $totp = UserAccountInfo::instance()->get_user_totp($user_id, false);

        $button_text = __('Setup', 'digits');
        $button_class = 'setup_button';
        $action = 'dig_setup_auth_app';

        $nonce = 'digits_auth_app_setup_shortcode';
        if (!empty($totp)) {
            $action = 'dig_remove_auth_app';
            $button_text = __('Remove', 'digits');
            $nonce = 'digits_remove_auth_app_setup_shortcode';
            $button_class = 'remove_button';
        }

        ?>
        <div class="digits_secure_account">
            <form>
                <div class="digits_secure_account_heading">
                    <?php esc_attr_e('2FA App (Google Auth, Authy, etc)', 'digits'); ?>
                </div>
                <div class="digits_secure_account_footer">
                    <?php $this->nonce_field($nonce); ?>
                    <button class="button button-primary digits_secure_account_modal <?php echo esc_attr($button_class); ?>"
                            data-action="<?php echo esc_attr($action); ?>"
                            type="button">
                        <?php echo esc_attr($button_text); ?>
                    </button>
                </div>
                <?php
                $this->secureModals->load_modal();
                ?>
            </form>
        </div>
        <?php
        $this->enqueue_scripts();
    }

    public function render_setup_biometrics($show_icons = false)
    {
        $user_id = get_current_user_id();
        $allow_multiple_devices = get_option('digits_allow_multiple_device', 1);

        $can_add_keys = dig_securityKeysEnabled();
        ?>
        <div class="digits_secure_account">
            <div class="digits_secure_account_heading">
                <?php esc_attr_e('Biometrics & Security Key Devices', 'digits'); ?>
            </div>

            <div class="digits_secure_account_body">
                <?php
                $devices = \DigitsDeviceAuth::instance()->listUserSecurityDevices($user_id);
                foreach ($devices as $device) {
                    $device_type = $device['device_type'];
                    $device_name = $device['device_name'];
                    ?>
                    <div class="digits_secure_account_item">
                        <?php
                        if ($show_icons) {
                            $icon_type = 'digits_secure_account_item_ic_key';
                            if ($device_type == 'platform') {
                                if (!empty($device['is_mobile'])) {
                                    $icon_type = 'digits_secure_account_item_ic_mob';
                                } else {
                                    $icon_type = 'digits_secure_account_item_ic_comp';
                                }
                            }
                            ?>
                            <div class="digits_secure_account_item_ic <?php echo $icon_type; ?>"></div>
                            <?php
                        }
                        ?>
                        <div class="digits_secure_account_item-name">
                            <?php echo $device_name; ?>
                        </div>
                        <div class="digits_secure_account_item-delete">
                            <div class="digits_secure_account_item-delete-icon digits_secure_account_delete"></div>
                            <input type="hidden" name="device_id"
                                   value="<?php echo esc_attr($device['uniqid']); ?>"/>
                            <input type="hidden" name="device_type"
                                   value="<?php echo esc_attr($device_type); ?>"/>
                            <input type="hidden" name="device_name"
                                   value="<?php echo esc_attr($device_name); ?>"/>
                            <?php wp_nonce_field($device['uniqid'] . $device_type . '_delete'); ?>
                            <input type="hidden" name="action" value="digits_delete_security_device"/>
                        </div>
                    </div>
                    <?php
                }
                ?>

            </div>

            <div class="digits_secure_account_footer">
                <form>

                    <?php $this->nonce_field('dig_setup_bio_key_devices_shortcode'); ?>
                    <?php
                    if ($allow_multiple_devices == 1 && $can_add_keys) {
                        ?>
                        <button class="button button-primary digits_secure_account_modal setup_button"
                                data-action="dig_setup_bio_key_devices" type="button">
                            <?php esc_attr_e('Add Device', 'digits'); ?>
                        </button>
                        <?php
                    }
                    ?>
                </form>
            </div>
            <?php
            $this->secureModals->load_modal();
            ?>
        </div>
        <?php
        $this->enqueue_scripts();
    }

    public function render_setup_fa_step($step_no)
    {
        $user_id = get_current_user_id();
        $available_methods = UserSettingsHandler::instance()->get_all_available_methods($user_id, $step_no);
        if (empty($available_methods)) {
            return '';
        }

        $heading = __('Enable %s-Factor Authentication', 'digits');
        $heading = sprintf($heading, $step_no);

        $input_class = [];

        $action_type = 'setup';

        $user_methods = UserSettingsHandler::getUserFaPreferredMethods($user_id, $step_no);

        if (!empty($user_methods)) {
            $action_type = 'disable';
            $input_class[] = 'digits-form_checked';
        }

        $action = "digits_{$action_type}_2fa_modal";
        if ($step_no == 3) {
            $action = "digits_{$action_type}_3fa_modal";
        }

        $input_class[] = 'digits-input_checkbox';

        ?>
        <form>
            <div class="digits_secure_account">
                <div class="digits_secure_account_modal" data-action="<?php echo $action; ?>">
                    <div class="<?php echo esc_attr(implode(" ", $input_class)); ?>">
                        <label>
                        <span class="digits-inp-checkbox_icon digits-inp_bg_defaultColor">
                        </span>
                            <span class="digits_secure_account_heading">
                            <?php echo esc_attr($heading); ?>
                        </span>
                        </label>
                    </div>
                </div>
                <?php
                if (!empty($user_methods)) {
                    ?>
                    <div class="digits_fa_steps_wrapper">
                        <?php
                        $all_methods = digits_all_auth_steps();
                        foreach ($user_methods as $method) {
                            ?>
                            <div class="digits_fa_step_name">
                                <?php echo esc_attr($all_methods[$method]); ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }

                $this->nonce_field($action);
                ?>
            </div>
        </form>
        <?php
        $this->secureModals->load_modal();
        $this->enqueue_scripts();
    }

    public function nonce_field($action = 'digits_secure')
    {
        wp_nonce_field($action);
    }

    public function remote_device_setup()
    {
        if (!empty($_REQUEST['callback']) && !empty($_REQUEST['action_type'])) {
            if ($_REQUEST['callback'] == 'setup_device' && $_REQUEST['action_type'] == 'device_auth') {
                $this->enqueue_scripts();
            }
        }
    }

    public function enqueue_scripts()
    {
        wp_register_script('digits-secure-script', get_digits_asset_uri('/assets/js/secure.min.js'), array(
            'jquery',
        ), digits_version(), true);

        $settings_array = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            "direction" => is_rtl() ? 'rtl' : 'ltr',
            "copiedtoclipboard" => __("Copied to clipboard", "digits"),
            'dig_dsb' => get_option('dig_dsb', -1),
        );
        wp_localize_script('digits-secure-script', 'dig_secure', $settings_array);

        wp_enqueue_script('digits-secure-script');
    }
}
