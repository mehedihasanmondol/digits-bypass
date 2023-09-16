<?php

use DigitsSettingsHandler\SecureModals;

if (!defined('ABSPATH')) {
    exit;
}

DigitsWCCheckoutHandler::instance();

class DigitsWCCheckoutHandler
{

    protected static $_instance = null;

    public function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        add_filter('woocommerce_order_button_html', [$this, 'place_order_button_html'], 5);

        add_filter('woocommerce_order_button_html', [$this, 'place_order_button_html'], 5);

        add_action('woocommerce_checkout_process', [$this, 'process_wc_checkout']);


        add_action('wp_ajax_digits_wc_checkout_phone_verification_modal', [$this, 'checkout_phone_verification_modal']);
        add_action('wp_ajax_nopriv_digits_wc_checkout_phone_verification_modal', [$this, 'checkout_phone_verification_modal']);
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

    public function process_wc_checkout()
    {

        $is_guest = false;
        $checkout_data = WC()->checkout()->get_posted_data();
        $payment_method = $checkout_data['payment_method'];

        if (!is_user_logged_in()) {
            if (!WC()->checkout()->is_registration_required() && empty($checkout_data['createaccount'])) {
                $is_guest = true;
            }

        }

        if ($is_guest) {
            $billing_phone_verification = get_option('digits_enable_guest_checkout_verification', '0');
        } else {
            $billing_phone_verification = get_option('digits_enable_billing_phone_verification', '0');
        }


        if ($billing_phone_verification == '0' || ($billing_phone_verification == 'cod' && $payment_method != 'cod')) {
            return;
        }
        $this->verify_checkout_mobile();
    }

    public function verify_checkout_mobile()
    {

        $phone_number = $_POST['mobile/email'];
        $otp = sanitize_text_field($_POST['digit_ac_otp']);
        if (isset($_POST['digt_countrycode'])) {
            $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        } else {
            $countrycode = sanitize_text_field($_POST['billing_phone_digt_countrycode']);
        }

        if (!verifyOTP($countrycode, $phone_number, $otp, false)) {
            wc_add_notice(__('Error verifying your Phone Number, please try again!', 'digits'), 'error');
        }
    }

    public function place_order_button_html($html)
    {
        $attr = ' data-digits_verify="' . esc_attr(wp_create_nonce('digits_wc_checkout_phone_verification')) . '" ';
        $html = str_replace("<button ", '<button onclick="verifyOTPbilling(10);return false;" ' . $attr, $html);
        return $html;
    }

    public function checkout_phone_verification_modal()
    {
        $data = array();
        try {
            $this->validate_request('digits_wc_checkout_phone_verification');

            $html = $this->phone_verification_modal();
            ob_start();

            SecureModals::secure_modal_html($html, 'digits_wc_checkout_phone_verification_modal', false);

            $data['html'] = ob_get_clean();
            wp_send_json_success($data);
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
        wp_send_json_success($data);
    }

    private function validate_request($action)
    {
        $check = check_ajax_referer($action, false, false);
        if (!$check) {
            throw new Exception(__('Unexpected error occurred, please try refreshing the page!', 'digits'));
        }
    }

    /**
     * @throws \Exception
     */
    public function phone_verification_modal()
    {

        ob_start();

        $title = __('Phone Verification', 'digits');

        $user_id = get_current_user_id();

        $action = 'digits_wc_checkout_phone_verification';
        ?>
        <form class="digits-checkout_phone_verification" id="digits-checkout_phone_verification">
            <div class="digits_secure_modal-title">
                <?php echo esc_attr($title); ?>
            </div>
            <div class="digits_secure_modal-desc digits_secure_modal_text">
                <div class="digits_secure_modal_text_opacity">
                    <?php esc_attr_e('Please enter the OTP sent to your phone', 'digits'); ?>
                </div>
                <div class="digits_secure_row digits-form_input">
                    <input class="digits_secure_input digits_secure_phone_otp" type="text"
                           autocomplete="off"
                           id="digits_secure_billing_phone_otp"
                           name="otp"
                           placeholder="<?php esc_attr_e('OTP', 'digits'); ?>" value=""
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
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>"/>
            <?php wp_nonce_field($action); ?>
        </form>
        <?php
        return ob_get_clean();
    }


}