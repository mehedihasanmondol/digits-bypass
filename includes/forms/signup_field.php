<?php

namespace DigitsFormHandler;


if (!defined('ABSPATH')) {
    exit;
}


class SignUpField
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

    public static function parse_info($values)
    {
        $field_type = $values['meta_key'];
        if (strpos($field_type, 'country') !== false) {
            $values['type'] = 'dropdown';
            $values['options'] = dig_country_list();
            $values['use_key'] = true;
        }
        return $values;
    }
}
