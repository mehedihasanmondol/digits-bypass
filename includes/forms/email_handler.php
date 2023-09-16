<?php

namespace DigitsFormHandler;


use DigitsDeviceAuth;
use DigitsNoticeException;
use DigitsSettingsHandler\UserAccountInfo;
use DigitsUserFormHandler\UserSettingsHandler;
use Exception;
use WP_Error;
use WP_User;


if (!defined('ABSPATH')) {
    exit;
}


final class EmailHandler
{
    protected static $_instance = null;
    public $type;
    public $user;
    public $to;
    public $from;
    public $body;
    public $display_name;
    public $subject;

    /**
     * @throws Exception
     */
    public function __construct($type = '')
    {
        if (!empty($type)) {
            $this->predefined_emails($type);
        }
    }

    /**
     * @throws Exception
     */
    public function predefined_emails($type)
    {
        $this->type = $type;
        switch ($type) {
            case 'login':
                $subject = __('Log in to {{sitename}}', 'digits');
                $mail_type = 'email_login';
                break;
            case 'verify_email':
            case 'register':
                $subject = __('Verify your Email Address', 'digits');
                $mail_type = 'email_verify';
                break;
            case 'forgot':
                $subject = __('Forgot your password?', 'digits');
                $mail_type = 'email_forgot';
                break;
            default:
                throw new Exception(__("Error! Not found", "digits"));
        }
        $this->subject = $subject;
        $this->body = $this->get_template($mail_type);

    }

    public function get_template($mail_type)
    {
        return $this->get_file_contents($mail_type);
    }

    public function get_file_contents($file_name)
    {

        $location = get_stylesheet_directory() . '/digits/' . $file_name . '.php';

        if (!file_exists($location)) {
            $location = get_digits_dir() . '/templates/' . $file_name . '.php';
        }

        if (file_exists($location)) {
            ob_start();

            require_once $location;
            $template = ob_get_contents();
            ob_end_clean();
            return $template;
        }
        return false;
    }

    public function setUser($user)
    {
        $this->user = $user;

        if (!empty($user->user_email)) {
            $this->to = $this->user->user_email;
        }

        $this->display_name = $user->display_name;
    }

    public function parse_placeholders($args = array())
    {
        $image_url = '';

        $digits_admin_email_style = get_option('digits_form_email_style', array());
        if (!empty($digits_admin_email_style['logo'])) {
            $image_url = $digits_admin_email_style['logo'];
        } else {
            $custom_logo_id = get_theme_mod('custom_logo');
            $image = wp_get_attachment_image_src($custom_logo_id, 'medium');
            if (!empty($image)) {
                $image_url = $image[0];
            }
        }


        $site_name = get_bloginfo('name');

        $placeholder_values = array(
            '{{site-logo}}' => $image_url,
            '{{sitename}}' => $site_name,
            '{{sitelink}}' => home_url(),
        );

        if (!empty($this->user) && $this->user instanceof WP_User) {
            $placeholder_values['{{name}}'] = $this->user->display_name;
        }

        $placeholder_values = array_merge($placeholder_values, $args);


        $this->subject = apply_filters('digits_email_subject', $this->subject, $placeholder_values);

        $placeholder_values = apply_filters('digits_email_placeholders', $placeholder_values, $this->type, $this->user);

        $this->subject = strtr($this->subject, $placeholder_values);

        $this->body = strtr($this->body, $placeholder_values);

    }

    public function send()
    {
        if (empty($this->to)) {
            throw new Exception(__('Cannot send email to an empty address', 'digits'));
        }
        return digit_send_email($this->to, $this->subject, $this->body, $this->display_name);
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @param mixed $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * @param mixed $display_name
     */
    public function setDisplayName($display_name)
    {
        $this->display_name = $display_name;
    }
}