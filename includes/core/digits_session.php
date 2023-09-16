<?php


if (!defined('ABSPATH')) {
    exit;
}

DigitsSessions::instance();

final class DigitsSessions
{
    const TABLE_PREFIX = 'digits_user_session';
    const USER_SESSION = 'd_user_session';
    protected static $_instance = null;

    public function __construct()
    {
        add_action('digits_create_database', array($this, 'activate'));
        add_action('auth_cookie_expired', array($this, 'auth_cookie_expired'));
        add_action('clear_auth_cookie', array($this, 'clear_auth_cookie'));
        add_action('digits_cron', array($this, 'digits_cron'));
    }

    public static function get($key)
    {
        $session = self::get_token();
        if (empty($session)) {
            return false;
        }

        global $wpdb;
        $table = self::get_table_name();

        $query = $wpdb->prepare("SELECT * FROM $table WHERE session_token = %s and data_key = %s order by session_id DESC LIMIT 1", $session, $key);
        $row = $wpdb->get_row($query);
        if (empty($row)) {
            return null;
        }

        if (strtotime($row->session_expiry) < time()) {
            return null;
        }

        return $row->data_value;
    }

    public static function update_identifier_value($identifier_id, $value)
    {
        if (is_object($value) || is_array($value)) {
            $value = json_encode($value);
        }

        global $wpdb;
        $table = self::get_table_name();

        $query = $wpdb->prepare("SELECT * FROM $table WHERE identifier_id = %s order by session_id DESC LIMIT 1", $identifier_id);
        $row = $wpdb->get_row($query);
        if (empty($row)) {
            return null;
        }
        $data = array('data_value' => $value);
        $where = array('identifier_id' => $identifier_id);
        return $wpdb->update($table, $data, $where);
    }

    public static function get_from_identifier($identifier_id, $get_row = false)
    {
        global $wpdb;
        $table = self::get_table_name();

        $query = $wpdb->prepare("SELECT * FROM $table WHERE identifier_id = %s order by session_id DESC LIMIT 1", $identifier_id);
        $row = $wpdb->get_row($query);
        if (empty($row)) {
            return null;
        }

        if (strtotime($row->session_expiry) < time()) {
            return null;
        }

        if ($get_row) {
            return $row;
        }
        return $row->data_value;
    }

    public static function get_from_key_identifier($key, $identifier_id, $get_row = false)
    {
        $session = self::get_token();
        if (empty($session)) {
            return false;
        }

        global $wpdb;
        $table = self::get_table_name();

        $query = "SELECT * FROM $table WHERE session_token = %s and data_key = %s AND identifier_id = %s order by session_id DESC LIMIT 1";
        $query = $wpdb->prepare($query, $session, $key, $identifier_id);
        $row = $wpdb->get_row($query);
        if (empty($row)) {
            return null;
        }

        if (strtotime($row->session_expiry) < time()) {
            return null;
        }

        if ($get_row) {
            return $row;
        }
        return $row->data_value;
    }

    public static function get_token()
    {
        if (is_user_logged_in()) {
            return wp_get_session_token();
        } else {
            if (empty($_COOKIE[self::USER_SESSION])) {
                $value = self::instance()->generate_id();
                setcookie(self::USER_SESSION, $value, 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl());
                return $value;
            } else {
                return $_COOKIE[self::USER_SESSION];
            }
        }
    }

    /**
     * Generate a cryptographically strong unique ID for the session token.
     *
     * @return string
     */
    public function generate_id()
    {
        return bin2hex(random_bytes(64));
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

    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_PREFIX;
    }

    public static function delete_user_key($user_id, $key)
    {
        if (empty($user_id) || !is_numeric($user_id)) {
            return false;
        }
        global $wpdb;
        $table = self::get_table_name();

        $data = array();
        $data['user_id'] = $user_id;
        $data['data_key'] = $key;

        return $wpdb->delete($table, $data);
    }

    public static function update($key, $value, $expiry_time, $identifier = false)
    {
        self::delete($key);
        return self::set($key, $value, $expiry_time, $identifier);
    }

    public static function set($key, $value, $expiry_time, $identifier = false)
    {
        $session = self::get_token();

        return self::set_session_value($session, $key, $value, $expiry_time, $identifier);
    }

    public static function set_session_value($session, $key, $value, $expiry_time, $identifier = false)
    {
        if (empty($session)) {
            return false;
        }

        if (is_object($value) || is_array($value)) {
            $value = json_encode($value);
        }

        global $wpdb;
        $table = self::get_table_name();
        $data = array('session_token' => $session);
        $data['user_id'] = get_current_user_id();
        $data['data_key'] = $key;
        $data['data_value'] = $value;

        if (!empty($expiry_time)) {
            $data['session_expiry'] = date("Y-m-d H:i:s", time() + $expiry_time);
        }
        $data['time'] = date("Y-m-d H:i:s", time());

        if (!empty($identifier)) {
            $data['identifier_id'] = $identifier;
        }

        return $wpdb->insert($table, $data);
    }

    public static function delete_identifier($identifier_id)
    {
        if (empty($identifier_id)) {
            return false;
        }

        global $wpdb;
        $table = self::get_table_name();
        $where = array('identifier_id' => $identifier_id);

        return $wpdb->delete($table, $where);
    }

    public static function delete($key)
    {
        $session = self::get_token();
        if (empty($session)) {
            return false;
        }

        global $wpdb;
        $table = self::get_table_name();
        $where = array('data_key' => $key, 'session_token' => $session);

        return $wpdb->delete($table, $where);
    }

    public function auth_cookie_expired($cookie_elements)
    {
        $token = $cookie_elements['token'];
        $this->_destroy_session($token);
    }

    public function clear_auth_cookie()
    {
        $this->destroy_session();
    }

    public function destroy_session()
    {
        $token = self::get_token();
        $this->_destroy_session($token);
    }

    public function create_new_session()
    {
        $this->destroy_session();

    }

    public function _destroy_session($session = false)
    {

        if (empty($session)) {
            return;
        }
        global $wpdb;
        $table = self::get_table_name();
        $where = array('session_token' => $session);
        $wpdb->delete($table, $where);
    }

    public function activate()
    {
        global $wpdb;
        $tb = $this->get_table_name();
        if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $tb (
                  session_id BIGINT UNSIGNED NOT NULL auto_increment,
                  identifier_id VARCHAR(255) NOT NULL,
		          session_token TEXT NOT NULL,
		          user_id BIGINT UNSIGNED NULL,
		          data_key LONGTEXT NOT NULL,
		          data_value LONGTEXT NOT NULL,
		          session_expiry datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          PRIMARY KEY (session_id),
		          INDEX idx_identifier_id (identifier_id)
	            ) $charset_collate;";

            dbDelta(array($sql));

        }
    }

    public function digits_cron()
    {
        global $wpdb;
        $tb = $this->get_table_name();
        $sql = $wpdb->prepare("DELETE FROM $tb WHERE `session_expiry` < DATE_SUB( NOW(), INTERVAL 1 DAY)");
        $wpdb->get_results($sql);
    }
}
