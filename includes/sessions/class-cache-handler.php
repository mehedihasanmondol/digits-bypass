<?php

defined('ABSPATH') || exit;

/**
 * Session handler class.
 */
class Digits_Cache_Handler
{

    const group_key = '_digits_cache';
    const expiry = 86400;
    protected static $_instance = null;
    public $data = [];

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

    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $value = DigitsSessions::get_from_identifier($key . self::group_key);
        $this->data[$key] = json_decode($value, true);
        return $value;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;

        DigitsSessions::update($key . self::group_key, $value, self::expiry, $key . self::group_key);
    }


}
