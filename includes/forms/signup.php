<?php

if (!defined('ABSPATH')) {
    exit;
}

class DigitsSignupFields
{
    public $action_type = '';
    public $form_data = [];
    public $is_break = false;
    public $break_id;
    private $fields = [];
    private $asterisk = '';
    private $fields_order = [];
    private $reg_details = [];

    public function __construct()
    {
        $asterisk = '';

        $show_asterisk = get_option('dig_show_asterisk', 0);
        if ($show_asterisk == 1) {
            $asterisk = '<span>&nbsp;*</span>';
        }
        $this->setAsterisk($asterisk);
    }

    /**
     * @param string $asterisk
     */
    public function setAsterisk($asterisk)
    {
        $this->asterisk = $asterisk;
    }

    public function initNativeFields()
    {
        $fields_order = explode(",", get_option("dig_sortorder"));
        if (empty($fields_order) || !is_array($fields_order)) {
            $fields_order = ['dig_cs_mobilenumber', 'dig_cs_email', 'dig_cs_name', 'dig_cs_username', 'dig_cs_password'];
        }

        $reg_custom_fields = digits_get_reg_fields();

        $reg_fields = [];
        foreach ($reg_custom_fields as $field_key => $reg_custom_field) {
            $field_key = 'dig_cs_' . cust_dig_filter_string($reg_custom_field['meta_key']);
            $reg_fields[$field_key] = $reg_custom_field;
        }

        $this->setFields($reg_fields);
        $this->setFieldsOrder($fields_order);

    }

    public function initFields($fields)
    {
        $this->setFieldsOrder(array_keys($fields));
        $this->setFields($fields);
    }

    /**
     * @param mixed $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param array $fields_order
     */
    public function setFieldsOrder($fields_order)
    {
        $this->fields_order = $fields_order;
    }

    public function render()
    {
        $this->process_fields();

        $fields = $this->fields_order;

        $dig_reg_details = $this->reg_details;
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

        $fields = array_filter($fields);

        if (empty($fields) || !is_array($fields)) {
            $fields = ['dig_cs_mobilenumber', 'dig_cs_email', 'dig_cs_name', 'dig_cs_username', 'dig_cs_password'];
        }

        $fields = apply_filters('digits_signup_render_fields_list', $fields);

        $use_tab = false;
        if (in_array('dig_cs_email', $fields) && in_array('dig_cs_mobilenumber', $fields)) {
            if ($emailaccep == 1 && $mobileaccp == 1) {
                $use_tab = true;
            }
        }

        $emailActiveClass = '';
        $tab_bar_class = '';
        $show_tab = true;

        $action_type = 'phone';

        if (
            ($emailaccep == 2 || $mobileaccp == 2) ||
            ($emailaccep == 0 && $mobileaccp > 0) ||
            ($emailaccep > 0 && $mobileaccp == 0)
        ) {
            $show_tab = false;
        }

        if (!$show_tab) {
            $tab_bar_class .= ' digits_hide_tabs ';
        }


        $email_field = true;
        $mobile_field = true;


        if ($emailaccep == 0) {
            $email_field = false;
        } else if ($emailaccep == 1 && $mobileaccp == 2) {
            $email_field = true;
        }

        if ($mobileaccp == 0) {
            $mobile_field = false;
        } else if ($emailaccep == 2 && $mobileaccp == 1) {
            $mobile_field = false;
        }

        $container_class = '';
        if ($emailaccep == 2 && $mobileaccp == 2) {
            $container_class = 'digits-form_body-no_tabs';
        }
        ?>
        <div class="digits-form_tab_container <?php echo $container_class; ?>">
            <?php
            if (!empty($_REQUEST['show_force_title'])) {
                $title = __('Register', 'digits');
                echo '<span class="main-section-title digits_display_none">' . $title . '</span>';
            }
            ?>
            <div class="digits-form_tabs">
                <?php
                if ($use_tab) {
                    ?>
                    <div class="digits-form_tab-bar <?php echo $tab_bar_class; ?>">
                        <?php
                        if ($mobile_field) {
                            echo '<div data-change="action_type" data-value="phone" class="digits-form_tab-item digits_reg_use_phone digits-tab_active">' . __('Use Phone Number', 'digits') . '</div>';
                        } else {
                            $emailActiveClass = 'digits-tab_active';
                            $action_type = 'email';
                        }
                        if ($email_field) {
                            echo '<div data-change="action_type" data-value="email" class="digits-form_tab-item digits_reg_use_email ' . $emailActiveClass . '">' . __('Use Email Address', 'digits') . '</div>';
                        }
                        ?>
                    </div>
                    <?php
                    echo '<input type="hidden" name="action_type" value="' . esc_attr($action_type) . '" autocomplete="off"/>';
                }
                ?>
            </div>
            <div class="digits-form_body">
                <?php
                if ($use_tab) {
                    ?>
                    <div class="digits-form_body_wrapper">
                        <?php if ($mobile_field) { ?>
                            <div data-field-type="phone"
                                 class="digits-form_tab_body digits-phone_row digits-tab_active">
                                <?php
                                $this->render_phone_field(false);
                                $fields = $this->remove_field($fields, 'dig_cs_mobilenumber');
                                ?>
                            </div>
                        <?php } ?>
                        <?php if ($email_field) { ?>
                            <div data-field-type="email"
                                 class="digits-form_tab_body digits-email_row <?php echo $emailActiveClass; ?>">
                                <?php
                                $this->render_email_field(false);
                                $fields = $this->remove_field($fields, 'dig_cs_email');
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                }

                $this->field_render($fields);
                ?>
            </div>
        </div>
        <?php
        $this->action_type = $action_type;
    }

    public function process_fields()
    {
        $order = $this->fields_order;
        $start = 0;
        $end = 0;
        foreach ($order as $field_index => $field_id) {
            if (!empty($this->fields[$field_id])) {
                $field = $this->fields[$field_id];
                if ($field['type'] == 'break') {
                    if (!empty($this->form_data[$field_id])) {
                        $field_value = $this->form_data[$field_id];
                        if (wp_verify_nonce($field_value, 'break_' . $field_id)) {
                            $start = $field_index + 1;
                            continue;
                        }
                    }
                    $this->break_id = $field_id;
                    $this->is_break = true;
                    $end = $field_index;
                    break;
                }
            }
            $end = $field_index + 1;
        }

        $length = $end - $start;
        $order = array_slice($order, $start, $length);
        $this->setFieldsOrder($order);

    }

    private function render_phone_field($show_label)
    {
        $country = $this->get_country();
        $userCountry = $country['country'];
        $userCountryCode = $country['code'];
        digits_ui_reg_phone_field('', $userCountryCode, $userCountry, $show_label);

    }

    public function get_country()
    {
        return getUserCountryCode(true);
    }

    public function remove_field($fields, $field)
    {
        $key = array_search($field, $fields, true);
        if ($key !== false) {
            unset($fields[$key]);
        }
        return $fields;
    }

    private function render_email_field($show_label)
    {
        digits_ui_reg_email_field('email', $show_label);
    }

    public function field_render($field_order)
    {
        $dig_reg_details = $this->reg_details;
        $fields = $this->fields;
        $asterisk = $this->asterisk;

        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

        ?>
        <div class="digits_signup_form_step digits_signup_active_step">
            <?php
            foreach ($field_order as $field_id) {
                if ($field_id == 'dig_cs_name') {
                    if ($nameaccep > 0) {
                        ?>

                        <div id="dig_cs_name" class="digits-form_input_row digits-user_inp_row">
                            <div class="digits-form_input">
                                <label class="field_label">
                                    <?php
                                    esc_attr_e('First Name', 'digits');
                                    if ($nameaccep == 2) {
                                        echo $asterisk;
                                    }
                                    ?>
                                </label>
                                <input type="text" name="digits_reg_name" id="digits_reg_name"
                                       value="" <?php if ($nameaccep == 2) {
                                    echo "required";
                                } ?>
                                       placeholder="<?php esc_attr_e('First Name', 'digits'); ?>"
                                       autocomplete="name"/>
                            </div>
                        </div>
                    <?php }
                } else if ($field_id == 'dig_cs_username') {
                    if ($usernameaccep > 0) {
                        ?>

                        <div id="dig_cs_username" class="digits-form_input_row digits-user_inp_row">
                            <div class="digits-form_input">
                                <label class="field_label">
                                    <?php
                                    esc_attr_e('Username', 'digits');
                                    if ($usernameaccep == 2) {
                                        echo $asterisk;
                                    }
                                    ?>
                                </label>
                                <input type="text" name="digits_reg_username" id="digits_reg_username"
                                       value="" <?php if ($usernameaccep == 2) {
                                    echo "required";
                                } ?>
                                       placeholder="<?php esc_attr_e('Username', 'digits'); ?>"
                                       autocomplete="username"/>
                            </div>
                        </div>
                        <?php
                    }
                } else if ($field_id == 'dig_cs_password') {
                    if ($passaccep > 0) {
                        ?>
                        <div id="dig_cs_password" class="digits-form_input_row digits-user_inp_row digits_password_inp_row">
                            <div class="digits-form_input">
                                <label class="field_label">
                                    <?php
                                    esc_attr_e('Password', 'digits');
                                    if ($passaccep == 2) {
                                        echo $asterisk;
                                    }
                                    ?>
                                </label>
                                <input type="password"
                                       name="digits_reg_password"
                                       class="new_password"
                                       autocomplete="new-password"
                                       placeholder="<?php esc_attr_e('Password', 'digits'); ?>"
                                    <?php if ($passaccep == 2) echo 'required="required"'; ?>
                                />
                            </div>
                        </div>
                        <?php
                    }
                } else if ($field_id == 'dig_cs_email') {
                    if ($emailaccep > 0) {
                        ?>
                        <div class="digits_email_holder">
                            <?php
                            $this->render_email_field(-1);
                            ?>
                        </div>
                        <?php
                    }
                } else if ($field_id == 'dig_cs_mobilenumber') {
                    if ($mobileaccp > 0) {
                        ?>
                        <div class="digits_phone_holder">
                            <?php
                            $this->render_phone_field(-1);
                            ?>
                        </div>
                        <?php
                    }
                } else {
                    $field = $fields[$field_id];
                    $field_type = $field['type'];
                    if ($field_type == 'form_step_title') {
                        $label = esc_attr__($field['label'], 'digits');
                        echo '<span class="main-section-title digits_display_none">' . $label . '</span>';
                    } else if ($field_type != 'break') {
                        $field['placeholder'] = $field['label'];
                        dig_show_fields(array($field_id => $field), !empty($asterisk), 11);
                    }
                }
            }

            if ($this->is_break) {
                $field_nonce = wp_create_nonce('break_' . $this->break_id);
                echo '<input type="hidden" name="' . esc_attr($this->break_id) . '" value="' . esc_attr($field_nonce) . '" />';
            } else {
                echo '<input type="hidden" name="digits_process_register" value="1" />';
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array $reg_details
     */
    public function setRegDetails($reg_details)
    {
        $this->reg_details = $reg_details;
    }

    /**
     * @return string
     */
    public function getActionType()
    {
        return $this->action_type;
    }

    /**
     * @return array
     */
    public function getFormData()
    {
        return $this->form_data;
    }

    /**
     * @param array $form_data
     */
    public function setFormData($form_data)
    {
        $this->form_data = $form_data;
    }

}
