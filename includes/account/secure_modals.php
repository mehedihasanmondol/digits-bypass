<?php

namespace DigitsSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

SecureModals::instance();

final class SecureModals
{
    protected static $_instance = null;

    public function __construct()
    {

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

    public function show_secure_modal()
    {
        self::secure_modal_html('','',true);

    }


    public static function secure_modal_html($contents = '', $extra_class = '',$add_style = true)
    {
        $theme = get_option('dig_form_theme', 'automatic');
        $theme_class = 'digits-auto-theme';
        if ($theme == 'dark') {
            $theme_class = 'digits-dark-theme';
        }
        ?>
        <div class="digits_secure_modal_box digits-form_container <?php echo $theme_class . ' ' . $extra_class; ?>">
            <div class="digits_secure_modal_wrapper">
                <div class="digits_secure_modal">
                    <div class="digits_secure_modal_contents">
                        <?php echo $contents; ?>
                    </div>
                    <div class="digits_secure_modal-close"></div>
                </div>
                <div class="digits_secure_modal_overlay">
                </div>
            </div>
        </div>
        <?php
        if($add_style){
            digits_new_form_create_style();
        }
    }

    public function load_2fa_modal()
    {
        $this->load_modal();
    }

    public function load_modal()
    {
        add_action('wp_footer', array($this, 'show_secure_modal'));
    }

}