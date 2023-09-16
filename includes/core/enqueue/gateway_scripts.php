<?php

if (!defined('ABSPATH')) {
    exit;
}


function digits_get_firebase_version()
{
    return '8.2.1';
}


function digits_reg_firebase_script()
{

    $handle = 'firebase';
    $list = 'enqueued';

    if (wp_script_is($handle, $list)) {
        return;
    }
    wp_register_script('firebase', 'https://www.gstatic.com/firebasejs/' . digits_get_firebase_version() . '/firebase-app.js', array(), digits_get_firebase_version(), false);
    wp_register_script('firebase-auth', 'https://www.gstatic.com/firebasejs/' . digits_get_firebase_version() . '/firebase-auth.js', array('firebase'), digits_get_firebase_version(), false);

    wp_enqueue_script('firebase');
    wp_enqueue_script('firebase-auth');

    $firebaseAuth = iniFireBaseinit();
    if (!empty($firebaseAuth)) {
        wp_add_inline_script('firebase-auth', $firebaseAuth);
    }
}

function iniFireBaseinit()
{
    $firebase = get_option('digit_firebase');


    $locale = apply_filters('wpml_current_language', '');
    if (empty($locale)) {
        $locale = get_locale();
    }


    if (empty($firebase['api_key']) && empty($firebase['config'])) {
        return;
    }

    $firebase_config = $firebase['config'];

    if (!empty($firebase_config)) {
        $data = stripslashes($firebase_config);
    } else {
        $data = 'var firebaseConfig = { 
            "apiKey": "' . $firebase['api_key'] . '",
            "authDomain": "' . $firebase['authdomain'] . '",
            "databaseURL": "' . $firebase['databaseurl'] . '",
            "projectId": "' . $firebase['projectid'] . '",
            "storageBucket": "' . $firebase['storagebucket'] . '",
            "messagingSenderId": "' . $firebase['messagingsenderid'] . '"
        };';
        $firebase['config'] = $data;
        update_option('digit_firebase', $firebase);
    }

    $data .= '
        firebase.initializeApp(firebaseConfig);
        firebase.auth().languageCode = "' . $locale . '";';

    $data = 'function digits_init_firebase(){' . $data . '};';
    return $data;
}


function digits_in_script()
{
    $app = get_option('digit_api');
    $appid = "";
    $handle = 'account-kit-ini';
    $list = 'enqueued';


    if ($app !== false && dig_is_gatewayEnabled(1) && !wp_script_is($handle, $list)) {
        $appid = $app['appid'];

        if (empty($appid)) {
            $appid = 0;
        }

        $csrf = wp_create_nonce('crsf-otp');


        if (isset($app['accountkit_type'])) {
            $accountkit_type = $app['accountkit_type'];
        } else {
            $accountkit_type = "modal";
        }


        ?>
        <script type="text/javascript">
            AccountKit_OnInteractive = function () {
                AccountKit.init(
                    {
                        appId: "<?php echo $appid; ?>",
                        state: "<?php echo $csrf; ?>",
                        display: "<?php echo $accountkit_type;?>",
                        version: "v1.1"
                    }
                );
            };
        </script>
        <?php
    }
    if (isset($_GET['ihc_ap_menu'])) {
        if ($_GET['ihc_ap_menu'] == "profile") {
            dig_addmobile();
        }
    }

}

add_action('wp_footer', 'digits_in_script');


function dig_get_accountkit_locale()
{

    $locale = apply_filters('wpml_current_language', '');
    if (empty($locale)) {
        $locale = get_locale();
    }
    $supportedLocaleArray = array(
        'af_ZA',
        'af_AF',
        'ar_AR',
        'bn_IN',
        'my_MM',
        'zh_CN',
        'zh_HK',
        'zh_TW',
        'hr_HR',
        'cs_CZ',
        'da_DK',
        'nl_NL',
        'en_GB',
        'en_US',
        'fi_FI',
        'fr_FR',
        'de_DE',
        'el_GR',
        'gu_IN',
        'he_IL',
        'hi_IN',
        'hu_HU',
        'id_ID',
        'it_IT',
        'ja_JP',
        'ko_KR',
        'cb_IQ',
        'ms_MY',
        'ml_IN',
        'mr_IN',
        'nb_NO',
        'pl_PL',
        'pt_BR',
        'pt_PT',
        'pa_IN',
        'ro_RO',
        'ru_RU',
        'sk_SK',
        'es_LA',
        'es_ES',
        'sw_KE',
        'sv_SE',
        'tl_PH',
        'ta_IN',
        'te_IN',
        'th_TH',
        'tr_TR',
        'ur_PK',
        'vi_VN'
    );

    if (in_array($locale, $supportedLocaleArray)) {
        $gl = $locale;
    } else {
        $gl = dig_get_locale($locale, $supportedLocaleArray);
    }

    if ($gl) {
        return $gl;
    } else {
        return 'en_US';
    }
}
