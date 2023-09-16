<?php

if (!defined('ABSPATH')) {
    exit;
}

DigitsThemeEditor::instance();

class DigitsThemeEditor
{
    protected static $_instance = null;
    public $loaded = false;

    public function __construct()
    {
        add_action('wp_ajax_digits_editor_data_save', [$this, 'ajax_save_data']);

        if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'digits_settings') {
            if (!empty($_REQUEST['button-editor'])) {
                add_action('wp_loaded', [$this, 'init']);
            }
        }
        if (!empty($_REQUEST['digits-editor'])) {
            add_action('wp_before_load_template', [$this, 'front_end']);
        }
    }

    public function ajax_save_data()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'digits_editor')) {
            wp_send_json_error(['message' => __('Please try again', 'digits')]);
        }
        if (empty($_REQUEST['editor_data'])) {
            wp_send_json_error(['message' => __('No data found!', 'digits')]);
        }

        update_option('digits_editor_data', $_REQUEST['editor_data'], true);
        wp_send_json_success(['message' => __('Saved', 'digits')]);
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

        if (!is_admin()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $src_url = site_url();

        if (!empty($_REQUEST['url'])) {
            if ($this->is_site_url($_REQUEST['url'])) {
                $src_url = $_REQUEST['url'];
            } else {
                wp_die(__('Invalid URL!', 'digits'));
            }
        }

        $editor_data = stripslashes(get_option('digits_editor_data', '{}'));


        digits_admin_add_scripts();
        digits_add_style();
        $this->enqueue_script();
        $separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

        $src_url = add_query_arg(
            ['digits-editor' => true,
                'digits-editor-mode' => urlencode(wp_create_nonce('digits-editor-mode'))],
            $src_url);
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <meta name='robots' content='noindex,nofollow,noarchive'/>
            <meta name='referrer' content='strict-origin-when-cross-origin'/>
            <title><?php echo __("Digits Editor", "digits") . $separator . get_bloginfo('name', 'display'); ?></title>
            <?php
            wp_print_styles('digits-login-style');
            wp_print_styles('digits-theme-editor');
            ?>
        </head>
        <body>
        <?php
        do_action('editor_footer');

        if (wp_style_is('digits-admin-wizard', 'enqueued')) {
            wp_print_styles('digits-admin-wizard');
        }
        ?>
        <div class="digits-theme-editor" id="digits-theme-editor">
            <div class="digits-theme-editor-external_code digits-theme-editor_bg">
                <div id="digits-editor-ec_tabs" class="digits-editor-ec_tabs">
                    <div data-type="css"
                         class="digits-editor-ec_tab digits-editor-css_tab selected"><?php echo esc_attr__("CSS", "digits"); ?></div>
                    <div data-type="js"
                         class="digits-editor-ec_tab digits-editor-js_tab"><?php echo esc_attr__("JS", "digits"); ?></div>
                </div>
                <div id="digits-editor-code" class="digits-editor-code_wrapper">
                    <textarea id="editor_css" name="css" class="digits-editor-code css selected"></textarea>
                    <textarea id="editor_js" name="js" class="digits-editor-code js" style="display: none;"></textarea>
                </div>
            </div>
            <div class="digits-theme-editor-preview-wrapper">
                <div class="digits-theme-editor_resize digits_editor_transition">
                    <div class="digits-theme-editor_resize-ic"></div>
                </div>
                <iframe
                        onload="digits_editor_iframe_loaded()"
                        referrerpolicy="same-origin"
                        allowpaymentrequest="false"
                        sandbox="allow-same-origin allow-scripts"
                        id="digits-editor-preview" src="<?php echo esc_attr($src_url); ?>"></iframe>
                <div class="digits-theme-editor_resize digits_editor_transition">
                    <div class="digits-theme-editor_resize-ic"></div>
                </div>
            </div>
            <div class="digits-theme-editor_resizing-tooltip">
                <div class="digits-theme-editor_resizing-tooltip-contents">
                    <?php echo esc_attr__("width", "digits"); ?>:&nbsp;<span></span>
                </div>
            </div>

            <div id="digits-editor-selector_list" class="digits-editor-elem-select_bar">
            </div>
            <div id="digits-editor-selector_controls" class="digits-editor-selector_controls">
                <div id="digits-editor-trigger_list" class="digits-editor_control digits-editor_control_list"
                     style="display: none">
                    <div id="digits-editor_native_forms" class="digits-editor_forms_list digits-editor_native_forms">
                        <div class="digits-editor_control_head">
                            <?php echo esc_attr__("Select the Type of Form", "digits"); ?>
                        </div>
                        <?php
                        $builder_active = is_plugin_active('digbuilder/digbuilder.php');

                        $page_list = [];
                        $modal_list = [];
                        if ($builder_active) {
                            $page_list = $this->get_builder_list('page');
                            $modal_list = $this->get_builder_list('modal');
                            if (empty($page_list) && empty($modal_list)) {
                                $builder_active = false;
                            }
                        }
                        $forms = array(
                            'login_register_popup' => __('Login / Signup Popup', 'digits'),
                            'login_popup' => __('Login Popup', 'digits'),
                            'register_popup' => __('Signup Popup', 'digits'),
                            'login_register_page' => __('Login / Signup Page', 'digits'),
                            'login_page' => __('Login Page', 'digits'),
                            'register_page' => __('Signup Page', 'digits'),
                        );
                        if ($builder_active) {
                            $forms['builder'] = 'Builder';
                        }
                        $this->render_selector($forms);
                        ?>
                    </div>
                    <?php
                    if ($builder_active) {
                        ?>
                        <div id="digits-editor_builder_forms"
                             class="digits-editor_forms_list digits-editor_native_forms">
                            <div class="digits-editor_control_head digits-editor_back_form_list">
                                <span class="digits-editor_arrow_ic"></span>
                                <?php echo esc_attr__("Go back", "digits"); ?>
                            </div>
                            <?php
                            if (!empty($modal_list)) {
                                $forms = array(
                                    __('Popup Builder') => 'heading',
                                );
                                $forms = array_merge($forms, $modal_list);
                                $this->render_selector($forms);
                            }

                            if (!empty($page_list)) {
                                $forms = array(
                                    __('Page Builder') => 'heading',
                                );
                                $forms = array_merge($forms, $page_list);
                                $this->render_selector($forms);
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div id="digits-remove_trigger" class="digits-editor_control_remove">
                        <div class="digits-editor_control_remove_ic"></div>
                        <div class="digits-editor_control_remove_text">
                            <?php echo esc_attr__("Remove", "digits"); ?>
                        </div>
                    </div>
                </div>
                <div id="digits-editor-show_trigger_list" class="digits-editor_control">
                    <div class="digits-editor_control_ic digits-editor_trigger_ic"></div>
                    <div class="digits-editor_control_text">
                        <?php echo esc_attr__("Trigger Digits Form", "digits"); ?>
                    </div>
                </div>
                <div id="digits-editor_hide_elem" class="digits-editor_control">
                    <div class="digits-editor_control_ic digits-editor_hide_ic"></div>
                    <div class="digits-editor_control_text">
                        <?php echo esc_attr__("Hide This", "digits"); ?>
                    </div>
                </div>
            </div>

            <div class="digits-theme-editor_bar digits-theme-editor_bg">
                <div id="digits-editor_cursor" class="digits-editor_tool digits_editor_transition">
                    <div class="digits_editor_cursor_selected">
                        <div id="digits_editor_selected_ic" class="digits-editor-selector_ic digits_editor_ic"></div>
                        <div class="digits_editor_cursor-expand"></div>
                    </div>
                    <div class="digits_editor_cursor_type_list digits_editor_transition">
                        <div class="digits_editor_cursor_type_item">
                            <div data-type="selector" class="digits-editor-selector_ic digits_editor_ic"></div>
                            <div class="digits_editor_cursor_type_text">
                                <?php echo esc_attr__("Selector", "digits"); ?>
                            </div>
                        </div>
                        <div class="digits_editor_cursor_type_item-sep">
                            <div></div>
                        </div>
                        <div class="digits_editor_cursor_type_item">
                            <div data-type="cursor" class="digits_editor_cursor_type_ic digits_editor_ic"></div>
                            <div class="digits_editor_cursor_type_text">
                                <?php echo esc_attr__("Cursor", "digits"); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="digits-editor_tool digits-responsive">
                    <div class="digits_editor_responsive_ic digits_editor_ic"></div>
                </div>
                <div class="digits-editor_tool digits-code_editor">
                    <div class="digits_editor_code_ic digits_editor_ic"></div>
                </div>

                <div class="digits_flex_1"></div>

                <div class="digits_editor_footer_action">
                    <form action="<?php echo esc_attr(admin_url('admin-ajax.php')); ?>" method="post">
                        <div id="digits-editor_save" class="digits-editor_tool">
                            <div class="digits_editor_save_ic digits_editor_ic"></div>
                            <div class="digits_editor_tool_text">
                                <?php echo esc_attr__('Save', 'digits'); ?>
                            </div>
                        </div>
                        <input type="hidden" name="editor_data" id="editor_data"
                               value="<?php echo esc_attr($editor_data); ?>"/>
                        <input type="hidden" name="action"
                               value="digits_editor_data_save"/>
                        <?php
                        wp_nonce_field('digits_editor');
                        ?>
                    </form>

                    <?php
                    $settings_url = admin_url('admin.php?page=digits_settings');
                    ?>
                    <div id="digits-editor_close" class="digits-editor_tool"
                         data-link="<?php echo esc_attr($settings_url); ?>">
                        <div class="digits_editor_close_editor_ic digits_editor_ic"></div>
                        <div class="digits_editor_tool_text">
                            <?php echo esc_attr__('Close Editor', 'digits'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        digits_loader();
        wp_print_scripts('digits-login-script');
        wp_print_scripts('digits-theme-editor');

        if (wp_script_is('digits-admin-wizard', 'enqueued')) {
            wp_print_scripts('digits-admin-wizard');
        }
        ?>
        <style>
            .dig_load_overlay {
                display: block;
            }
        </style>
        <script>
            function digits_editor_iframe_loaded() {
                document.querySelector('.dig_load_overlay').style.display = 'none';
            }
        </script>
        </body>
        </html>
        <?php
        die();
    }

    public function get_builder_list($type)
    {
        $pages = digits_pages_list($type);
        $list = array();
        foreach ($pages as $page_key => $page) {
            if ($page['value'] == -1) {
                continue;
            }
            $list[$page_key] = $page['label'];
        }
        return $list;
    }

    public function enqueue_script()
    {
        wp_enqueue_style('digits-theme-editor', get_digits_asset_uri('/admin/assets/css/editor.min.css'), array(), digits_version(), 'all');
        wp_enqueue_script('digits-theme-editor', get_digits_asset_uri('/admin/assets/js/editor.min.js'), array('jquery'), digits_version());
    }

    public function front_end()
    {
        $verify = wp_verify_nonce($_REQUEST['digits-editor-mode'], 'digits-editor-mode');
        if (!$verify) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }
        global $current_user;
        $current_user = null;
        add_filter('determine_current_user', [$this, 'temp_no_user'], 9999);


        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_script']);
    }

    public function temp_no_user($user)
    {
        return 0;
    }

    public function enqueue_frontend_script()
    {

        wp_enqueue_style('digits-theme-frontend-editor', get_digits_asset_uri('/admin/assets/css/frontend-editor.min.css'), array(), digits_version(), 'all');
        wp_enqueue_script('digits-theme-frontend-editor', get_digits_asset_uri('/admin/assets/js/frontend-editor.min.js'), array('jquery'), digits_version());
    }

    public function is_site_url($url)
    {
        $site_url = parse_url(site_url());
        $url = parse_url($url);
        return $site_url['host'] === $url['host'];
    }

    private function render_selector($forms)
    {
        ?>
        <div class="digits-editor_control_list_content">
            <div class="digits-editor_control_body">
                <?php
                foreach ($forms as $form_key => $form_label) {
                    if ($form_label == 'heading') {
                        ?>
                        <div class="digits-editor_control_title"><?php echo esc_attr($form_key); ?></div>
                        <?php
                    } else {
                        $expandable = $form_key == 'builder';
                        $value = $form_key;
                        $id = uniqid();
                        ?>
                        <div class="digits-editor_form_selector" data-value="<?php echo esc_attr($form_key); ?>">
                            <label class="digits-editor_form_label" for="<?php echo esc_attr($id); ?>">
                                <span class="digits-editor_radio">
                                    <span></span>
                                </span>
                                <input type="radio" class="digits_form_trigger"
                                       id="<?php echo esc_attr($id); ?>"
                                    <?php
                                    if ($expandable) echo 'disabled';
                                    ?>
                                       value="<?php echo esc_attr($value); ?>"
                                       name="digits_form_trigger"/>
                                <?php echo $form_label; ?>
                                <?php
                                if ($expandable) {
                                    echo '<div class="digits-editor_arrow_ic"></div>';
                                }
                                ?>
                            </label>
                        </div>
                        <div class="digits-editor_form_sep"></div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

}
