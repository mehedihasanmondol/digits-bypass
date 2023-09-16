<?php

namespace DigitsOnBoardingWizard;

if (!defined('ABSPATH')) {
    exit;
}

Wizard::instance();

class Wizard
{
    protected static $_instance = null;
    public $loaded = false;

    public function __construct()
    {
        add_action('wp_ajax_digits_save_wizard_state', [$this, 'save_state']);
        add_action('admin_footer', [$this, 'admin_footer']);
        add_action('editor_footer', [$this, 'admin_footer']);
    }

    public function save_state()
    {
        if (!current_user_can('manage_options')) {
            die();
        }

        if (!wp_verify_nonce($_REQUEST['nonce'], 'untdor_obw')) {
            die();
        }

        if (isset($_REQUEST['finish'])) {
            delete_site_option('digits_configuration_wizard_history');
            delete_site_option('dig_resume_configuration_wizard');
            update_site_option('dig_show_configuration_wizard', 0);
        } else {
            if (isset($_REQUEST['wizard_state'])) {
                $wizard_state = $_REQUEST['wizard_state'];
                update_site_option('digits_configuration_wizard_history', $wizard_state);
            }
            if (isset($_REQUEST['skip_for_now'])) {
                update_site_option('dig_resume_configuration_wizard', 1);
            }
        }
        wp_send_json_success();
    }

    public function admin_footer()
    {
        if (empty($_REQUEST['digits_nonce']) || empty($_REQUEST['resume_configuration_wizard'])) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['digits_nonce'], 'digits_onboard_wizard')) {
            return;
        }
        $this->init_ui();
    }

    public function show_wizard()
    {

        if (wp_is_mobile()) {
            return false;
        }

        $resume = get_site_option('dig_resume_configuration_wizard', 0);

        if (isset($_GET['resume_configuration_wizard'])) {
            return true;
        } else if ($resume == 1) {
            return false;
        }

        return get_site_option('dig_show_configuration_wizard', 1) == 1;
    }

    public function init_ui()
    {
        if (!$this->show_wizard() || $this->loaded) {
            return;
        }
        $this->loaded = true;
        $this->load_ui();
        $this->enqueue_scripts();
        $this->enqueue_styles();
    }

    public function wizard_steps()
    {
        $find_code_link = 'https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code';

        $builder_buttons = [
            [
                'label' => __('How to use Builder', 'digits'),
                'href' => 'https://help.unitedover.com/digits/knowledgebase/builder/'
            ],
            [
                'label' => __('How to use Elementor', 'digits'),
                'href' => 'https://elementor.com/blog/what-is-elementor-for-wordpress/'
            ]
        ];
        $steps = [
            'welcome_screen' => [
                'renderer' => 'welcome_screen',
                'default' => true,
                'show_next' => true,
            ],
            'activate_plugin' => [
                'renderer' => 'activate_plugin',
                'hint_text' => sprintf(__('%sClick here%s to see how to find the purchase code', 'digits'), '<a href="' . esc_attr($find_code_link) . '" target="_blank">', '</a>'),
                'show_next' => true
            ],
            'gateway_selector' => [
                'title' => __('What do you wanna use?', 'digits'),
                'buttons' => [
                    [
                        'label' => __('Send OTP SMS for <span>Free</span>', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'firebase_gateway',
                    ],
                    [
                        'label' => __('I want to use <span>Paid</span> OTP SMS', 'digits'),
                        'next_step' => 'paid_gateway',
                    ]
                ],
                'layout' => 'buttons',
                'hint_text' => __('95% of our users use <span>Free SMS Gateway</span> as it meets their needs', 'digits'),
                'show_next' => false,
            ],
            'firebase_gateway' => [
                'title' => __('Setup Firebase Gateway on this Page', 'digits'),
                'buttons' => [
                    [
                        'label' => __('Documentation', 'digits'),
                        'href' => 'https://help.unitedover.com/digits/kb/firebase-setup-instructions/'
                    ],
                ],
                'layout' => 'bottom_view',
                'switch_to_tab' => 'apisettingstab',
                'show_next' => true,
                'next_step' => 'use_form_type',
            ],
            'paid_gateway' => [
                'title' => __('Setup Your SMS Gateway Here.', 'digits'),
                'desc' => __('The values asked here will be provided by your gateway. If you need help in finding those, please get in touch with your gateway support.', 'digits'),
                'layout' => 'bottom_view',
                'hint_text' => __('<span>SMS Gateway</span> is a service which is required to send SMS messages', 'digits'),
                'switch_to_tab' => 'apisettingstab',
                'show_next' => true,
                'next_step' => 'use_form_type',
            ],
            'use_form_type' => [
                'title' => __('What type of forms do you want to use?', 'digits'),
                'buttons' => [
                    [
                        'label' => __('I want to use <span>Digits Forms</span>', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'use_digits_form_type',
                    ],
                    [
                        'label' => __('I want to use my <span>Existing Forms</span>', 'digits'),
                        'next_step' => 'use_existing_form_type',
                    ]
                ],
                'hint_text' => __('Please use <span>Digits Forms</span> to experience enhanced security and seamless experience', 'digits'),
                'layout' => 'buttons',
                'show_next' => false
            ],
            'use_digits_form_type' => [
                'title' => __('What type of forms do you want to use?', 'digits'),
                'buttons' => [
                    [
                        'label' => __('I will <span>use Drag and Drop Builder</span>', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'digits_install_form_builder',
                    ],
                    [
                        'label' => __('<span>I don\'t</span> want to <span>use Builder</span>', 'digits'),
                        'next_step' => 'digits_use_without_builder',
                    ]
                ],
                'hint_text' => __('You can design your forms visually with our <span>Drag and Drop Builder</span>', 'digits'),
                'layout' => 'buttons',
                'show_next' => false
            ],
            'use_existing_form_type' => [
                'renderer' => 'use_existing_form',
                'next_step' => 'digits_finish_configuration_wizard',
            ],
            'digits_use_without_builder' => [
                'title' => __('Setup Your Forms Here', 'digits'),
                'desc' => __('After setting up your Signup form you can navigate from left hand pane to setup your Login Form and even style it accordingly.', 'digits'),
                'layout' => 'bottom_view',
                'switch_to_tab' => 'signuptab',
                'show_next' => true,
                'next_step' => 'digits_setup_form_button',
            ],
            'digits_install_form_builder' => [
                'renderer' => 'digits_install_plugins',
                'next_step' => 'digits_use_form_builder',
                'show_next' => true,
            ],
            'digits_use_form_builder' => [
                'title' => __('What do you want to create?', 'digits'),
                'buttons' => [
                    [
                        'label' => __('Create a <span>Popup / Modal Form</span>', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'digits_builder_create_popup_form',
                    ],
                    [
                        'label' => __('Create a <span>Page Form</span>', 'digits'),
                        'next_step' => 'digits_builder_create_page_form',
                    ]
                ],
                'layout' => 'buttons',
                'show_next' => false
            ],
            'digits_builder_create_popup_form' => [
                'title' => __('Create a New Popup on this Page', 'digits'),
                'buttons' => $builder_buttons,
                'layout' => 'bottom_view',
                'show_next' => true,
                'next_step' => 'digits_setup_form_button',
                'navigate_to' => $this->create_navigation_url(admin_url('edit.php?post_type=digits-forms-popup')),
            ],
            'digits_builder_create_page_form' => [
                'title' => __('Create a New Page on this Page', 'digits'),
                'buttons' => $builder_buttons,
                'layout' => 'bottom_view',
                'show_next' => true,
                'next_step' => 'digits_setup_form_button',
                'navigate_to' => $this->create_navigation_url(admin_url('edit.php?post_type=digits-forms-page')),
            ],
            'digits_setup_form_button' => [
                'title' => __('Now that you have your form created, its time to link it to your site so that people can use it.', 'digits'),
                'buttons' => [
                    [
                        'label' => __('Change existing button', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'digits_change_existing_button',
                    ],
                    [
                        'label' => __('Can\'t change / Don\'t have existing button', 'digits'),
                        'next_step' => 'digits_dont_change_existing_button',
                    ]
                ],
                'layout' => 'buttons',
                'hint_text' => __('<span>Change existing button</span> If you already have a button which on your site for login and signup', 'digits'),
                'show_next' => false,
            ],
            'digits_change_existing_button' => [
                'title' => __('How can it be changed?', 'digits'),
                'buttons' => [
                    [
                        'label' => __('I don\'t know how to change it', 'digits'),
                        'primary_color' => true,
                        'next_step' => 'digits_edit_using_editor',
                    ],
                    [
                        'label' => __('It can be changed from Menu Items', 'digits'),
                        'next_step' => 'digits_edit_menu_item',
                    ],
                    [
                        'label' => __('Have other options to change it', 'digits'),
                        'next_step' => 'digits_have_other_options_to_change',
                    ]
                ],
                'layout' => 'buttons',
                'show_next' => false,
            ],
            'digits_dont_change_existing_button' => [
                'title' => __('If you don’t have an existing button then the easiest option is to create a menu item on your site which triggers login or signup form for User', 'digits') . '<br /><br />' . __('Or if you can edit your website header then you can create a button and use the below documentations to link the button.', 'digits'),
                'buttons' => [
                    [
                        'label' => __('How to use Login and Registration Form', 'digits'),
                        'primary_color' => true,
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-use-modal-login-form/',
                        'button_type' => 'wrap_content',
                    ],
                    [
                        'label' => __('How To Link Login Page or Modal', 'digits'),
                        'primary_color' => true,
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-change-link-of-existing-button-to-digits-modal-or-page/',
                        'button_type' => 'wrap_content',
                    ]
                ],
                'offset' => 32,
                'layout' => 'informational',
                'show_next' => true,
                'next_step' => 'digits_finish_configuration_wizard',
            ],
            'digits_edit_using_editor' => [
                'title' => __('Edit existing button using our Button Editor', 'digits'),
                'desc' => __('Our button editor will help you to edit your existing button and make it trigger our page or popup form without the need of any development or coding knowledge.', 'digits'),
                'layout' => 'bottom_view',
                'show_next' => true,
                'next_step' => 'use_form_type',
                'request' => 'button-editor',
                'navigate_to' => $this->create_navigation_url(admin_url('admin.php?page=digits_settings&button-editor=true')),
            ],
            'digits_edit_menu_item' => [
                'title' => __('Edit existing button menu item', 'digits'),
                'buttons' => [
                    [
                        'label' => __('Documentation', 'digits'),
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-use-modal-login-form/'
                    ],
                ],
                'layout' => 'bottom_view',
                'show_next' => true,
                'next_step' => 'use_form_type',
                'navigate_to' => $this->create_navigation_url(admin_url('nav-menus.php')),
            ],
            'digits_have_other_options_to_change' => [
                'title' => __('If your theme has other options to change it then please do the needful changes or else you can go through the below documentations.', 'digits'),
                'buttons' => [
                    [
                        'label' => __('How to change the link of an existing button', 'digits'),
                        'primary_color' => true,
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-change-link-of-existing-button-to-digits-modal-or-page/',
                        'button_type' => 'wrap_content',
                    ],
                    [
                        'label' => __('How To Link Login Page or Modal', 'digits'),
                        'primary_color' => true,
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-change-link-of-existing-button-to-digits-modal-or-page/',
                        'button_type' => 'wrap_content',
                    ],
                    [
                        'label' => __('How To Trigger Modal From A Custom Button', 'digits'),
                        'primary_color' => true,
                        'href' => 'https://help.unitedover.com/digits/kb/how-to-trigger-modal-from-a-custom-button/',
                        'button_type' => 'wrap_content',
                    ]
                ],
                'offset' => 12,
                'layout' => 'informational',
                'show_next' => true,
                'next_step' => 'digits_finish_configuration_wizard',
            ],
            'digits_finish_configuration_wizard' => [
                'renderer' => 'configuration_wizard_feedback',
            ]

        ];
        return $steps;
    }

    public function create_navigation_url($url)
    {
        return add_query_arg([
            'resume_configuration_wizard' => 1,
            'digits_nonce' => wp_create_nonce('digits_onboard_wizard')
        ], $url);
    }

    public function load_ui()
    {
        $step_history = get_site_option('digits_configuration_wizard_history', '');
        $step_history = array_unique(explode(",", $step_history));

        ?>
        <div style="display: none">
            <div class="untdor_obw untdor_obw_hide" id="untdor_obw"
                 data-nonce="<?php echo wp_create_nonce('untdor_obw'); ?>">
                <div class="untdor_obw_full_screen_overlay untdor_transition"></div>
                <div id="untdor_wizard_box" class="untdor_wizard_box untdor_wizard_box_full untdor_transition">
                    <div class="untdor_wizard_box_bg untdor_transition"></div>
                    <div class="untdor_wizard_additional_box_text untdor_wizard_additional_box_text_skip untdor_wizard_skip_for_now">
                        <?php
                        esc_attr_e('skip for now (you can return back later)', 'digits');
                        ?>
                    </div>
                    <div class="untdor_wizard_main">

                        <div class="untdor_wizard_back untdor_transition" data-back="1">
                            <div class="untdor_wizard_back_arrow_ic untdor_wizard_arrow_ic untdor_wizard_arrow_dark_color"></div>
                        </div>

                        <div id="untdor_wizard_contents" class="untdor_wizard_contents">
                            <?php $this->render_steps($step_history); ?>
                        </div>
                        <div class="untdor_wizard_footer">
                            <div id="untdor_wizard_next_box" class="untdor_wizard_next_box untdor_transition">
                                <div class="untdor_wizard_back_forward_btn">
                                    <div class="untdor_wizard_next_btn untdor_wizard_next_btn_medium untdor_wizard_next_btn_primary"
                                         data-back="1">
                                        <div class="untdor_wizard_arrow_ic untdor_wizard_arrow_medium untdor_wizard_arrow_primary_color"></div>
                                    </div>
                                    <div class="untdor_wizard_next_btn untdor_wizard_next_btn_big untdor_wizard_next_btn_dark">
                                        <div class="untdor_wizard_arrow_ic untdor_wizard_arrow_big untdor_wizard_arrow_dark_color"></div>
                                    </div>
                                </div>
                                <div class="untdor_wizard_next_wrapper untdor_wizard_next_btn untdor_wizard_next_btn_big untdor_wizard_next_btn_default">
                                    <div class="untdor_wizard_next_step_ic untdor_wizard_arrow_ic untdor_wizard_arrow_big untdor_wizard_arrow_primary_color"></div>
                                </div>

                            </div>
                            <div id="untdor_wizard_footer_action" class="untdor_transition">
                                <div class="untdor_minimize_expand_wizard untdor_minimize_wizard_ic untdor_transition"></div>
                                <div id="untdor_wizard_hint_box" class="untdor_wizard_hint_box untdor_transition">
                                    <div class="untdor_wizard_hint_box_text"></div>
                                    <div class="untdor_hint_ic"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="digits_obw_step_history" id="digits_obw_step_history"
                           value="<?php echo esc_attr(implode(",", $step_history)); ?>"/>
                    <div class="untdor_wizard_additional_box_text untdor_wizard_additional_box_text_about">
                        <?php
                        esc_attr_e('Digits Onboarding Wizard', 'digits');
                        ?>
                    </div>
                </div>

                <div class="untdor_expand_wizard untdor_minimize_expand_wizard untdor_transition">
                    <div class="untdor_expand_wizard_ic"></div>
                    <div class="untdor_expand_wizard_text">
                        <?php
                        esc_attr_e('Expand Onboarding Wizard', 'digits');
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_steps($step_history)
    {
        $active_tab_index = 0;

        if (!empty($step_history)) {
            $active_tab_index = end($step_history);
        }
        if (!empty($_REQUEST['wizard_step'])) {
            $active_tab_index = absint($_REQUEST['wizard_step']);
        }

        $redirect_to_digits_settings = false;
        if (empty($_REQUEST['page']) || $_REQUEST['page'] != 'digits_settings' || !empty($_REQUEST['button-editor'])) {
            $redirect_to_digits_settings = true;
        }

        $wizard_steps = $this->wizard_steps();
        $i = 0;
        foreach ($wizard_steps as $step_key => $wizard_step) {
            $extra_class = '';
            $attrs = '';
            $step_id = $this->format_id($step_key);
            if ((isset($wizard_step['default']) && empty($active_tab_index)) || $active_tab_index == $i) {
                $extra_class = ' active_step';
            }

            if ($redirect_to_digits_settings && empty($wizard_step['navigate_to'])) {
                $wizard_step['navigate_to'] = $this->create_navigation_url(admin_url('admin.php?page=digits_settings&tab=dashboard'));
            }

            if (!empty($wizard_step['navigate_to'])) {
                $attrs .= ' data-switch-to-tab="' . esc_attr($wizard_step['navigate_to']) . '" data-navigate="1"';
            } else if (!empty($wizard_step['switch_to_tab'])) {
                $attrs .= ' data-switch-to-tab="' . esc_attr($wizard_step['switch_to_tab']) . '" ';
            }

            ?>
            <div id="<?php echo $step_id; ?>"
                 class="untdor_wizard_step <?php echo $extra_class; ?>" <?php echo $attrs; ?>
            >
                <?php
                if (isset($wizard_step['renderer'])) {
                    $renderer = $wizard_step['renderer'];
                    $this->$renderer();
                } else {
                    $this->render_wizard_step($wizard_step);
                }

                if (!empty($wizard_step['next_step'])) {
                    echo '<input type="hidden" class="next_step_view" value="' . $this->format_id($wizard_step['next_step']) . '"/>';
                }

                if (!empty($wizard_step['hint_text'])) {
                    echo '<div class="untdor_step_hint">' . $wizard_step['hint_text'] . '</div>';
                }

                ?>
            </div>
            <?php
            $i++;
        }
    }

    public function welcome_screen()
    {
        ?>
        <div class="untdor_wizard_center">
            <div class="untdor_wizard_heading">
                <?php
                esc_attr_e('Welcome', 'digits');
                ?>
            </div>
            <div class="untdor_wizard_heading_desc">
                <?php
                esc_attr_e('This wizard will guide you through the setup process of this plugin', 'digits');
                ?>
            </div>
        </div>
        <?php
    }

    public function activate_plugin()
    {
        $code = dig_get_option('dig_purchasecode');
        $license_type = dig_get_option('dig_license_type', 1);
        ?>
        <form autocomplete="off" class="wiz_process_purchase_code">
            <div class="untdor_wizard_center untdor_wizard_fields">
                <div class="untdor_wizard_input_row">
                    <label><?php esc_attr_e('Enter your purchase code', 'digits'); ?></label>
                    <input type="text"
                           autocomplete="off"
                           data-purchase_code="1"
                           nocop="1"
                           id="digits_wizard_purchase_code"
                           placeholder="<?php esc_attr_e('Purchase Code', 'digits'); ?>"
                           required="required"
                           class="digits_purchase_code" name="digits_purchase_code"
                           value="<?php echo esc_attr($code) ?>"
                    />
                    <?php
                    digit_activation_fields();
                    ?>
                </div>
                <div class="untdor_wizard_input_row">
                    <label><?php esc_attr_e('Select the site type', 'digits'); ?></label>
                    <div class="untdor_wizard_input_radio_group">
                        <div class="untdor_wizard_input_radio">
                            <label>
                                <input type="radio" id="untdor_wizard_license_type_live"
                                       name="dig_license_type" <?php if ($license_type == "1") echo "checked"; ?>
                                       autocomplete="off"
                                       value="1"/>
                                <span class="untdor_wizard_input_radio_check"></span>
                                <?php esc_attr_e('Production / Live', 'digits'); ?>
                            </label>
                        </div>
                        <div class="untdor_wizard_input_radio">
                            <label>
                                <input type="radio" id="untdor_wizard_license_type_staging"
                                       name="dig_license_type" <?php if ($license_type == "2") echo "checked"; ?>
                                       autocomplete="off"
                                       value="2"/>
                                <span class="untdor_wizard_input_radio_check"></span>
                                <?php esc_attr_e('Staging / Test', 'digits'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    public function use_existing_form()
    {
        ?>
        <div class="untdor_wizard_no_gap show_next_previous">
            <div class="untdor_wizard_dummy_spacer"></div>
            <div class="untdor_wizard_step_title">
                <?php echo esc_attr__('You can only use your existing forms if', 'digits'); ?>
            </div>
            <div class="untdor_wizard_step_desc_list">
                <ol>
                    <li>
                        <?php esc_attr_e('Built on WooCommerce/WordPress standards and using their hooks.', 'digits'); ?>
                    </li>
                    <li>
                        <?php
                        $site = '<br /><a href="https://digits.unitedover.com" target="_blank">https://digits.unitedover.com</a>';
                        echo sprintf(esc_attr__('We are compatible with that particular 3rd Party plugin. The  list of compatible plugins can be found on our website %s', 'digits'), $site); ?>
                    </li>
                </ol>
            </div>
            <div class="untdor_wizard_highlight_box">
                <div class="untdor_wizard_highlight_box_text">
                    <?php
                    esc_attr_e('We highly recommend using our forms for better security and more features', 'digits');
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function digits_install_plugins()
    {
        $class = '';

        $addons = [
            [
                'slug' => 'elementor',
                'plugin' => 'elementor/elementor.php',
                'is_wp' => 1],
            [
                'slug' => 'digbuilder',
                'plugin' => 'digbuilder/digbuilder.php',
                'is_wp' => 0]
        ];
        $need_to_install = [];
        foreach ($addons as $plugin) {
            if (!is_plugin_active($plugin['plugin'])) {
                $need_to_install[] = $plugin;
            }
        }
        $is_installed = empty($need_to_install);

        if (!$is_installed) {
            $class = 'disable_next';
        }
        ?>
        <div class="untdor_wizard_center untdor_wizard_no_gap untdor_wizard_fbox_offset <?php echo $class; ?>"
             data-offset="32">
            <div class="untdor_wizard_step_title">
                <?php echo esc_attr__('Install Required Plugins', 'digits'); ?>
            </div>

            <div class="untdor_wizard_highlight_box">
                <div class="untdor_wizard_highlight_box_text">
                    <?php echo esc_attr__('Elementor & Page Builder', 'digits'); ?>
                </div>
                <div class="untdor_wizard_installer untdor_wizard_highlight_box_action">
                    <div class="untdor_wizard_highlight_tick_box untdor_installed" <?php if (!$is_installed) echo 'style="display:none;"'; ?>>
                        <div class="untdor_wizard_tick"></div>
                    </div>
                    <?php
                    if (!$is_installed) {

                        $plugins = json_encode($need_to_install);

                        ?>
                        <div class="untdor_wizard_install_btn untdor_wizard_highlight_box_btn untdor_wizard_installing_progress">
                            <div class="untdor_wizard_installing_progress_bar untdor_progress_transition"></div>
                            <div class="untdor_wizard_installing_progress_indicator"></div>
                        </div>

                        <div class="untdor_wizard_install_btn untdor_wizard_highlight_box_btn untdor_wizard_install_plugins"
                             data-nonce="<?php echo esc_attr(wp_create_nonce('dig_install_addon')); ?>">
                            <?php echo esc_attr__('Install', 'digits'); ?>
                        </div>
                        <input type="hidden" class="untdor_plugin_list" value="<?php echo esc_attr($plugins); ?>"/>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <div class="untdor_wizard_step_desc">
                <?php echo esc_attr__('With our drag and drop builder you can create popup forms or page forms as per your requirement.', 'digits'); ?>
                <br/><br/>
                <?php echo esc_attr__('You can even use our Elementor widgets in any of your existing WordPress pages too :)', 'digits'); ?>
            </div>
        </div>
        <?php
    }

    public function render_wizard_step($step)
    {
        if ($step['layout'] == 'buttons') {
            ?>
            <div class="untdor_wizard_center untdor_wizard_btns_box disable_next">
                <div class="untdor_wizard_btns_box_title">
                    <?php echo $step['title']; ?>
                </div>
                <?php
                foreach ($step['buttons'] as $button) {
                    $this->render_button($button);
                }
                ?>
            </div>
            <?php
        } else if ($step['layout'] == 'bottom_view') {
            $attrs = '';

            if (!empty($step['switch_to_tab'])) {
                $switch = $step['switch_to_tab'];
                $attrs .= ' data-tab_view="' . esc_attr($switch) . '" ';
            }
            ?>
            <div class="untor_wiz_bottom show_bottom_view" <?php echo $attrs; ?>>
                <div class="untdor_wizard_bottom_box_wrapper">
                    <div class="untdor_wizard_bottom_box_title">
                        <?php echo $step['title']; ?>
                    </div>
                    <?php
                    if (!empty($step['desc'])) {
                        ?>
                        <div class="untdor_wizard_bottom_box_desc">
                            <?php echo $step['desc']; ?>
                        </div>
                        <?php
                    }
                    if (!empty($step['buttons'])) {
                        ?>
                        <div class="untdor_wizard_bottom_btns">
                            <?php
                            foreach ($step['buttons'] as $button) {
                                $this->render_button($button);
                            }
                            ?>
                        </div>
                        <?php
                    }

                    ?>
                </div>
            </div>
            <?php
        } else if ($step['layout'] == 'informational') {
            ?>
            <div class="untdor_wizard_center untdor_wizard_info_box untdor_wizard_fbox_offset"
                 data-offset="<?php echo $step['offset']; ?>">
                <div class="untdor_wizard_info_wrapper">
                    <div class="untdor_wizard_btns_box_title">
                        <?php echo $step['title']; ?>
                    </div>
                    <div class="untdor_wizard_info_box_btns">
                        <?php
                        foreach ($step['buttons'] as $button) {
                            $this->render_button($button);
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }

    }

    public function configuration_wizard_feedback()
    {
        ?>
        <form class="untdor_wizard_feedback" autocomplete="off">
            <div class="untdor_wizard_center untdor_wizard_feedback_box untdor_wizard_no_gap untdor_wizard_fbox_offset"
                 data-offset="-25">
                <div class="untdor_wizard_feedback_box_title">
                    <?php echo esc_attr__('Voila, its all done :D', 'digits'); ?>
                </div>
                <div class="untdor_wizard_feedback_box_desc">
                    <?php echo esc_attr__('The basic setup of Digits plugin has been done, now you can play with its settings to explore more features', 'digits'); ?>
                </div>

                <div class="untdor_wizard_feedback_box_rate">
                    <div class="untdor_wizard_rate_title">
                        <?php echo esc_attr__('Rate your experience with this wizard', 'digits'); ?>
                    </div>
                    <div class="untdor_wizard_rating">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            $id = 'untdor_wizard_rate_star' . $i;
                            echo '<input class="untdor_wizard_rate_star" type="radio" id="' . $id . '" name="untdor_wizard_rate_stars" value="' . $i . '" />';
                            echo '<label for="' . $id . '"></label>';
                        }
                        ?>
                        <input type="hidden" name="dig_domain" value="<?php echo esc_attr(dig_network_home_url()); ?>"/>

                    </div>
                    <div class="untdor_wizard_rate_textarea_box">
                        <textarea
                                name="how_can_we_improve"
                                rows="2"
                                required="required"
                                placeholder="<?php echo esc_attr__('How can we improve this wizard?', 'digits'); ?>"></textarea>
                        <div>
                            <button class="untdor_wizard_submit_feedback" type="submit">
                                <?php echo esc_attr__('Send', 'digits'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    public function render_button($button)
    {
        $btn_class = [];

        if (!empty($button['button_type']) && $button['button_type'] == 'wrap_content') {
            $btn_class[] = 'untdor_wizard_step_btn_inline';
        } else {
            $btn_class[] = 'untdor_wizard_step_btn';
        }

        if (isset($button['primary_color'])) {
            $btn_class[] = 'untdor_wizard_btn_primary_color';
        } else {
            $btn_class[] = 'untdor_wizard_btn_dark_color';
        }
        $show_id = '';
        $button_ic = '';

        if (isset($button['next_step'])) {
            $view_id = $this->format_id($button['next_step']);
            $btn_class[] = 'btn_' . $view_id;
            $show_id = 'data-next-step="' . $view_id . '"';

            $button_ic = '<div class="untdor_wizard_arrow_ic untdor_wizard_arrow_big"></div>';
        } else if (isset($button['href'])) {
            $show_id = 'data-href="' . $button['href'] . '"';
            $button_ic = '<div class="untdor_wizard_external_ic"></div>';
        }
        ?>
        <div <?php echo $show_id; ?>
                class="<?php echo implode(" ", $btn_class); ?>">
            <div class="untdor_wizard_step_btn_text"><?php echo $button['label']; ?></div>
            <?php
            echo $button_ic;
            ?>
        </div>
        <?php
    }

    public function format_id($id)
    {
        return 'untwiz_' . $id;
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

    public function enqueue_styles()
    {
        wp_enqueue_style('digits-admin-wizard', get_digits_asset_uri('/admin/assets/css/wizard.min.css'), array(), digits_version(), 'all');

    }

    public function enqueue_scripts()
    {
        wp_register_script('digits-admin-wizard', get_digits_asset_uri('/admin/assets/js/wizard.min.js'), array(
            'jquery',
        ), digits_version(), true);

        $settings_array = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'thank_you_for_feedback' => __('Thank you for submitting your valuable feedback!'),
            'rate_experience' => __('Please rate your experience!'),
            "direction" => is_rtl() ? 'rtl' : 'ltr',
        );
        wp_localize_script('digits-admin-wizard', 'dig_wiz', $settings_array);

        wp_enqueue_script('digits-admin-wizard');
    }
}
