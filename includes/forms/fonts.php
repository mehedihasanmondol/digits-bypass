<?php

defined('ABSPATH') || exit;

DigitsFontHandler::instance();

class DigitsFontHandler
{

    protected static $_instance = null;
    public $font_name = false;

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

    public function init_default_fonts()
    {
        $font_family = digits_get_font_family();
        $font_info = explode("@", $font_family, 2);
        $font_group = $font_info[0];
        $font_name = $font_info[1];

        if ($font_group != 'system') {
            $font_source = 'https://fonts.googleapis.com/css';

            $fonts = array(
                $font_name . ":900,700,500,500i,400,200,300"
            );

            $font_handler = $font_group . '-' . $font_name;

            $font_src = add_query_arg(array(
                "family" => urlencode(implode("|", $fonts)),
            ), $font_source);

            wp_enqueue_style($font_handler, $font_src);
        }
        $this->font_name = $font_name;
        add_action('wp_footer', [$this, 'add_inline_css']);
        add_action('wp_head', [$this, 'add_inline_css']);
        add_action('login_head', [$this, 'add_inline_css']);
    }

    public function add_inline_css()
    {
        if (empty($this->font_name)) {
            return;
        }
        $font_name = $this->font_name;
        $this->font_name = false;
        ?>
        <style type="text/css">
            .digits_secure_modal_box, .digits_ui,
            .digits_secure_modal_box *, .digits_ui *,
            .digits_font, .dig_ma-box, .dig_ma-box input, .dig_ma-box input::placeholder, .dig_ma-box ::placeholder, .dig_ma-box label, .dig_ma-box button, .dig_ma-box select, .dig_ma-box * {
                font-family: '<?php echo esc_attr($font_name);?>', sans-serif;
            }
        </style>
        <?php
    }

}
