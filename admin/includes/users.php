<?php

use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

function digits_add_manage_users_columns($column)
{
    $column['digits_mobile_number'] = __('Phone Number', 'digits');
    $column['digits_user_auth_step'] = __('Authentication', 'digits');

    return $column;
}

add_filter('manage_users_columns', 'digits_add_manage_users_columns');

function digits_add_manage_user_mobile($value, $column_name, $user_id)
{
    switch ($column_name) {
        case 'digits_mobile_number' :
            return get_user_meta($user_id, 'digits_phone', true);
            break;
        case 'digits_user_auth_step':
            if (!UserSettingsHandler::isUser2FaEnabled($user_id)) {
                return __('1-Factor Authentication', 'digits');
            }

            if (UserSettingsHandler::isUser3FaEnabled($user_id)) {
                return __('3-Factor Authentication', 'digits');
            }

            return __('2-Factor Authentication', 'digits');
        default:
            return $value;
    }
}

add_filter('manage_users_custom_column', 'digits_add_manage_user_mobile', 25, 3);

function digits_add_mobile_user_search($wp_user_query)
{
    global $pagenow;
    if (is_admin() && 'users.php' == $pagenow) {
        if (false === strpos($wp_user_query->query_where, '@') && !empty($_GET["s"])) {
            global $wpdb;

            $user_ids = array();
            $user_ids_per_term = array();

            // Usermeta fields to search
            $usermeta_keys = array('digits_phone');

            $query_string_meta = "";
            $search_terms = sanitize_text_field($_GET["s"]);
            $search_terms_array = explode(' ', $search_terms);

            // Search users for each search term (word) individually
            foreach ($search_terms_array as $search_term) {
                // reset ids per loop
                $user_ids_per_term = array();

                // add all custom fields into the query
                if (!empty($usermeta_keys)) {
                    $query_string_meta = "meta_key='" . implode("' OR meta_key='", esc_sql($usermeta_keys)) . "'";
                }


                // Query usermeta table
                $usermeta_results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE (" . $query_string_meta . ") AND LOWER(meta_value) LIKE '%%%s%%'", $search_term));

                foreach ($usermeta_results as $usermeta_result) {
                    if (!in_array($usermeta_result->user_id, $user_ids_per_term)) {
                        array_push($user_ids_per_term, $usermeta_result->user_id);
                    }
                }

                // Query users table
                $users_results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT ID FROM $wpdb->users WHERE LOWER(user_nicename) LIKE '%%%s%%' OR LOWER(user_email) LIKE '%%%s%%' OR LOWER(display_name) LIKE '%%%s%%'", $search_term, $search_term, $search_term));

                foreach ($users_results as $users_result) {
                    if (!in_array($users_result->ID, $user_ids_per_term)) {
                        array_push($user_ids_per_term, $users_result->ID);
                    }
                }

                // Limit results to matches of all search terms
                if (empty($user_ids)) {
                    $user_ids = array_merge($user_ids, $user_ids_per_term);
                } else {
                    if (!empty($user_ids_per_term)) {
                        $user_ids = array_unique(array_intersect($user_ids, $user_ids_per_term));
                    }
                }
            }

            // Convert IDs to comma separated string
            $ids_string = implode(',', $user_ids);

            if (!empty($ids_string)) {
                // network users search (multisite)
                $wp_user_query->query_where = str_replace("user_nicename LIKE '" . $search_terms . "'", "ID IN(" . $ids_string . ")", $wp_user_query->query_where);

                // site (blog) users search
                $wp_user_query->query_where = str_replace("user_nicename LIKE '%" . $search_terms . "%'", "ID IN(" . $ids_string . ")", $wp_user_query->query_where);

                // network/site users search by number (WordPress assumes user ID number)
                $wp_user_query->query_where = str_replace("ID = '" . $search_terms . "'", "ID = '" . $search_terms . "' OR ID IN(" . $ids_string . ")", $wp_user_query->query_where);
            }
        }
    }

    return $wp_user_query;
}

add_action('pre_user_query', 'digits_add_mobile_user_search');