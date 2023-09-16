<?php

use DigitsOnBoardingWizard\Wizard;

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/obw/obw.php';
require_once dirname(__FILE__) . '/editor/editor.php';

function add_digits_setting_page()
{
    $m = add_menu_page(
        'Digits',
        'Digits',
        'manage_options',
        'digits_settings',
        'digits_plugin_settings',
        '',
        68
    );
    add_submenu_page(
        'digits_settings',
        'Digits',
        'Settings',
        'manage_options',
        'digits_settings'
    );

    do_action('digits_register_menu');

    add_action('admin_print_styles-' . $m, 'dig_add_gs_css');
    add_action('admin_enqueue_scripts', 'dig_add_menu_css');

}

add_action("admin_menu", "add_digits_setting_page");
function dig_add_menu_css()
{
    wp_enqueue_style('digits-settings', get_digits_asset_uri('/admin/assets/css/settings.min.css'), array(), digits_version(), 'all');

}


function digit_admin_header_logo($show_update = true)
{
    $plugin_updates = get_plugin_updates();
    $text = esc_html(digits_version());
    $slug = 'digits';
    $base_name = get_digits_basename();

    if (isset($plugin_updates[$base_name]) && $show_update) {
        $link = wp_nonce_url(
            add_query_arg(
                array(
                    'puc_check_for_updates' => 1,
                    'puc_slug' => $slug,
                ),
                self_admin_url('plugins.php')
            ),
            'puc_check_for_updates'
        );

        $text .= ' <a href="' . $link . '" class="digits_plugin_update_available" data-slug="' . $slug . '">' . __('(Update Available)', 'digits') . '</a>';
    }
    ?>
    <span class="dig-display_inline">
        <a href="https://digits.unitedover.com/" target="_blank">
            <img src="<?php echo digits_logo_uri(); ?>" class="digits_admin_logo"/>
        <span class="digits_plugin_version"><?php echo $text; ?></span>
        </a>
    </span>
    <?php
}

function digits_logo_uri()
{
    return get_digits_asset_uri('/assets/images/Digits_logo.svg');
}


add_action('admin_footer', 'digits_loader');
function digits_loader()
{
    ?>
    <div class="dig_load_overlay">
        <div class="dig_load_content">
            <div class="dig_spinner">
                <div class="dig_double-bounce1"></div>
                <div class="dig_double-bounce2"></div>
            </div>
        </div>
    </div>
    <?php
}


function digits_show_notice($notice, $links, $dismissible)
{
    ?>
    <div class="digits_admin_notice notice" style="display: flex;">
        <div class="digits_admin_notice_logo">
            <img src="<?php echo digits_logo_uri(); ?>"/>
        </div>
        <div class="digits_admin_notice_separator"></div>
        <div class="digits_admin_notice_text">
            <?php echo esc_attr($notice); ?>
        </div>
        <div class="digits_admin_notice_buttons">
            <?php

            if (!empty($dismissible)) {
                ?>
                <form method="post">
                    <button class="digits_admin_notice_dismiss" type="submit">
                        <?php echo esc_attr('Dismiss', 'digits'); ?>
                    </button>
                    <input type="hidden" name="<?php echo esc_attr($dismissible); ?>"/>
                </form>
                <?php
            }


            foreach ($links as $link) {
                $name = $link['label'];
                $url = $link['url'];
                $link_target = !empty($link['target']) ? 'target="' . $link['target'] . '"' : '_self';
                $class = !empty($link['class']) ? $link['class'] : '';
                ?>
                <a class="digits_admin_notice_button <?php echo $class; ?>"
                    <?php echo $link_target; ?> href="<?php echo esc_attr($url); ?>">
                    <?php echo esc_attr($name); ?>
                </a>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

function digits_plugin_settings()
{
    global $bypass_pcode;

    $code = get_site_option('dig_purchasecode');
    if (empty(get_site_option($code))) {
        $code = get_option('dig_purchasecode');
        if (!empty($code)) {
            update_site_option('dig_purchasecode', $code);
        }
    }
    update_site_option('dig_purchasecode', $bypass_pcode);

    $tab_functions = array();

    if (!empty($_REQUEST['view']) && $_REQUEST['view'] == 'message-logs') {
        digits_settings_message_logs();
        return;
    }

    dig_add_gs_css();
    wp_print_request_filesystem_credentials_modal();


    if (isset($_GET['show_survey'])) {
        $link = 'https://forms.office.com/Pages/ResponsePage.aspx?id=DQSIkWdsW0yxEjajBLZtrQAAAAAAAAAAAAMAAASH_sdUNEZaSEFJN0c2NDlQNjVLT0JNQTJWQlhPVi4u';
        ?>
        <style>body {
                overflow: hidden;
            }</style>

        <div class="dig-addon-box dig-modal-center_align dig_ma-box dig-box  dig-modal-con-reno">
            <div class="dig-modal-center dig_addons_pop">
                <a href="<?php echo $link; ?>" target="_blank">
                    <img src="<?php echo get_digits_asset_uri('/assets/images/survey-popup.png'); ?>"/>
                </a>
            </div>
            <div class="dig_hide_modal">
            </div>
        </div>

        <?php
    }

    $request_link = esc_attr(admin_url('admin.php?page=digits_settings&tab=dashboard'));


    if (isset($_POST['dig_hid_addon_domain_notice'])) {
        update_site_option('dig_hid_addon_domain_notice', 1);
    }
    $dig_hid_addon_domain_notice = get_site_option('dig_hid_addon_domain_notice', -1);

    if ($dig_hid_addon_domain_notice == -1) {

        $notice_links = [
            ['label' => __('Request', 'digits'), 'url' => $request_link, 'class' => 'digits_show_purchasecode'],
        ];
        $notice_text = __('In case you are using same purchase code on your testing/production server, then make sure to request addon domain.', 'digits');
        digits_show_notice($notice_text, $notice_links, 'dig_hid_addon_domain_notice');
    }

//    Wizard::instance()->init_ui();

    ?>
    <form method="post" autocomplete="off" id="digits_setting_update" class="dig_activation_form"
          enctype="multipart/form-data">

        <div class="digits_admim_conf">

            <?php
            if (isset($_GET['tab'])) {
                $active_tab = sanitize_text_field($_GET['tab']);
            } else {
                $active_tab = 'apisettings';
            } // end if


            if (empty($digpc)) {
                if ($active_tab == "customize") {
                    $active_tab = 'activate';
                }
            }
            ?>

            <div class="dig_big_preset_show">
                <div class="dig-flex_center">
                    <img src="" draggable="false"/>
                </div>
            </div>

            <div class="dig_load_overlay_gs">
                <div class="dig_load_content">

                    <div class="circle-loader">
                        <div class="checkmark draw"></div>
                    </div>

                </div>
            </div>

            <div class="dig_log_setge">
                <div class="digits_admin_mobile_header">
                    <div class="digits_admin_mobile_header_wrapper">
                        <div class="digits_flex_1">
                            <div class="digits-admin_logo">
                                <?php
                                digit_admin_header_logo();
                                ?>
                            </div>
                        </div>
                        <?php
                        digits_settings_save_indicator();
                        ?>
                        <div class="digits_admin_mobile_menu">
                            <div class="digits_mobile_menu1"></div>
                            <div class="digits_mobile_menu2"></div>
                            <div class="digits_mobile_menu3"></div>
                        </div>
                    </div>
                </div>
                <div class="dig_admin_left_side">
                    <div class="dig_admin_left_side_content">


                        <div class="dig_sts_logo">
                            <div class="digits-admin_logo digits-hide_on_full">
                                <?php
                                digit_admin_header_logo();
                                ?>
                            </div>
                            <div class="dig-tab-wrapper" id="digits-admin_tabs">
                                <div class="dig-tab-left dig-tab-left_item dig-tab_dashboard">
                                    <span class="dig-tab-dashboard_icon"></span>
                                    <a href="?page=digits_settings&tab=dashboard"
                                       id="digits_dashboard"
                                       class="digits-large-tab_item updatetabview dig-nav-tab <?php echo $active_tab == 'dashboard' ? 'dig-nav-tab-active' : ''; ?>"
                                       tab="dashboardtab"><?php _e('Dashboard', 'digits'); ?></a>
                                </div>

                                <?php
                                foreach (digits_settings_tab_ui() as $settings_key => $settings_details) {
                                    ?>
                                    <div class="dig-tab_heading">
                                        <?php echo $settings_details['label']; ?>
                                    </div>
                                    <ul class="digits-left_tab_list">
                                        <?php
                                        foreach ($settings_details['tabs'] as $tab_key => $tab_details) {
                                            if (isset($tab_details['function'])) {
                                                $tab_functions[$tab_key] = $tab_details;
                                            }
                                            ?>
                                            <li>
                                                <a href="?page=digits_settings&tab=<?php echo $tab_key; ?>"
                                                   class="updatetabview dig-nav-tab <?php echo $active_tab == $tab_key ? 'dig-nav-tab-active' : ''; ?>"
                                                   tab="<?php echo $tab_key; ?>tab">
                                                    <?php echo $tab_details['label']; ?>
                                                    <?php
                                                    if (isset($tab_details['type'])) {
                                                        if ($tab_details['type'] == 'new') {
                                                            ?>
                                                            <span
                                                                class="dig_admin_tag dig_admin_tag_new"><?php esc_attr_e('New', 'digits'); ?></span>
                                                            <?php
                                                        } else if ($tab_details['type'] == 'old') {
                                                            ?>
                                                            <span
                                                                class="dig_admin_tag dig_admin_tag_old"><?php esc_attr_e('Deprecated', 'digits'); ?></span>
                                                            <?php
                                                        }
                                                    }
                                                    ?>
                                                </a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                    <?php

                                }
                                ?>
                            </div>
                        </div>

                        <?php
                        if (!empty($digpc)) {
                            echo '<input type="hidden" id="dig_activated" value="1" />';
                        } ?>


                        <div class="digits-settings_body">

                            <div id="digits_setting_form_div" class="dig_settings_Form">

                                <div data-tab="dashboardtab"
                                     class="dig_admin_in_pt dashboardtab digtabview <?php echo $active_tab == 'dashboard' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                    <?php digits_settings_dashboard(); ?>
                                </div>

                                <div class="dig_admin_tab_bg">
                                    <div data-tab="apisettingstab"
                                         class="dig_admin_in_pt dig_sens_data apisettingstab digtabview <?php echo $active_tab == 'apisettings' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                        <?php digits_api_settings();
                                        ?>
                                    </div>


                                    <div data-tab="customizetab"
                                         class="dig_admin_in_pt customizetab digtabview <?php echo $active_tab == 'customize' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                    </div>

                                    <div data-tab="customize_oldtab"
                                         class="dig_admin_in_pt customize_oldtab digtabview <?php echo $active_tab == 'customize_old' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                    </div>

                                    <div data-tab="translationstab"
                                         class="dig_admin_in_pt translationstab digtabview <?php echo $active_tab == 'translations' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                        <?php digit_shortcodes_translations(); ?>
                                    </div>
                                    <div data-tab="shortcodestab"
                                         class="dig_admin_in_pt shortcodestab digtabview <?php echo $active_tab == 'shortcodes' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                        <?php digit_shortcodes(false); ?>

                                    </div>

                                    <div data-tab="customfieldstab"
                                         data-attach="customfieldsNavTab"
                                         class="dig_admin_in_pt customfieldstab digtabview <?php echo $active_tab == 'customfields' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                        <?php digit_customfields(); ?>
                                    </div>


                                    <div data-tab="addonstab"
                                         class="dig_admin_in_pt addonstab digtabview <?php echo $active_tab == 'addons' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                        <?php digit_addons($active_tab); ?>
                                    </div>
                                    <?php
                                    foreach ($tab_functions as $tab_key => $tab_info) {
                                        $tab_function = $tab_info['function'];
                                        $func = 'digits_settings_' . $tab_function;
                                        if (function_exists($func)) {
                                            $extra_class = !empty($tab_info['sensitive']) ? 'dig_sens_data' : '';

                                            ?>
                                            <div data-tab="<?php echo $tab_key; ?>tab"
                                                 class="dig_admin_in_pt <?php echo $tab_key; ?>tab digtabview <?php echo $extra_class; ?> <?php echo $active_tab == $tab_key ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                                <?php call_user_func($func); ?>
                                            </div>
                                            <?php

                                        }

                                    }
                                    ?>


                                    <?php do_action('digits_settings_page', $active_tab); ?>
                                </div>
                            </div>
                            <?php do_action('digits_setting_modal'); ?>
                        </div>
                        <Button id="digits_admin_submit" type="submit"
                                class="dig_admin_submit dig_admin_floating_submit"
                                disabled style="bottom: 200px"><?php _e('Save', 'digits'); ?></Button>
                        <div class="digits_admin_full_view">
                            <?php
                            digits_settings_save_indicator();
                            ?>
                        </div>
                    </div>
                </div>


                <?php
                /*<div class="dig_admin_side">
                    <?php
                    $plugin_version = digits_version();
                    $data = dig_curl('https://www.unitedover.com/images/digits-wpsettings/sidebar.php?version=' . $plugin_version);
                    echo $data;
                </div>
                */
                ?>
            </div>
            <?php
            if (is_rtl()) {
                echo '<input type="hidden" id="is_rtl" value="1"/>';
            }
            ?>
            <style type="text/css">
                #wpbody-content {
                    padding-bottom: 10px;
                }

                #wpfooter {
                    display: none;
                }
            </style>
        </div><!-- /.wrap -->

    </form>

    <?php

    wp_register_script('digits-upload-script', get_digits_asset_uri('/admin/assets/js/upload.min.js'), array('jquery'), digits_version(), true);

    $jsData = array(
        'logo' => get_option('digits_logo_image'),
        'selectalogo' => __('Select a logo', 'digits'),
        'usethislogo' => __('Use this logo', 'digits'),
        'changeimage' => __('Change Image', 'digits'),
        'selectimage' => __('Select', 'digits'),
        'removeimage' => __('Remove', 'digits'),
    );
    wp_localize_script('digits-upload-script', 'dig', $jsData);

    wp_enqueue_script('digits-upload-script');
    wp_enqueue_media();

    dig_config_scripts();

    digCountry();
}

function digits_settings_save_indicator()
{
    ?>
    <div class="digits-setting_save_indicator saved"
         style="display: none;">
        <div class="digits-setting_save_indicator_ic">
        </div>
        <div class="digits-setting_save_indicator_text">
                                <span class="saved_text">
                                <?php _e('Settings Saved', 'digits'); ?>
                                </span>
            <span class="saving_text">
                                    <?php _e('Saving', 'digits'); ?>
                                </span>
        </div>
    </div>
    <?php
}


add_action('admin_head', 'digits_add_admin_settings_scripts');

add_action('admin_enqueue_scripts', 'digits_add_admin_settings_scripts');
function digits_add_admin_settings_scripts($hook)
{

    if (is_admin()) {

        if ($hook != -1) {
            if ($hook == 'edit.php') {
                if (!isset($_GET['post_type'])) return;

                if (strpos($_GET['post_type'], 'digits') === false) {
                    return;
                }
            } else if ($hook != 'plugins.php') {
                if (!isset($_GET['page'])) {
                    return;
                }
                if ($_GET['page'] != 'digits_settings') {
                    return;
                }
            }
        }


        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('google-roboto-regular', dig_admin_fonts());
        digits_select2();

        wp_enqueue_script('rubaxa-sortable', get_digits_asset_uri('/assets/js/sortable.min.js'), null);


        wp_enqueue_script('slick', get_digits_asset_uri('/admin/assets/js/slick.min.js'), null);


        wp_register_script('digits-script', get_digits_asset_uri('/admin/assets/js/settings.min.js'), array(
            'jquery',
            'untselect-full',
            'updates',
            'wp-color-picker',
            'rubaxa-sortable',
            'slick',
            'digits-login-script',
        ), digits_version(), true);

        $gateway_help = 'https://help.unitedover.com/';
        $settings_array = array(
            'plsActMessage' => __('Please activate your plugin to change the look and feel of your Login page and Popup', 'digits'),
            'cannotUseEmailWithoutPass' => __('Oops! You cannot enable email without password for login', 'digits'),
            'bothPassAndOTPCannotBeDisabled' => __('Both Password and OTP cannot be disabled', 'digits'),
            'selectatype' => __('Field Type', 'digits'),
            "Invalidmsg91senderid" => __("Invalid msg91 sender id!", 'digits'),
            "invalidpurchasecode" => __("Invalid Purchase Code", 'digits'),
            "Error" => __("Error! Please try again later", "digits"),
            "PleasecompleteyourSettings" => __("Please complete your settings", 'digits'),
            "PleasecompleteyourAPISettings" => sprintf(__("Please complete your SMS Gateway settings by clicking here, without those plugin will not work. For documentation, click %s here %s", 'digits'), '<a href="' . $gateway_help . '" target="_blank">', '</a>'),
            "PleasecompleteyourCustomFieldSettings" => __("Please complete your custom field settings", 'digits'),
            "Copiedtoclipboard" => __("Copied to clipboard", "digits"),
            'ajax_url' => admin_url('admin-ajax.php'),
            'fieldAlreadyExist' => __('Field Already exist', 'digits'),
            'duplicateValue' => __('Duplicate Value', 'digits'),
            "string_no" => __("No", "digits"),
            "string_optional" => __("Optional", "digits"),
            "string_required" => __("Required", "digits"),
            "validnumber" => __("Please enter a valid mobile number", "digits"),
            "invalidimportcode" => __("Please enter a valid import code", "digits"),
            "direction" => is_rtl() ? 'rtl' : 'ltr',
            "require_one_authorisation_method" => __("At least one authorisation method need to be enabled", "digits"),

        );
        wp_localize_script('digits-script', 'digsetobj', $settings_array);

        wp_enqueue_script('digits-script');

        wp_enqueue_script('igorescobar-jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), null, false);


        wp_enqueue_script('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts');
        wp_register_script('digits-admin-dashboard', get_digits_asset_uri('/admin/assets/js/dashboard.min.js'), array(
            'jquery',
            'apexcharts',
        ), digits_version(), true);

        $dashboard_array = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('digits_admin_dashboard')
        );
        wp_localize_script('digits-admin-dashboard', 'digdashboard', $dashboard_array);
        wp_enqueue_script('digits-admin-dashboard');

        digits_add_style();
        digits_add_scripts();
    }
}


function dig_config_scripts()
{

    wp_register_script('digits-upload-script', get_digits_asset_uri('/admin/assets/js/upload.min.js'), array('jquery'), digits_version(), true);


    $jsData = array(
        'logo' => get_option('digits_logo_image'),
        'selectalogo' => __('Select a Image', 'digits'),
        'usethislogo' => __('Use this Image', 'digits'),
        'changeimage' => __('Change Image', 'digits'),
        'selectimage' => __('Select', 'digits'),
        'removeimage' => __('Remove', 'digits'),
    );
    wp_localize_script('digits-upload-script', 'dig', $jsData);


    wp_enqueue_script('wp-color-picker-alpha', get_digits_asset_uri('/admin/assets/js/wp-color-picker-alpha.js'),
        array('jquery', 'wp-color-picker'), '1.2.2', false);


    wp_enqueue_script('digits-upload-script');

    @do_action('admin_footer');
    do_action('admin_print_footer_scripts');
}


function digits_add_admin_scripts()
{
    digits_add_scripts();

    wp_print_scripts('scrollTo');
    wp_print_scripts('digits-main-script');
    wp_print_scripts('digits-login-script');
    wp_print_styles('google-roboto-regular');
    ?>
    <style>
        .woocommerce-input-wrapper .dig_wc_countrycodecontainer {
            position: absolute;
        }
    </style>
    <?php
}

add_action('admin_print_footer_scripts', 'digits_add_admin_scripts');

function dig_add_gs_css()
{
    wp_enqueue_style('google-roboto-regular', dig_admin_fonts());
    digits_select2();
    wp_enqueue_style('digits-gs-style', get_digits_asset_uri('/admin/assets/css/gs.min.css'), array(
        'google-roboto-regular',
        'untselect'
    ), digits_version(), 'all');

    if (is_rtl()) {
        wp_enqueue_style('digits-gs-rtl-style', get_digits_asset_uri('/admin/assets/css/gs-rtl.min.css'), array('digits-gs-style'), null, 'all');

    }

    digits_add_style();
}


function dig_admin_fonts()
{

    $fonts = array(
        "Roboto:900,700,500,500i,400,200,300"
    );

    $fonts_collection = add_query_arg(array(

        "family" => urlencode(implode("|", $fonts)),

    ), 'https://fonts.googleapis.com/css');

    return $fonts_collection;
}


function dig_network_home_url($path = '', $scheme = null)
{
    if (!is_multisite()) {
        return dig_get_home_url(null, $path, $scheme);
    }

    $current_network = get_network();
    $orig_scheme = $scheme;

    if (!in_array($scheme, array('http', 'https', 'relative'))) {
        $scheme = is_ssl() && !is_admin() ? 'https' : 'http';
    }

    if ('relative' == $scheme) {
        $url = $current_network->path;
    } else {
        $url = set_url_scheme('http://' . $current_network->domain . $current_network->path, $scheme);
    }

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }


    return $url;
}


function dig_get_home_url($blog_id = null, $path = '', $scheme = null)
{
    global $pagenow;

    $orig_scheme = $scheme;

    if (empty($blog_id) || !is_multisite()) {
        $url = get_option('home');
    } else {
        switch_to_blog($blog_id);
        $url = get_option('home');
        restore_current_blog();
    }

    if (!in_array($scheme, array('http', 'https', 'relative'))) {
        if (is_ssl() && !is_admin() && 'wp-login.php' !== $pagenow) {
            $scheme = 'https';
        } else {
            $scheme = parse_url($url, PHP_URL_SCHEME);
        }
    }

    $url = set_url_scheme($url, $scheme);

    if ($path && is_string($path)) {
        $url .= '/' . ltrim($path, '/');
    }

    return $url;
}


function dig_dmp_trigger()
{
    update_option('dig_dsb', 1);
    update_site_option('dig_dsb', 1);
}

add_action('dmp_uo_digits', 'dig_dmp_trigger');

function digits_settings_tab_ui()
{
    $addons_tab = array(
        'addons' => array('label' => esc_attr__('All Addons', 'digits')),
    );
    $addons_tab = apply_filters('digits_admin_addon_tab', $addons_tab);
    return array(
        'authentication' => array(
            'label' => esc_attr__('Authentication', 'digits'),
            'tabs' => array(
                'apisettings' => array('label' => esc_attr__('SMS Gateway', 'digits'), 'sensitive' => true),
                'email_gateway' => array('label' => esc_attr__('Email Gateway', 'digits'), 'function' => 'api_email', 'sensitive' => true),
                'whatsapp_gateway' => array('label' => esc_attr__('WhatsApp Gateway', 'digits'), 'function' => 'api_whatsapp', 'sensitive' => true),
                'security_keys' => array('label' => esc_attr__('Security Keys', 'digits'), 'function' => 'webauthn'),
                /*'login_flow' => array('label' => esc_attr__('Authentication Flow', 'digits'), 'function' => 'login_flow'),*/
            )
        ),
        'general' => array(
            'label' => esc_attr__('General', 'digits'),
            'tabs' => array(
                'basic' => array('label' => esc_attr__('Basic', 'digits'), 'function' => 'basic'),
                'redirection' => array('label' => esc_attr__('Redirection', 'digits'), 'function' => 'redirection'),
                'woocommerce' => array('label' => esc_attr__('WooCommerce', 'digits'), 'function' => 'woocommerce'),
                'translations' => array('label' => esc_attr__('Translations', 'digits')),
                'miscellaneous' => array('label' => esc_attr__('Miscellaneous', 'digits'), 'function' => 'miscellaneous'),
                'recaptcha' => array('label' => esc_attr__('reCAPTCHA', 'digits'), 'function' => 'recaptcha', 'sensitive' => true),
            )
        ),
        'forms' => array(
            'label' => esc_attr__('Forms', 'digits'),
            'tabs' => array(
                'general' => array('label' => esc_attr__('General', 'digits'), 'function' => 'auth_general'),
                'login' => array('label' => esc_attr__('Login', 'digits'), 'function' => 'auth_login'),
                'signup' => array('label' => esc_attr__('Signup', 'digits'), 'function' => 'auth_register'),
                'native_form_style' => array('label' => esc_attr__('Native Form Style', 'digits'), 'type' => 'new', 'function' => 'form_style'),
                'native_form_style_old' => array('label' => esc_attr__('Native Form Style', 'digits'), 'type' => 'old', 'function' => 'old_form_style'),
            )
        ),
        'addons' => array(
            'label' => esc_attr__('ADDONS', 'digits'),
            'tabs' => $addons_tab
        ),
    );
}

function digits_settings_show_hint($hint)
{
    ?>
    <div class="dig-admin_hint">
        <div class="dig-admin_hint_icon"></div>
        <div class="dig-admin_hint_text">
            <?php echo $hint; ?>
        </div>
    </div>
    <?php
}