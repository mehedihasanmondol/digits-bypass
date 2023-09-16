<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * migrate fields
 * hide country code
 * @since v7.0.1.5
 *
 * */
add_action('digits_activation_hooks', 'digits_create_db');
function digits_create_db()
{
    _digits_create_db(false);
}

function _digits_create_db($load_dep = true)
{
    if ($load_dep) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }

    $db_version = get_option('digits_db_version', 0);

    update_option('digits_db_version', 3);
    global $wpdb;
    $tb = $wpdb->prefix . 'digits_otp';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tb (
                  id BIGINT UNSIGNED NOT NULL auto_increment,
                  user_id BIGINT DEFAULT NULL,
                  ref_id BIGINT DEFAULT NULL,
                  step_no TINYINT DEFAULT NULL,
                  email VARCHAR (100) DEFAULT NULL,
                  countrycode VARCHAR(30) DEFAULT NULL,
		          phone VARCHAR(30) DEFAULT NULL,
		          otp VARCHAR(255) NOT NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                  action_type VARCHAR(32) NULL,
		          ip VARCHAR(200) NOT NULL,
		          INDEX idx_email (email),
		          INDEX idx_user_id (user_id),
		          INDEX idx_phone (countrycode,phone),
		           primary key (id)
	            ) $charset_collate;";

        dbDelta(array($sql));
    }

    $tb2 = $wpdb->prefix . 'digits_login_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tb2'") != $tb2) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql2 = "CREATE TABLE $tb2 (
                  id BIGINT UNSIGNED NOT NULL auto_increment,
                  user_id BIGINT DEFAULT NULL,
                  login_steps VARCHAR (100) DEFAULT NULL,
                  login_methods VARCHAR (100) DEFAULT NULL,
                  request_source VARCHAR (100) DEFAULT NULL,
                  request_type VARCHAR (100) DEFAULT NULL,
                  user_token LONGTEXT DEFAULT NULL,
                  user_agent TEXT DEFAULT NULL,
                  password_less TINYINT DEFAULT NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          ip VARCHAR(200) NOT NULL,
		          primary key (id),
		          INDEX idx_user_id (user_id),
		          INDEX idx_request_source (request_source)
	            ) $charset_collate;";
        dbDelta(array($sql2));
    }


    $charset_collate = $wpdb->get_charset_collate();
    $auth_table = $wpdb->prefix . 'digits_auth_devices';
    $auth_sql = "CREATE TABLE $auth_table (
                  id BIGINT UNSIGNED NOT NULL auto_increment,
                  uniqid TEXT DEFAULT NULL,
                  user_id BIGINT DEFAULT NULL,
                  device_name TEXT DEFAULT NULL,
                  device_type TEXT DEFAULT NULL,
                  device_info TEXT DEFAULT NULL,
                  key_id TEXT DEFAULT NULL,
                  cred_source LONGTEXT DEFAULT NULL,
                  is_mobile TINYINT DEFAULT NULL,
                  user_agent TEXT DEFAULT NULL,
                  last_used datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          ip VARCHAR(200) NOT NULL,
		           primary key (id),
		           INDEX idx_user_id (user_id)
	            ) $charset_collate;";
    maybe_create_table($auth_table, $auth_sql);

    do_action('digits_create_database');


    if ($db_version == 2) {
        digits_migrate_fields_db_ip_field();
    }

}


add_action('digits_activation_hooks', 'digits_migrate_fields_db');


function digits_migrate_fields_db()
{
    
}
/*
 * for v2 to v3 migration
 * */
function digits_migrate_fields_db_ip_field()
{
    global $wpdb;
    $tb = $wpdb->prefix . 'digits_otp';
    $tb2 = $wpdb->prefix . 'digits_login_logs';
    $auth_table = $wpdb->prefix . 'digits_auth_devices';
    $wpdb->query("ALTER TABLE $tb MODIFY ip VARCHAR (200) NOT NULL;");
    $wpdb->query("ALTER TABLE $tb2 MODIFY ip VARCHAR (200) NOT NULL;");
    $wpdb->query("ALTER TABLE $auth_table MODIFY ip VARCHAR (200) NOT NULL;");
}
