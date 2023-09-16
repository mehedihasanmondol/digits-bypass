<?php

if (!defined('ABSPATH')) {
    exit;
}

DigitsThemeCustomisations::instance();

class DigitsThemeCustomisations
{
    protected static $_instance = null;
    private $triggerForm = false;

    public function __construct()
    {
        add_action('wp_head', [$this, 'wp_head']);
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

    public function wp_head()
    {
        $editor_data = stripslashes(get_option('digits_editor_data', '{}'));
        if (empty($editor_data)) {
            return;
        }
        $editor_data = json_decode($editor_data, true);
        if (empty($editor_data)) {
            return;
        }

        if (!empty($editor_data['css_script'])) {
            ?>
            <style id="digits_custom_css">
                <?php echo $editor_data['css_script'];?>
            </style>
            <?php
        }
        if (!empty($editor_data['js_script'])) {
            ?>
            <script id="digits_custom_js" type="text/javascript">
                <?php echo $editor_data['js_script'];?>
            </script>
            <?php
        }

        if (!empty($editor_data['hideElements'])) {
            ?>
            <style id="digits_hide_elements">
                <?php
                echo implode(',',$editor_data['hideElements']).'{display: none;}';?>
            </style>
            <?php
        }
        if (!empty($editor_data['triggerForm'])) {
            $this->triggerForm = $editor_data['triggerForm'];
            add_action('wp_footer', [$this, 'wp_footer'], 10);
        }
    }

    public function wp_footer()
    {
        if (empty($this->triggerForm) && !is_array($this->triggerForm)) {
            return;
        }
        if (is_user_logged_in()) {
            return;
        }

        $native_forms = [
            'login_register_popup' => ['type' => 1, 'name' => 'login_register'],
            'login_popup' => ['type' => 4, 'name' => 'login'],
            'register_popup' => ['type' => 2, 'name' => 'register'],
            'login_register_page' => ['type' => '', 'href' => '?login=true', 'name' => 'login_register'],
            'login_page' => ['type' => 'login', 'href' => '?login=true&type=login', 'name' => 'login'],
            'register_page' => ['type' => 'register', 'href' => '?login=true&type=register', 'name' => 'register'],
        ];

        $current_url = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        ?>
        <script>
            jQuery(document).ready(function () {
                <?php
                $builder = class_exists('Digits_Pagebuilder_shortcodes');
                foreach ($this->triggerForm as $elem => $form_key) {
                    if (isset($native_forms[$form_key])) {
                        $native_form = $native_forms[$form_key];
                        $type = $native_form['type'];

                        $form_type = $native_form['name'];
                        $href = isset($native_form['href']) ? $native_form['href'] : false;

                        if (!empty($href)) {
                            $href .= dig_url_language();
                            $href = apply_filters('digits_page_url', $href, $current_url, $form_type);

                            $href = esc_attr($href);
                            $href = str_replace("&amp;", "&", $href);
                            $this->page_js($elem, $href);
                        } else {
                            $this->popup_js($elem, $type);
                        }
                    } else if ($builder) {
                        $form_info = explode('_', $form_key, 2);
                        if (sizeof($form_info) == 2) {
                            $post_id = $form_info[1];
                            if ($form_info[0] == 'page') {
                                $href = Digits_Pagebuilder_shortcodes::get_url($post_id);
                                $this->page_js($elem, $href);
                            } else {
                                $data_show = Digits_Pagebuilder_shortcodes::popup_id($post_id, true);
                                $this->builder_popup_js($elem, $data_show);
                            }
                        }
                    }
                }
                ?>
            })
        </script>
        <?php
    }

    public function page_js($elem, $href)
    {
        ?>
        jQuery('<?php echo esc_attr($elem); ?>').addClass('digits-login-modal').attr({'href': '<?php echo $href; ?>', 'data-link': 1});
        <?php
    }

    public function popup_js($elem, $type)
    {
        ?>
        jQuery('<?php echo esc_attr($elem); ?>').addClass('digits-login-modal').attr({'type': <?php echo esc_attr($type); ?>, 'href': '#'});
        <?php
    }

    public function builder_popup_js($elem, $data_show)
    {
        ?>
        jQuery('<?php echo esc_attr($elem); ?>').addClass('digits-login-modal').attr({'data-show': '<?php echo esc_attr($data_show); ?>', 'href': '#'});
        <?php
    }
}
