<?php


if (!defined('ABSPATH')) {
    exit;
}

DigitsTranslate::instance();

final class DigitsTranslate
{
    protected static $_instance = null;
    private $replaceOTPWith = false;

    public function __construct()
    {
        add_action('wp_loaded', [$this, 'init']);
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

    public function init()
    {
        $replace_otp_with = get_option('dig_replace_otp_word', false);
        if (!empty($replace_otp_with)) {
            $this->replaceOTPWith = $replace_otp_with;
            add_filter('gettext_digits', [$this, 'replace_word'], 100, 3);
        }
    }

    public function replace_word($translation, $text, $domain)
    {
        $translation = str_ireplace("OTP", $this->replaceOTPWith, $translation);
        return $translation;
    }

}
