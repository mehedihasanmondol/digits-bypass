<?php

if (!defined('ABSPATH')) {
    exit;
}


if (!function_exists('untdovr_gateway_field_label')) {
    function untdovr_gateway_field_label($field)
    {
        return $field;
    }
}

add_filter('digits_sms_gateways', 'digits_add_gateway_list');
function digits_add_gateway_list($gateways)
{
    return array_merge($gateways, digits_additional_gateways_list());
}

function digits_additional_gateways_list()
{
    return array(
        'SMS123' => array(
            'value' => 130,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
            ),
        ),
        'OperSMS' => array(
            'value' => 34,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Login') => array('text' => true, 'name' => 'login'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
            ),
        ),
        'SparrowSMS' => array(
            'value' => 35,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Token') => array('text' => true, 'name' => 'token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'ADPDIGITAL' => array(
            'value' => 37,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Spryng' => array(
            'value' => 38,
            'inputs' => array(
                untdovr_gateway_field_label('Bearer Token') => array('text' => true, 'name' => 'bearer_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Karix' => array(
            'value' => 39,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('UID') => array('text' => true, 'name' => 'uid'),
                untdovr_gateway_field_label('Token') => array('text' => true, 'name' => 'token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Bandwidth' => array(
            'value' => 40,
            'inputs' => array(
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'uid'),
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'token'),
                untdovr_gateway_field_label('Application ID') => array('text' => true, 'name' => 'application_id'),
                untdovr_gateway_field_label('Account ID') => array('text' => true, 'name' => 'account_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'CDYNE' => array(
            'value' => 41,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('License Key') => array('text' => true, 'name' => 'license_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'EngageSpark' => array(
            'value' => 42,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Organization ID') => array('text' => true, 'name' => 'organization_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'KAPSystem' => array(
            'value' => 43,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Telestax' => array(
            'value' => 44,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Account SID') => array('text' => true, 'name' => 'account_sid'),
                untdovr_gateway_field_label('Auth Token') => array('text' => true, 'name' => 'auth_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TTAGSystems' => array(
            'value' => 45,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Wavecell' => array(
            'value' => 46,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Subaccount ID') => array('text' => true, 'name' => 'subaccount_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSAero' => array(
            'value' => 47,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Email') => array('text' => true, 'name' => 'email'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'GatewayAPI' => array(
            'value' => 48,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'AgileTelecom' => array(
            'value' => 49,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('SMS User') => array('text' => true, 'name' => 'sms_user'),
                untdovr_gateway_field_label('SMS Password') => array('text' => true, 'name' => 'sms_password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'GreenText' => array(
            'value' => 50,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Client ID') => array('text' => true, 'name' => 'client_id'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'MNotify' => array(
            'value' => 51,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSBroadcast' => array(
            'value' => 52,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSGatewayHub' => array(
            'value' => 53,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
                untdovr_gateway_field_label('Entity ID') => array('text' => true, 'name' => 'entity-id'),
                untdovr_gateway_field_label('DLT Template ID') => array('text' => true, 'name' => 'dlt-template-id'),
            ),
        ),
        'ThaiBulkSMS' => array(
            'value' => 54,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSCountry' => array(
            'value' => 55,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TextMagic' => array(
            'value' => 56,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'QSMS' => array(
            'value' => 57,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSFactor' => array(
            'value' => 58,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'ESMS' => array(
            'value' => 59,
            'require_addon' => 1,
            'label' => 'ESMS.VN',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Brandname') => array('text' => true, 'name' => 'brandname'),
            ),
        ),
        'ISMS' => array(
            'value' => 60,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Textplode' => array(
            'value' => 61,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'RouteSMS' => array(
            'value' => 62,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Skebby' => array(
            'value' => 63,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SendHub' => array(
            'value' => 64,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
            ),
        ),
        'Proovl' => array(
            'value' => 132,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Token') => array('text' => true, 'name' => 'token'),
            ),
        ),
        'Tyntec' => array(
            'value' => 65,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'BulkSMSNigeria' => array(
            'value' => 66,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'BulkSMS' => array(
            'value' => 67,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Token ID') => array('text' => true, 'name' => 'token_id'),
                untdovr_gateway_field_label('Token Secret') => array('text' => true, 'name' => 'token_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Esendex' => array(
            'value' => 68,
            'inputs' => array(
                untdovr_gateway_field_label('Account reference ') => array('text' => true, 'name' => 'account_reference'),
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'WebSMS' => array(
            'value' => 69,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Access Token ') => array('text' => true, 'name' => 'access_token'),
            ),
        ),
        'SMSGlobal' => array(
            'value' => 70,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'FortyTwo' => array(
            'value' => 71,
            'inputs' => array(
                untdovr_gateway_field_label('Authorization Token') => array('text' => true, 'name' => 'authorization_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Primotexto' => array(
            'value' => 72,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Spirius' => array(
            'value' => 73,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'ExpertTexting' => array(
            'value' => 74,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Jusibe' => array(
            'value' => 75,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'Mensatek' => array(
            'value' => 76,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Email') => array('text' => true, 'name' => 'email'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SpeedSMS' => array(
            'value' => 77,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Access token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSMISR' => array(
            'value' => 78,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'JazzCMT' => array(
            'value' => 79,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'MoceanSMS' => array(
            'value' => 80,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SendSMS247' => array(
            'value' => 81,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SmscUA' => array(
            'value' => 82,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'CPSMS' => array(
            'value' => 83,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        '1s2u' => array(
            'value' => 84,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TextAnywhere' => array(
            'value' => 85,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMS77' => array(
            'value' => 86,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Verimor' => array(
            'value' => 87,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'LabsMobile' => array(
            'value' => 88,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Unisender' => array(
            'value' => 89,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Aruba' => array(
            'value' => 90,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Comilio' => array(
            'value' => 91,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Authorization token') => array('text' => true, 'name' => 'authorization_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSHosting' => array(
            'value' => 92,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Authorization key') => array('text' => true, 'name' => 'auth_key'),
                untdovr_gateway_field_label('Authorization secret') => array('text' => true, 'name' => 'auth_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Gateway' => array(
            'value' => 93,
            'require_addon' => 1,
            'label' => 'Gateway.sa',
            'inputs' => array(
                untdovr_gateway_field_label('API ID') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('API Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
                untdovr_gateway_field_label('Template ID') => array('text' => true, 'name' => 'template_id'),
            ),
        ),
        'UwaziiMobile' => array(
            'value' => 94,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SureSMS' => array(
            'value' => 95,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'EasysendSMS' => array(
            'value' => 96,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Sinch' => array(
            'value' => 97,
            'inputs' => array(
                untdovr_gateway_field_label('Bearer token') => array('text' => true, 'name' => 'bearer_token'),
                untdovr_gateway_field_label('Service plan id') => array('text' => true, 'name' => 'service_plan_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSAla' => array(
            'value' => 98,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API ID') => array('text' => true, 'name' => 'api_id'),
                untdovr_gateway_field_label('API Password') => array('text' => true, 'name' => 'api_password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSEmpresa' => array(
            'value' => 99,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Semaphore' => array(
            'value' => 100,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Wavy' => array(
            'value' => 101,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Authentication token') => array('text' => true, 'name' => 'authentication_token'),
            ),
        ),
        'SMSTo' => array(
            'value' => 102,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Telnyx' => array(
            'value' => 103,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TeleSign' => array(
            'value' => 104,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'D7Networks' => array(
            'value' => 105,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'ISMSIndonesia' => array(
            'value' => 106,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SendPK' => array(
            'value' => 107,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'MimSMS' => array(
            'value' => 108,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Token (only for BD Portal)') => array('text' => true, 'name' => 'api_token', 'optional' => 1),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
                untdovr_gateway_field_label('Portal') => array('select' => true, 'name' => 'portal', 'options' => array('BRAND SMS' => 'brand_sms', 'BD PORTAL' => 'bd_portal')),

            ),
        ),
        'OpenMarket' => array(
            'value' => 109,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'MobyT' => array(
            'value' => 110,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TM4B' => array(
            'value' => 111,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SwiftSMSGateway' => array(
            'value' => 112,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Account key') => array('text' => true, 'name' => 'account_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        '2Factor' => array(
            'value' => 113,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Template Name') => array('text' => true, 'name' => 'template-name'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'GupShup' => array(
            'value' => 114,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('APP ID') => array('text' => true, 'name' => 'app_id'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Digimiles' => array(
            'value' => 115,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('IP Address') => array('text' => true, 'name' => 'ip_addr'),
                untdovr_gateway_field_label('Port') => array('text' => true, 'name' => 'port'),
                untdovr_gateway_field_label('Entity ID') => array('text' => true, 'name' => 'entity_id'),
                untdovr_gateway_field_label('Template ID') => array('text' => true, 'name' => 'template_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'CallFire' => array(
            'value' => 116,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'NowSMS' => array(
            'value' => 117,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
            ),
        ),
        'Releans' => array(
            'value' => 118,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'ZipWhip' => array(
            'value' => 119,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
            ),
        ),
        'MessageMedia' => array(
            'value' => 120,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'TheSMSWorks' => array(
            'value' => 121,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Mogreet' => array(
            'value' => 122,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Client ID') => array('text' => true, 'name' => 'client_id'),
                untdovr_gateway_field_label('Token') => array('text' => true, 'name' => 'token'),
                untdovr_gateway_field_label('Campaign ID') => array('text' => true, 'name' => 'campaign_id'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        '46elks' => array(
            'value' => 123,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SlickText' => array(
            'value' => 124,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Private key') => array('text' => true, 'name' => 'private_key'),
            ),
        ),
        'SMSIdea' => array(
            'value' => 125,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Tatango' => array(
            'value' => 126,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
            ),
        ),
        'SMSEdge' => array(
            'value' => 127,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'SMSMasivos' => array(
            'value' => 128,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API key') => array('text' => true, 'name' => 'api_key'),
            ),
        ),
        'CommzGate' => array(
            'value' => 129,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
            ),
        ),
        'SMS.RU' => array(
            'value' => 131,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API ID') => array('text' => true, 'name' => 'api_id'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from', 'optional' => 1),
            ),
        ),
        'Messente' => array(
            'value' => 133,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'Text Marketer' => array(
            'value' => 134,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Orig') => array('text' => true, 'name' => 'orig'),
            ),
        ),
        'Spring Edge' => array(
            'value' => 135,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),

        'Signalwire' => array(
            'value' => 136,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Space URL') => array('text' => true, 'name' => 'space_url'),
                untdovr_gateway_field_label('Project ID') => array('text' => true, 'name' => 'project_id'),
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),

        'Camoo' => array(
            'value' => 137,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),

        'CM.com' => array(
            'value' => 138,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from', 'optional' => 1),
            )
        ),

        'Ooredoo Sms' => array(
            'value' => 139,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Mobile') => array('text' => true, 'name' => 'mobile'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
            )
        ),
        'Max-Sms' => array(
            'value' => 140,
            'require_addon' => 1,
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'uname'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
            )
        ),
        'payam_resan' => array(
            'value' => 141,
            'require_addon' => 1,
            'label' => 'Payam Resan',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'uname'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
            )
        ),
        'foxglove' => array(
            'value' => 142,
            'require_addon' => 1,
            'label' => 'Foxglove Connect',
            'inputs' => array(
                untdovr_gateway_field_label('User') => array('text' => true, 'name' => 'user'),
                untdovr_gateway_field_label('Key') => array('text' => true, 'name' => 'key'),
                untdovr_gateway_field_label('Account Type') => array('select' => true, 'name' => 'account_type',
                    'options' => array(
                        'Service/Transaction SMS' => '1',
                        'Promotional SMS' => '2',
                        'International SMS' => '3',
                        'OTP SMS' => '6',
                        'Other SMS' => '7',
                    )),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender')
            )
        ),
        'txtsync' => array(
            'value' => 143,
            'require_addon' => 1,
            'label' => 'TxtSync',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),
        'serwersms' => array(
            'value' => 144,
            'require_addon' => 1,
            'label' => 'SerwerSMS.pl',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),
        'orange_gateway' => array(
            'value' => 145,
            'require_addon' => 1,
            'label' => 'Orange',
            'inputs' => array(
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Sender Address') => array('text' => true, 'name' => 'sender'),
                untdovr_gateway_field_label('Sender Name') => array('text' => true, 'name' => 'sender_name', 'optional' => 1),
            )
        ),
        'msegat' => array(
            'value' => 146,
            'require_addon' => 1,
            'label' => 'Msegat',
            'inputs' => array(
                untdovr_gateway_field_label('UserName') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender Name') => array('text' => true, 'name' => 'sender'),
            )
        ),
        'altiria' => array(
            'value' => 147,
            'require_addon' => 1,
            'label' => 'Altiria',
            'inputs' => array(
                untdovr_gateway_field_label('Login') => array('text' => true, 'name' => 'login'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
                untdovr_gateway_field_label('Domain ID') => array('text' => true, 'name' => 'domainId', 'optional' => 1),
            ),
        ),
        'redsms' => array(
            'value' => 148,
            'require_addon' => 1,
            'label' => 'RedSMS',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Login') => array('text' => true, 'name' => 'login'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from'),
            ),
        ),
        'osonsms' => array(
            'value' => 149,
            'require_addon' => 1,
            'label' => 'OsonSMS',
            'inputs' => array(
                untdovr_gateway_field_label('User Login') => array('text' => true, 'name' => 'login'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from'),
            ),
        ),
        'dooae' => array(
            'value' => 150,
            'require_addon' => 1,
            'label' => 'Doo.ae',
            'inputs' => array(
                untdovr_gateway_field_label('Mobile') => array('text' => true, 'name' => 'mobile'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'smsir_gateway' => array(
            'value' => 151,
            'require_addon' => 1,
            'label' => 'SMS.ir',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Line No') => array('text' => true, 'name' => 'line_no'),
            ),
        ),
        'notify_lk' => array(
            'value' => 152,
            'require_addon' => 1,
            'label' => 'Notify.lk',
            'inputs' => array(
                untdovr_gateway_field_label('User ID') => array('text' => true, 'name' => 'user_id'),
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
            ),
        ),
        'malath' => array(
            'value' => 153,
            'require_addon' => 1,
            'label' => 'Malath',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'smsalert' => array(
            'value' => 154,
            'require_addon' => 1,
            'label' => 'SMS Alert',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        /*'Rangine' => array(
            'value' => 155,
            'label' => 'Rangine.ir (IRAN)',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array(
                    'text' => true,
                    'name' => 'username',
                ),
                untdovr_gateway_field_label('Password') => array(
                    'text' => true,
                    'name' => 'password',
                ),
                untdovr_gateway_field_label('Sender') => array(
                    'text' => true,
                    'name' => 'sender',
                ),
                untdovr_gateway_field_label('Send as default pattern') => array(
                    'options' => array(
                        untdovr_gateway_field_label('No') => 0,
                        untdovr_gateway_field_label('Yes') => 1
                    ),
                    'name' => 'sample',
                    'optional' => 1
                ),
                untdovr_gateway_field_label('Shop Name') => array(
                    'text' => true,
                    'name' => 'shopname',
                    'optional' => 1,
                ),
                untdovr_gateway_field_label('Pattern Code') => array(
                    'text' => true,
                    'name' => 'patterncode',
                    'optional' => 1
                ),
                untdovr_gateway_field_label('Pattern Variables') => array(
                    'textarea' => true,
                    'name' => 'patternvars',
                    'optional' => 1
                ),
                untdovr_gateway_field_label('Panel URL. Default: sms.rangine.ir') => array(
                    'text' => true,
                    'name' => 'domain',
                    'optional' => 0,
                ),
                untdovr_gateway_field_label('International API Key (Optional)') => array(
                    'text' => true,
                    'name' => 'internationalapi',
                    'optional' => 1
                ),
            )
        ),*/
        'turkeysms' => array(
            'value' => 156,
            'require_addon' => 1,
            'label' => 'Turkey SMS',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'sozuri' => array(
            'value' => 157,
            'require_addon' => 1,
            'label' => 'Sozuri',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Project') => array('text' => true, 'name' => 'project'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
                untdovr_gateway_field_label('Type') => array('select' => true, 'name' => 'sms_type', 'options' => array('Transactional' => 'transactional', 'Promotional' => 'promotional')),
            ),
        ),
        'kivalo' => array(
            'value' => 158,
            'require_addon' => 1,
            'label' => 'Kivalo Solutions',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'sms_ninja' => array(
            'value' => 159,
            'require_addon' => 1,
            'label' => 'SMS.Ninja',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Device') => array('text' => true, 'name' => 'device', 'optional' => 1),
                untdovr_gateway_field_label('Sim') => array('text' => true, 'name' => 'sim', 'optional' => 1),
            ),
        ),
        'sms_mode' => array(
            'value' => 160,
            'require_addon' => 1,
            'label' => 'SMS Mode',
            'inputs' => array(
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Emetteur') => array('text' => true, 'name' => 'emetteur', 'optional' => 1),
            ),
        ),
        'brandedsmspakistan' => array(
            'value' => 161,
            'require_addon' => 1,
            'label' => 'Branded SMS Pakistan',
            'inputs' => array(
                /*untdovr_gateway_field_label('X-Rapid API Key') => array('text' => true, 'name' => 'xrapid_api_key'),
                untdovr_gateway_field_label('X-Rapid API Host') => array('text' => true, 'name' => 'xrapid_api_host'),*/
                untdovr_gateway_field_label('Account API Key') => array('text' => true, 'name' => 'account_api_key'),
                untdovr_gateway_field_label('Account Email') => array('text' => true, 'name' => 'email'),
                untdovr_gateway_field_label('Masking') => array('text' => true, 'name' => 'mask'),
            ),
        ),
        'sms_routee' => array(
            'value' => 162,
            'require_addon' => 1,
            'label' => 'Routee',
            'inputs' => array(
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'web2sms237' => array(
            'value' => 163,
            'require_addon' => 1,
            'label' => 'Web2SMS237',
            'inputs' => array(
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'beeline' => array(
            'value' => 164,
            'require_addon' => 1,
            'label' => 'Beeline',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'sms_cc' => array(
            'value' => 165,
            'require_addon' => 1,
            'label' => 'SMS.CC',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Domain') => array('text' => true, 'name' => 'domain'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'kavenegar' => array(
            'value' => 166,
            'require_addon' => 1,
            'label' => 'Kavenegar',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'nhn_toast' => array(
            'value' => 167,
            'require_addon' => 1,
            'label' => 'NHN Toast',
            'inputs' => array(
                untdovr_gateway_field_label('APP Key') => array('text' => true, 'name' => 'app_key'),
                untdovr_gateway_field_label('Secret Key') => array('text' => true, 'name' => 'secret_key'),
                untdovr_gateway_field_label('Sender number') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'hubtel' => array(
            'value' => 168,
            'require_addon' => 1,
            'label' => 'Hubtel',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'globelabs' => array(
            'value' => 169,
            'require_addon' => 1,
            'label' => 'Globe Labs',
            'inputs' => array(
                untdovr_gateway_field_label('Access Token') => array('text' => true, 'name' => 'access_token'),
                untdovr_gateway_field_label('Sender Address (last 4 digits)') => array('text' => true, 'name' => 'senderAddress'),
            ),
        ),
        'wholesale' => array(
            'value' => 170,
            'require_addon' => 1,
            'label' => 'WholesaleSMS',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('From') => array('text' => true, 'name' => 'from', 'optional' => 1),
            ),
        ),
        'turbosmsbiz' => array(
            'value' => 171,
            'require_addon' => 1,
            'label' => 'TurboSMS.biz',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
            ),
        ),
        'mobilenetsa' => array(
            'value' => 172,
            'require_addon' => 1,
            'label' => 'Mobile.net.sa',
            'inputs' => array(
                untdovr_gateway_field_label('Username') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),
        'eskizeu' => array(
            'value' => 173,
            'require_addon' => 1,
            'label' => 'Eskiz.uz',
            'inputs' => array(
                untdovr_gateway_field_label('Email') => array('text' => true, 'name' => 'username'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'oursms' => array(
            'value' => 174,
            'require_addon' => 1,
            'label' => 'OurSMS',
            'inputs' => array(
                untdovr_gateway_field_label('API Token') => array('text' => true, 'name' => 'api_token'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender'),
            ),
        ),
        'beemafrica' => array(
            'value' => 175,
            'require_addon' => 1,
            'label' => 'Beem Africa',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Secret Key') => array('text' => true, 'name' => 'secret_key'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'source_addr'),
            ),
        ),
        'turbosmstop' => array(
            'value' => 176,
            'require_addon' => 1,
            'label' => 'TurboSMS.top',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
            ),
        ),
        'quicksmsxyz' => array(
            'value' => 177,
            'require_addon' => 1,
            'label' => 'QuickSMS.xyz',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
            ),
        ),
        'telnor' => array(
            'value' => 178,
            'require_addon' => 1,
            'label' => 'Telenor',
            'inputs' => array(
                untdovr_gateway_field_label('Msisdn') => array('text' => true, 'name' => 'msisdn'),
                untdovr_gateway_field_label('Password') => array('text' => true, 'name' => 'password'),
                untdovr_gateway_field_label('Mask') => array('text' => true, 'name' => 'mask', 'optional' => 1),
                untdovr_gateway_field_label('Operator ID') => array('text' => true, 'name' => 'operator_id', 'optional' => 1),
            ),
        ),

        'fasttosms' => array(
            'value' => 179,
            'require_addon' => 1,
            'label' => 'Fast2SMS',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('Template ID') => array('text' => true, 'name' => 'template-id'),
                untdovr_gateway_field_label('Sender ID') => array('text' => true, 'name' => 'sender_id'),
            ),
        ),

        'sendlime' => array(
            'value' => 180,
            'require_addon' => 1,
            'label' => 'SendLime',
            'inputs' => array(
                untdovr_gateway_field_label('API Key') => array('text' => true, 'name' => 'api_key'),
                untdovr_gateway_field_label('API Secret') => array('text' => true, 'name' => 'api_secret'),
                untdovr_gateway_field_label('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
            ),
        ),

        'aliyun' => array(
            'value' => 181,
            'label' => 'Aliyun',
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Access Key') => array('text' => true, 'name' => 'access_key'),
                    __('Access Secret') => array('text' => true, 'name' => 'access_secret'),
                    __('Region (Optional)') => array('text' => true, 'name' => 'region', 'optional' => 1),
                    __('API Version (Optional)') => array('text' => true, 'name' => 'api_version', 'optional' => 1),
                    __('Sign Name') => array('text' => true, 'name' => 'signature'),
                    __('Template Code') => array('text' => true, 'name' => 'template_code')
                )
        ),

        'smsconnexion' => array(
            'value' => 182,
            'label' => 'SMS Connexion',
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('API Token') => array('text' => true, 'name' => 'access_token'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1),
                )
        ),

        'afilnet' => array(
            'value' => 183,
            'label' => 'AFILNET',
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Username') => array('text' => true, 'name' => 'username'),
                    __('Password') => array('text' => true, 'name' => 'password'),
                    __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1),
                )
        ),


        'tencent' => array(
            'value' => 184,
            'require_addon' => 1,
            'label' => 'Tencent',
            'inputs' => array(
                __('Secret ID') => array(
                    'text' => true,
                    'name' => 'secret_id'
                ),
                __('Secret Key') => array(
                    'text' => true,
                    'name' => 'secret_key'
                ),
                __('Region') => array(
                    'text' => true,
                    'name' => 'region'
                ),
                __('Template ID') => array(
                    'text' => true,
                    'name' => 'template_id'
                ),
                __('Sign Name') => array(
                    'text' => true,
                    'name' => 'sign_name'
                ),
                __('SMS SDK App ID') => array(
                    'text' => true,
                    'name' => 'sms_sdk_app_id'
                ),
            ),
        ),
        'sms4world' => array(
            'value' => 185,
            'require_addon' => 1,
            'label' => 'SMS4World',
            'inputs' => array(
                __('Username') => array(
                    'text' => true,
                    'name' => 'username'
                ),
                __('Password') => array(
                    'text' => true,
                    'name' => 'password'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender'
                ),
            ),
        ),
        'enablex' => array(
            'value' => 186,
            'require_addon' => 1,
            'label' => 'Enablex',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Campaign ID') => array(
                    'text' => true,
                    'name' => 'campaign_id'
                ),
            ),
        ),
        'trumpia' => array(
            'value' => 187,
            'require_addon' => 1,
            'label' => 'Trumpia',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Username') => array(
                    'text' => true,
                    'name' => 'username'
                ),
            ),
        ),
        'truedialog' => array(
            'value' => 188,
            'require_addon' => 1,
            'label' => 'TrueDialog',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Account ID') => array(
                    'text' => true,
                    'name' => 'account_id'
                ),
            ),
        ),
        'worldtext' => array(
            'value' => 189,
            'require_addon' => 1,
            'label' => 'WorldText',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Account ID') => array(
                    'text' => true,
                    'name' => 'account_id'
                ),
            ),
        ),
        'directsms' => array(
            'value' => 190,
            'require_addon' => 1,
            'label' => 'DirectSMS',
            'inputs' => array(
                __('Username') => array(
                    'text' => true,
                    'name' => 'username'
                ),
                __('Password') => array(
                    'text' => true,
                    'name' => 'password'
                ),
                __('Sender ID') => array(
                    'text' => true,
                    'name' => 'sender_id'
                ),
            ),
        ),

        'textdrip' => array(
            'value' => 191,
            'require_addon' => 1,
            'label' => 'TextDrip',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                )
            ),
        ),

        'authkey' => array(
            'value' => 192,
            'require_addon' => 1,
            'label' => 'Authkey.io',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_id'
                )
            ),
        ),
        'dexatel' => array(
            'value' => 193,
            'require_addon' => 1,
            'label' => 'Dexatel',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_name'
                )
            ),
        ),
        'dinahosting' => array(
            'value' => 194,
            'require_addon' => 1,
            'label' => 'Dinahosting',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Account Number') => array(
                    'text' => true,
                    'name' => 'account_number'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_name'
                )
            ),
        ),
        'mobtexting' => array(
            'value' => 195,
            'require_addon' => 1,
            'label' => 'Mobtexting',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_name'
                )
            ),
        ),

        'nextsms' => array(
            'value' => 196,
            'require_addon' => 1,
            'label' => 'NextSMS',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_name'
                )
            ),
        ),

        'octopush' => array(
            'value' => 197,
            'require_addon' => 1,
            'label' => 'Octopush',
            'inputs' => array(
                __('API Key') => array(
                    'text' => true,
                    'name' => 'api_key'
                ),
                __('API Login') => array(
                    'text' => true,
                    'name' => 'api_login'
                ),
                __('Sender') => array(
                    'text' => true,
                    'name' => 'sender_name'
                )
            ),
        ),

        'smseagle' => array(
            'value' => 198,
            'require_addon' => 1,
            'label' => 'SMSEagle',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
            ),
        ),
        'smstech' => array(
            'value' => 199,
            'require_addon' => 1,
            'label' => 'SMSTech',
            'inputs' => array(
                __('Access Token') => array(
                    'text' => true,
                    'name' => 'access_token'
                ),
            ),
        ),

    );
}

