<?php


if (!defined('ABSPATH')) {
    exit;
}

function digits_session_cron_job_scheduler()
{
    if (!wp_next_scheduled('digits_session_cron_hook')) {
        wp_schedule_event(time(), 'daily', 'digits_session_cron_hook');
    }
}

add_action('admin_init', 'digits_session_cron_job_scheduler');

function digits_session_cron()
{
    do_action('digits_cron');
}

add_action( 'digits_session_cron_hook', 'digits_session_cron' );
