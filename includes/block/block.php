<?php

if (!defined('ABSPATH')) {
    exit;
}

function digits_register_block()
{
    if (!function_exists('register_block_type')) {
        return;
    }

    $blocks = [
        'login-register' => [
            'render_callback' => 'digits_sc_block_login_register'
        ],
        'login' => [
            'render_callback' => 'digits_sc_block_login'
        ],
        'forgot-password' => [
            'render_callback' => 'digits_sc_block_forgot_password'
        ],
        'register' => [
            'render_callback' => 'digits_sc_block_register'
        ]
    ];
    foreach ($blocks as $block_key => $block) {
        $block['icon'] = 'digits_block_editor_sc_icon';
        register_block_type(__DIR__ . '/' . $block_key, $block);
    }
}

add_action('init', 'digits_register_block');

add_filter('block_categories_all', function ($categories) {

    $categories[] = array(
        'slug' => 'digits',
        'title' => 'Digits',
        'icon' => 'digits_glyph_ic'
    );

    return $categories;
});

function digits_sc_block_login_register()
{
    return do_shortcode('[df-form]');
}

function digits_sc_block_login()
{
    return do_shortcode('[df-form-login]');
}

function digits_sc_block_forgot_password()
{
    return do_shortcode('[df-form-forgot-password]');
}

function digits_sc_block_register()
{
    return do_shortcode('[df-form-signup]');
}