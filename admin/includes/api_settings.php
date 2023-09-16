<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once 'gateway_functions.php';
require_once 'email_gateways.php';
require_once 'gateway_list.php';
require_once 'email_gateways_functions.php';

function digit_getGatewayName($digit_tapp)
{
    switch ($digit_tapp) {
        case 2:
            return "Twilio";
            break;
        case 3:
            return "Msg91";
            break;
        case 4:
            return "Yunpian";
            break;
        default:
            return '';
            break;
    }
}

function digits_admin_show_notices()
{
    $digpc = dig_get_option('dig_purchasecode');
    $request_link = esc_attr(admin_url('admin.php?page=digits_settings&tab=dashboard'));

    $purchase_link = 'https://1.envato.market/0zxKP';
    if (empty($digpc)) {
        $notice_links = [
            ['label' => __('Register', 'digits'), 'url' => $request_link, 'class' => 'digits_show_purchasecode'],
            ['label' => __('Buy Now', 'digits'), 'url' => $purchase_link, 'target' => '_blank'],
        ];
        $notice_text = __('You are using trial version of Digits, please register the plugin using purchase code or else purchase a license to use the plugin with all of its features.', 'digits');
        digits_show_notice($notice_text, $notice_links, false);
    }


    if (strpos(home_url(), "http://") !== false) {

        $request_link = esc_attr(admin_url('options-general.php'));
        $notice_links = [
            ['label' => __('Fix It', 'digits'), 'url' => $request_link],
        ];
        $notice_text = __('We\'ve noticed that your site is using insecure protocol (http) instead of secure protocol (https), so we request you change it from your WordPress General settings.', 'digits');
        digits_show_notice($notice_text, $notice_links, false);

    }

    if (isset($_POST['del_dig_resume_configuration_wizard'])) {
        delete_site_option('dig_resume_configuration_wizard');
    }

    if (!isset($_REQUEST['resume_configuration_wizard'])) {
        if (!wp_is_mobile()) {
            $resume_configuration_wiz = get_site_option('dig_resume_configuration_wizard', 0);

            if ($resume_configuration_wiz == 1) {
                $request_link = esc_attr(admin_url('admin.php?page=digits_settings&tab=dashboard&resume_configuration_wizard=1'));
                $notice_links = [
                    ['label' => __('Resume', 'digits'), 'url' => $request_link],
                ];
                $notice_text = __('You left your Digits plugin configuration in the middle, resume it to setup the plugin.', 'digits');
                digits_show_notice($notice_text, $notice_links, 'del_dig_resume_configuration_wizard');
            }
        }
    }
}

add_action('admin_notices', 'digits_admin_show_notices');


function digit_apisettings()
{

    ?>


    <h1><?php _e("API Settings", "digits"); ?></h1>
    <p class="lead"></p>

    <form method="post">
        <?php
        digits_api_settings();
        ?>


        <p class="digits-setup-action step">
            <Button type="submit"
                    class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></Button>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=documentation'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
    </form>

    <?php
}

function digit_test_api_box($type = 'phone')
{
    $countrycode = esc_attr(get_the_author_meta('digt_countrycode', get_current_user_id()));
    if (empty($countrycode)) {
        $countrycode = getUserCountryCode();
    }
    $user = wp_get_current_user();
    $email = $user->user_email;
    ?>
    <div class="dig_api_test">
        <div class="dig_gateway_sep_line"></div>

        <div class="dig_call_test_api">
            <div class="dig_admin_sec_head"><span><?php _e('Test Gateway Settings', 'digits'); ?></span></div>
            <div class="dig_test_mob_ho">
                <table class="form-table">
                    <?php
                    if ($type == 'email') {
                        ?>
                        <tr>
                            <th>
                                <label>
                                    <?php _e('Email Address', 'digits'); ?>
                                </label>
                            </th>
                            <td>
                                <div class="dig_test_number_wrapper">
                                    <div class="user_email_wrapper">
                                        <input dig-save="0" class="user_email" type="text"
                                               placeholder="<?php _e('Your Email Address', 'digits'); ?>"
                                               value="<?php echo esc_attr($email); ?>"
                                               name="email"></div>

                                    <div class="dig_call_test_api_btn"><?php _e('Send Test', 'digits'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <tr>
                            <th>
                                <label>
                                    <?php _e('Phone Number', 'digits'); ?>
                                </label>
                            </th>
                            <td>
                                <div class="dig_test_number_wrapper">
                                    <div class="digcon">
                                        <div class="dig_wc_countrycodecontainer dig_wc_logincountrycodecontainer"
                                             style="display: inline-block;">
                                            <input dig-save="0" type="text" name="digt_countrycode"
                                                   class="input-text countrycode dig_wc_logincountrycode"
                                                   value="<?php echo $countrycode; ?>" maxlength="6" size="3"
                                                   placeholder="<?php echo $countrycode; ?>"
                                                   autocomplete="tel-country-code">
                                        </div>
                                        <input dig-save="0" class="mobile" type="text"
                                               placeholder="<?php _e('Your Phone Number', 'digits'); ?>"
                                               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>"
                                               name="mobile/email" style="padding-left:107px !important;"></div>

                                    <div class="dig_call_test_api_btn"><?php _e('Send Test', 'digits'); ?></div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <th></th>
                        <td>
                            <div class="dig_call_test_response">
                                <div class="dig_call_test_response_wrapper">
                                    <div class="dig_call_test_response_head"><?php _e('Gateway Response', 'digits'); ?></div>
                                    <div class="dig_call_test_response_msg"></div>
                                </div>
                                <p class="dig_ecr_desc">
                                    <?php
                                    esc_attr_e("This response is from your gateway company. If the response looks good and you didn't receive a message then please contact your gateway support.", 'digits');
                                    ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php
}

function getWhatsAppGateWayArray()
{
    $gateways = array(
        __('Disabled', 'digits') => array(
            'value' => -1,
            'inputs' =>
                array(),
        ),
        'Twilio' => array(
            'value' => 2,
            'inputs' =>
                array(
                    __('Twilio Account SID') => array('text' => true, 'name' => 'account_sid'),
                    __('Twilio Auth Token') => array('text' => true, 'name' => 'auth_token'),
                    __('Whatsapp Number') => array('text' => true, 'name' => 'whatsappnumber')
                ),
        ),
        'MessageBird' => array(
            'value' => 3,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Access Key') => array('text' => true, 'name' => 'accesskey'),
                    __('Whatsapp Channel ID') => array('text' => true, 'name' => 'channel_id'),
                    __('Template Name') => array('text' => true, 'name' => 'template-name'),
                    __('Namespace') => array('text' => true, 'name' => 'namespace'),
                    __('Language') => array('text' => true, 'name' => 'language'),
                ),
        ),


        'Karix' => array(
            'value' => 4,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('UID') => array('text' => true, 'name' => 'uid'),
                    __('Token') => array('text' => true, 'name' => 'token'),
                    __('Sender') => array('text' => true, 'name' => 'sender'),

                ),
        ),
        'Gupshup' => array(
            'value' => 5,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('APP Name') => array('text' => true, 'name' => 'app_name'),
                    __('Source') => array('text' => true, 'name' => 'source'),
                    __('Template ID') => array('text' => true, 'name' => 'template_id'),
                ),
        ),
        'threesixtydialog' => array(
            'value' => 6,
            'require_addon' => 1,
            'label' => '360dialog',
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        ),
        'damcorp' => array(
            'value' => 7,
            'require_addon' => 1,
            'label' => 'Damcorp',
            'inputs' => array(
                __('API Token') => array('text' => true, 'name' => 'api_token'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
            ),
        ),
        'spoki' => array(
            'value' => 8,
            'require_addon' => 1,
            'label' => 'Spoki',
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
            ),
        ),
        'wati' => array(
            'value' => 9,
            'require_addon' => 1,
            'label' => 'Wati',
            'inputs' => array(
                __('Base URL') => array('text' => true, 'name' => 'base_url'),
                __('Access Token') => array('text' => true, 'name' => 'access_token'),
                __('Broadcast Name') => array('text' => true, 'name' => 'broadcast-name'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
            ),
        ),
        'whatsapp_cloud' => array(
            'value' => 10,
            'require_addon' => 1,
            'label' => 'WhatsApp Cloud API',
            'inputs' => array(
                __('Access Token') => array('text' => true, 'name' => 'access_token'),
                __('From Phone Number ID') => array('text' => true, 'name' => 'from_number_id'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        ),
        'msg91_whatsapp' => array(
            'value' => 11,
            'require_addon' => 1,
            'label' => 'Msg91',
            'inputs' => array(
                __('Authentication Key') => array('text' => true, 'name' => 'auth_key'),
                __('From') => array('text' => true, 'name' => 'from'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        ),

        'chat_api_whatsapp' => array(
            'value' => 13,
            'require_addon' => 1,
            'label' => 'ChatAPI',
            'inputs' => array(
                __('Authentication Key') => array('text' => true, 'name' => 'api_key'),
                __('Instance ID') => array('text' => true, 'name' => 'instance_id'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language')
            ,
            ),
        ),
        'interakt_whatsapp' => array(
            'value' => 14,
            'require_addon' => 1,
            'label' => 'Interakt',
            'inputs' => array(
                __('Authentication Key') => array('text' => true, 'name' => 'api_key'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        ),
        'kaylera_whatsapp' => array(
            'value' => 15,
            'require_addon' => 1,
            'label' => 'Kaylera',
            'inputs' => array(
                __('Authentication Key') => array('text' => true, 'name' => 'api_key'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        ),
        'wa_team_whatsapp' => array(
            'value' => 16,
            'require_addon' => 1,
            'label' => 'WATeam',
            'inputs' => array(
                __('Authentication Key') => array('text' => true, 'name' => 'api_key'),
                __('URL') => array('text' => true, 'name' => 'url'),
                __('Template Name') => array('text' => true, 'name' => 'template-name'),
                __('Namespace') => array('text' => true, 'name' => 'namespace'),
                __('Language') => array('text' => true, 'name' => 'language'),
            ),
        )
    );

    return $gateways;
}

function getGateWayArray()
{

    /// next 300
    $smsgateways = array(
        'Firebase' => array(
            'value' => 13,
            'group' => 'starting_group',
            'data-test' => 0,
            'inputs' =>
                array(__('Firebase Config') => array('textarea' => true, 'name' => 'config'))
        ),
        'BulkSMSbd' => array(
            'value' => 3936,
            'inputs' =>
                array(
                    __('API key') => array('text' => true, 'name' => 'api_key'),
                    __('Sender ID') => array('text' => true, 'name' => 'senderid'),
                ),
        ),
        'facebook' => array(
            'value' => 1,
            'label' => 'Account Kit',
            'group' => 'starting_group',
            'data-test' => 0,
            'inputs' => array()
        ),
        'Twilio' => array(
            'value' => 2,
            'group' => 'starting_group',
            'inputs' => array()
        ),
        'Msg91' => array(
            'value' => 3,
            'group' => 'starting_group',
            'inputs' => array()
        ),
        'MessageBird' => array(
            'value' => 8,
            'group' => 'starting_group',
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'accesskey'),
                __('Originator') => array('text' => true, 'name' => 'originator')
            )
        ),
        'Plivo' => array(
            'value' => 11,
            'group' => 'starting_group',
            'inputs' => array(
                __('Auth ID') => array('text' => true, 'name' => 'auth_id'),
                __('Auth Token') => array(
                    'text' => true,
                    'name' => 'auth_token'
                ),
                __('Sender') => array('text' => true, 'name' => 'sender_id', 'optional' => 1)
            )
        ),
        'ClickSend' => array(
            'value' => 6,
            'group' => 'starting_group',
            'inputs' => array(
                __('API Username') => array(
                    'text' => true,
                    'name' => 'apiusername'
                ),
                __('API Key') => array('text' => true, 'name' => 'apikey'),
                __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),
        'Infobip' => array(
            'value' => 32,
            'group' => 'starting_group',
            'inputs' =>
                array(
                    __('Base URL') => array('text' => true, 'name' => 'base_url'),
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                )
        ),
        'Amazon SNS' => array(
            'value' => 25,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Access Key ID') => array('text' => true, 'name' => 'access_key'),
                    __('Secret Access Key') => array('text' => true, 'name' => 'access_secret'),
                    __('Region') => array('text' => true, 'name' => 'region'),
                    __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                    __('Template ID') => array('text' => true, 'name' => 'template_id', 'optional' => 1),
                    __('Entity ID') => array('text' => true, 'name' => 'entity_id', 'optional' => 1),
                ),
        ),
        'Amazon Pinpoint' => array(
            'value' => 30,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Application ID/Project ID') => array('text' => true, 'name' => 'app_id'),
                    __('Access Key ID') => array('text' => true, 'name' => 'access_key'),
                    __('Secret Access Key') => array('text' => true, 'name' => 'access_secret'),
                    __('Region') => array('text' => true, 'name' => 'region'),
                    __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                    __('Template ID') => array('text' => true, 'name' => 'template_id', 'optional' => 1),
                    __('Entity ID') => array('text' => true, 'name' => 'entity_id', 'optional' => 1),
                ),
        ),

        'Alibaba' => array(
            'value' => 18,
            'label' => 'Alibaba',
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Access Key') => array('text' => true, 'name' => 'access_key'),
                    __('Access Secret') => array('text' => true, 'name' => 'access_secret'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                )
        ),

        'Alibaba Go China' => array(
            'value' => 33,
            'label' => 'Alibaba (Go China)',
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Access Key') => array('text' => true, 'name' => 'access_key'),
                    __('Access Secret') => array('text' => true, 'name' => 'access_secret'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1),
                    __('TemplateParam') => array('text' => true, 'name' => 'templatecode'),
                    __('SmsUpExtendCode') => array('text' => true, 'name' => 'frosmsupextendcodem', 'optional' => 1)
                )
        ),

        'Clickatell' => array(
            'value' => 5,
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
                __('From') => array(
                    'text' => true,
                    'name' => 'from',
                    'optional' => 1
                )
            )
        ),
        'ClockWork' => array(
            'value' => 7,
            'inputs' => array(
                __('ClockWork API') => array(
                    'text' => true,
                    'name' => 'clockworkapi'
                ),
                __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),
        'Kaleyra' => array(
            'value' => 15,
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
                __('SID') => array('text' => true, 'name' => 'sid'),
                __('Sender ID') => array('text' => true, 'name' => 'sender_id'),
                __('Template ID') => array('text' => true, 'name' => 'template_id', 'optional' => 1)
            )
        ),
        'Mobily.ws' => array(
            'value' => 9,
            'require_addon' => 1,
            'inputs' => array(
                __('Mobile') => array('text' => true, 'name' => 'mobile'),
                __('Password') => array('text' => true, 'name' => 'password'),
                __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
            )
        ),
        'Alfa Cell' => array(
            'value' => 28,
            'require_addon' => 1,
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
                __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
            )
        ),
        'Nexmo' => array(
            'value' => 10,
            'inputs' => array(
                __('API Key') => array('text' => true, 'name' => 'api_key'),
                __('API Secret') => array(
                    'text' => true,
                    'name' => 'api_secret'
                ),
                __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),
        /*'CM'          => array(
            'value'  => 27,
            'inputs' => array(
                __( 'API Key' ) => array(
                    'text' => true,
                    'name' => 'api_key'
                ),
                __( 'From' )    => array( 'text' => true, 'name' => 'from' )
            )
        ),*/
        'SMSAPI' => array(
            'value' => 12,
            'inputs' => array(
                __('Token') => array('text' => true, 'name' => 'token'),
                __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
            )
        ),

        'Africas Talking' => array(
            'value' => 26,
            'inputs' =>
                array(
                    __('Username') => array('text' => true, 'name' => 'username'),
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                )
        ),

        'Textlocal' => array(
            'value' => 17,
            'inputs' =>
                array(
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                )
        ),
        'Unifonic' => array(
            'value' => 14,
            'inputs' =>
                array(
                    __('AppSid') => array('text' => true, 'name' => 'appsid'),
                    __('Sender ID') => array('text' => true, 'name' => 'senderid', 'optional' => 1)
                )
        ),

        'Melipayamak' => array(
            'value' => 16,
            'inputs' => array(
                __('Username') => array('text' => true, 'name' => 'username'),
                __('Password') => array('text' => true, 'name' => 'password'),
                __('From') => array('text' => true, 'name' => 'from', 'optional' => 1),

            )
        ),


        'ADNSMS' => array(
            'value' => 19,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('API Secret') => array('text' => true, 'name' => 'api_secret')
                )
        ),

        'Netgsm' => array(
            'value' => 20,
            'inputs' =>
                array(
                    __('Username') => array('text' => true, 'name' => 'username'),
                    __('Password') => array('text' => true, 'name' => 'password'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                )
        ),

        'SMSC.ru' => array(
            'value' => 21,
            'inputs' =>
                array(
                    __('Login') => array('text' => true, 'name' => 'login'),
                    __('Password') => array('text' => true, 'name' => 'password'),
                    __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                )
        ),
        'TargetSMS' => array(
            'value' => 22,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Login') => array('text' => true, 'name' => 'login'),
                    __('Password') => array('text' => true, 'name' => 'password'),
                    __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                )
        ),

        'Ghasedak' => array(
            'value' => 23,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('API Key') => array('text' => true, 'name' => 'api_key')
                )
        ),
        'Farapayamak' => array(
            'value' => 24,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Username') => array('text' => true, 'name' => 'username'),
                    __('Password') => array('text' => true, 'name' => 'password'),
                    __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                )
        ),
        'SendinBlue' => array(
            'value' => 31,
            'inputs' =>
                array(
                    __('API Key') => array('text' => true, 'name' => 'api_key'),
                    __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                )
        ),
        'IBulksms' => array(
            'value' => 29,
            'require_addon' => 1,
            'inputs' =>
                array(
                    __('Auth Key') => array('text' => true, 'name' => 'auth_key'),
                    __('Sender ID') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                )
        ),
        'Yunpian' => array(
            'value' => 4,
            'inputs' => array()
        ),

    );

    $smsgateways = apply_filters('digits_sms_gateways', $smsgateways);

    return $smsgateways;
}


add_filter('digits_sms_gateways', 'unitedover_custom_gateway_option', 100);
if (!function_exists('unitedover_custom_gateway_option')) {
    function unitedover_custom_gateway_option($smsgateways)
    {

        $placeholder = 'to:{to}, message:{message}, sender:{sender_id}';
        $desc = '<i>' . __('Enter Parameters separated by "," and values by ":"', 'digits') . '</i><br />';
        $desc .= 'To : {to}<br /> Message : {message}<br /> Sender ID : {sender_id}<br /> OTP : {otp}';

        $custom = array(
            'custom_gateway' => array(
                'value' => 900,
                'group' => esc_attr__('Custom Gateway', 'digits'),
                'label' => esc_attr__('Custom', 'digits'),
                'inputs' => array(
                    __('SMS Gateway URL') => array('text' => true, 'name' => 'gateway_url', 'placeholder' => 'https://www.example.com/send'),
                    __('HTTP Header') => array('textarea' => true, 'name' => 'http_header', 'rows' => 3, 'optional' => 1, 'desc' => esc_attr__('Headers separated by ","', 'digits')),
                    __('HTTP Method') => array('select' => true, 'name' => 'http_method', 'options' => array('GET' => 'GET', 'POST' => 'POST')),
                    __('Gateway Parameters') => array('textarea' => true, 'name' => 'gateway_attributes', 'rows' => 6, 'desc' => $desc, 'placeholder' => $placeholder),
                    __('Send as Body Data') => array('select' => true, 'name' => 'send_body_data', 'options' => array('No' => 0, 'Yes' => 1)),
                    __('Encode Message') => array('select' => true, 'name' => 'encode_message', 'options' => array(__('URL Encode') => 1, __('URL Raw Encode') => 3, __('No') => 0, __('Convert To Unicode') => 2)),
                    __('Phone Number') => array('select' => true, 'name' => 'phone_number', 'options' => array(__('with + and country code', 'digits') => 1, __('with only country code') => 2, __('without country code') => 3)),
                    __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                ),
            ),
        );

        return array_merge($smsgateways, $custom);
    }
}

function digits_api_settings()
{


    $digit_tapp = get_option('digit_tapp', 13);

    $app = get_option('digit_api');
    $appid = "";
    $appsecret = "";
    $accountkit_type = "";
    if ($app !== false) {
        $appid = $app['appid'];
        $appsecret = $app['appsecret'];
        if (isset($app['accountkit_type'])) {
            $accountkit_type = $app['accountkit_type'];
        } else {
            $accountkit_type = "modal";
        }
    }

    $tiwilioapicred = get_option('digit_twilio_api');
    $twiliosid = "";
    $twiliotoken = "";
    $twiliosenderid = "";


    if ($tiwilioapicred !== false) {
        $twiliosid = $tiwilioapicred['twiliosid'];
        $twiliotoken = $tiwilioapicred['twiliotoken'];
        $twiliosenderid = $tiwilioapicred['twiliosenderid'];
    }


    $msg91apicred = get_option('digit_msg91_api');
    $msg91authkey = "";
    $msg91senderid = "";
    $msg91dlt_te_id = "";
    $msg91route = 1;
    if ($msg91apicred !== false) {
        $msg91authkey = $msg91apicred['msg91authkey'];
        $msg91senderid = $msg91apicred['msg91senderid'];
        $msg91route = $msg91apicred['msg91route'];

        if (isset($msg91apicred['msg91dlt_te_id'])) {
            $msg91dlt_te_id = $msg91apicred['msg91dlt_te_id'];
        }
        if (empty($msg91route)) {
            $msg91route = 2;
        }
    }


    $yunpianapi = get_option('digit_yunpianapi');

    $smsgateways = getGateWayArray();
    ?>

    <div class="dig_admin_head"><span><?php _e('SMS Gateway', 'digits'); ?></span></div>
    <div class="dig_admin_tab_grid">
        <div class="dig_admin_tab_grid_elem">
            <input type="hidden" class="dig_save" value='1' name="dig_save"/>
            <div class="digits_gateway_container digits_gateway_api_box">
                <table class="form-table digits_default_gateway_details gateway_table">
                    <?php digit_select_gateway('name="digit_tapp" id="digit_tapp"', $digit_tapp); ?>

                    <tr class="facebookcred gateway_conf" <?php if ($digit_tapp != 1) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label for="appid"><?php _e('App ID', 'digits'); ?> </label></th>
                        <td>
                            <input type="text" id="appid" name="appid" class="regular-text"
                                   value="<?php echo $appid; ?>"
                                   placeholder="<?php _e('App ID', 'digits'); ?>"
                                   autocomplete="off"/>
                        </td>
                    </tr>
                    <tr class="facebookcred gateway_conf" <?php if ($digit_tapp != 1) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label for="appsecret"><?php _e('AccountKit App Secret', 'digits'); ?> </label>
                        </th>
                        <td>
                            <input type="text" id="appsecret" name="appsecret" class="regular-text"
                                   value="<?php echo $appsecret; ?>" autocomplete="off"
                                   placeholder="<?php _e('App Secret', 'digits'); ?>"/>
                        </td>
                    </tr>

                    <tr class="facebookcred gateway_conf" <?php if ($digit_tapp != 1) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label for="accountkit_type"><?php _e('Type', 'digits'); ?> </label></th>
                        <td>
                            <select name="accountkit_type">
                                <option value="modal" <?php if ($accountkit_type == 'modal') {
                                    echo "selected='selected'";
                                } ?>><?php _e('Modal', 'digits'); ?></option>
                                <option value="popup" <?php if ($accountkit_type == 'popup') {
                                    echo "selected='selected'";
                                } ?>><?php _e('Popup', 'digits'); ?></option>
                            </select>

                            <p class="dig_ecr_desc">
                                <?php _e('Only use Popup if your website is non https:// otherwise we highly recommend using modal.', 'digits'); ?>
                            </p>
                        </td>
                    </tr>


                    <tr class="twiliocred gateway_conf" <?php if ($digit_tapp != 2) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label for="twiliosid"><?php _e('Account SID', 'digits'); ?> </label></th>
                        <td>
                            <input type="text" id="twiliosid" name="twiliosid" class="regular-text"
                                   value="<?php echo $twiliosid; ?>"
                                   placeholder="<?php _e('Account SID', 'digits'); ?>"
                                   autocomplete="off"/>
                        </td>
                    </tr>
                    <tr class="twiliocred gateway_conf" <?php if ($digit_tapp != 2) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label for="twiliotoken"><?php _e('Auth Token', 'digits'); ?> </label></th>
                        <td>
                            <input type="text" id="twiliotoken" name="twiliotoken" class="regular-text"
                                   value="<?php echo $twiliotoken; ?>" autocomplete="off"
                                   placeholder="<?php _e('Auth Token', 'digits'); ?>"/>
                        </td>
                    </tr>
                    <tr class="twiliocred gateway_conf" <?php if ($digit_tapp != 2) {
                        echo 'style="display:none;"';
                    } ?> >
                        <th scope="row"><label
                                    for="twiliosenderid"><?php _e('Sender ID (From number)', 'digits'); ?> </label>
                        </th>
                        <td>
                            <input type="text" id="twiliosenderid" name="twiliosenderid" class="regular-text"
                                   value="<?php echo $twiliosenderid; ?>" autocomplete="off"
                                   placeholder="<?php _e('Sender ID', 'digits'); ?>"/>
                        </td>
                    </tr>

                    <tr class="msg91cred gateway_conf" <?php if ($digit_tapp != 3) {
                        echo 'style="display:none;"';
                    } ?>>
                        <th scope="row"><label for="msg91authkey"><?php _e('Authentication Key', 'digits'); ?> </label>
                        </th>
                        <td>
                            <input type="text" id="msg91authkey" name="msg91authkey" class="regular-text"
                                   value="<?php echo $msg91authkey; ?>" autocomplete="off"
                                   placeholder="<?php _e('Authentication Key', 'digits'); ?>"/>
                        </td>
                    </tr>
                    <tr class="msg91cred gateway_conf" <?php if ($digit_tapp != 3) {
                        echo 'style="display:none;"';
                    } ?>>
                        <th scope="row"><label for="msg91route"><?php _e('ROUTE', 'digits'); ?> </label></th>
                        <td>
                            <select name="msg91route">
                                <option value="1" <?php if ($msg91route == 1) {
                                    echo "selected='selected'";
                                } ?>><?php _e('SendOTP', 'digits'); ?></option>
                                <option value="2" <?php if ($msg91route == 2) {
                                    echo "selected='selected'";
                                } ?>><?php _e('Transactional', 'digits'); ?></option>
                            </select>
                            <p class="dig_ecr_desc">
                                If your website users are only from <b>India</b> then you can use <b>Transactional</b>
                                or
                                <b>SendOTP</b> route. But if your users are from any other <b>country than India</b>
                                then you
                                should
                                only use <b>SendOTP</b> route.
                            </p>
                        </td>
                    </tr>
                    <tr class="msg91cred gateway_conf" <?php if ($digit_tapp != 3) {
                        echo 'style="display:none;"';
                    } ?>>
                        <th scope="row"><label for="msg91senderid"><?php _e('Sender ID', 'digits'); ?> </label></th>
                        <td>
                            <input type="text" id="msg91senderid" name="msg91senderid" class="regular-text"
                                   value="<?php echo $msg91senderid; ?>" autocomplete="off"
                                   placeholder="<?php _e('Sender ID', 'digits'); ?>"/>
                        </td>
                    </tr>

                    <tr class="msg91cred gateway_conf" <?php if ($digit_tapp != 3) {
                        echo 'style="display:none;"';
                    } ?>>
                        <th scope="row"><label for="msg91dlt_te_id"><?php _e('DLT Template ID', 'digits'); ?> </label>
                        </th>
                        <td>
                            <input type="text" id="msg91dlt_te_id" name="msg91dlt_te_id" class="regular-text"
                                   value="<?php echo $msg91dlt_te_id; ?>" autocomplete="off"
                                   placeholder="<?php _e('DLT Template ID', 'digits'); ?>"/>
                        </td>
                    </tr>

                    <tr class="yunpiancred gateway_conf" <?php if ($digit_tapp != 4) {
                        echo 'style="display:none;"';
                    } ?>>
                        <th scope="row"><label for="yunpianapikey"><?php _e('API Key', 'digits'); ?> </label></th>
                        <td>
                            <input type="text" id="yunpianapikey" name="yunpianapikey" class="regular-text"
                                   value="<?php echo $yunpianapi; ?>" autocomplete="off"
                                   placeholder="<?php _e('API Key', 'digits'); ?>"/>
                            <p class="dig_ecr_desc"><?php _e('Please keep this message template similar to the one on Yunpian, just replace #code# with {OTP} otherwise messages will not be sent.', 'digits'); ?></p>
                        </td>
                    </tr>

                    <?php
                    dig_show_gateway_api_fields($smsgateways, $digit_tapp, '');
                    ?>


                </table>


                <?php
                $dig_messagetemplate = get_option("dig_messagetemplate", digits_default_otp_template());

                $dig_messagetemplate = trim($dig_messagetemplate);
                ?>
                <table class="form-table">
                    <tr class="disotp" <?php if ($digit_tapp == 13) echo 'style="display:none;"'; ?>>
                        <th scope="row" style="vertical-align:top;"><label
                                    for="dig_messagetemplate"><?php _e('Message Template', 'digits'); ?></label></th>
                        <td>
                    <textarea name="dig_messagetemplate" placeholder="Message Template" class="dig_inp_wid3"
                              required><?php echo $dig_messagetemplate; ?></textarea>
                            <p class="dig_ecr_desc">
                                <?php _e('Site Name', 'digits'); ?> - {NAME}<br/>
                                <?php _e('Domain', 'digits'); ?> - {DOMAIN}<br/>
                                <?php _e('OTP', 'digits'); ?> - {OTP}

                        </td>
                    </tr>
                </table>
                <?php
                digits_otp_resend_time('mob');
                digit_test_api_box();
                ?>
            </div>
            <?php
            do_action('digits_api_settings');
            ?>
        </div>

        <div class="dig_admin_tab_grid_elem dig_admin_tab_grid_sec">
            <?php
            $text = __('For SMS gateways besides Firebase, we recommend testing your gateway beforehand to avoid confusion and ensure successful message delivery.', 'digits');
            digits_settings_show_hint($text);
            ?>

        </div>
    </div>
    <?php
}

/*
 * TODO: remove iniFireBaseinit after 7.1
 * */
function digit_select_gateway($gatewayAttributes, $digit_tapp = -1, $smsgateways = array(), $gateway_type = 'sms')
{

    if (empty($smsgateways)) {
        $loadDefault = true;
        $smsgateways = getGateWayArray();
    }

    $prefix = '';
    $td_class = '';

    if ($gateway_type == 'email') {
        $td_class = 'dig-email-gs-gatway-select-td';
        $gatewayLabel = __('Email Gateway', 'digits');
        $prefix = 'email';
    } else if ($gateway_type == 'whatsapp') {
        $td_class = 'dig-whatsapp-gs-gatway-select-td';
        $gatewayLabel = __('WhatsApp Gateway', 'digits');
        $prefix = 'whatsapp';
    } else {
        $td_class = 'dig-gs-gatway-select-td';
        $gatewayLabel = __('SMS Gateway', 'digits');
    }
    $gatewayName = digit_getGatewayName($digit_tapp);
    iniFireBaseinit();
    ?>

    <tr>
        <th scope="row" valign="top" style="vertical-align: top;">
            <label><?php echo $gatewayLabel; ?> </label></th>
        <td class="<?php echo esc_attr($td_class); ?>">

            <select class="digit_gateway" <?php echo $gatewayAttributes; ?> autocomplete="off">
                <?php

                $list = apply_filters('digits_addon', array());
                $additional_gateway_installed = !in_array('additional-gateways', $list) ? false : true;

                $gateway_groups = apply_filters('digits_group_gateways_list', $smsgateways);
                foreach ($gateway_groups as $group_name => $gateway_group) {

                    $optgroup_label = 'label="' . esc_attr__($group_name) . '"';
                    if ($group_name == 'starting_group') {
                        $optgroup_label = '';
                    }
                    if ($group_name != 'hide')
                        echo '<optgroup ' . $optgroup_label . '>';

                    foreach ($gateway_group as $name => $details) {

                        $sel = "";
                        $value = $details['value'];

                        $data_test = isset($details['data-test']) ? 'data-test="0"' : '';

                        if ($value == $digit_tapp) {

                            $gatewayName = $name;
                            $sel = 'selected="selected"';
                        }

                        $han = digits_strtolower(str_replace(array(".", " "), "_", $prefix . $name));

                        $gateway_label = isset($details['label']) ? $details['label'] : $name;

                        $require_addon = (isset($details['require_addon']) && !$additional_gateway_installed) ? $details['require_addon'] : 0;

                        echo '<option data-addon="' . $require_addon . '" data-value="' . $value . '" value="' . $value . '" ' . $sel . ' han="' . $han . '" ' . $data_test . '>' . $gateway_label . '</option>';
                    }
                    if ($group_name != 'hide')
                        echo '</optgroup>';
                }
                ?>
            </select><br/>
            <div>
                <?php if ($gateway_type == 'sms') { ?>
                    <span class="dig_current_gateway"
                          style="<?php if ($digit_tapp == 1 || $digit_tapp == 13 || $digit_tapp == -1 || $digit_tapp == 900) {
                              echo 'display:none;';
                          } ?>">
                        <?php printf(__('You should have paid <span>%s</span> plan to use this.', 'digits'), $gatewayName); ?>
                    </span>
                <?php } ?>

                <?php

                if (!$additional_gateway_installed) { ?>
                    <p class="dig_ecr_desc require_addon_text"><?php esc_html_e('Please install Additional Gateways addon to use this gateway.'); ?>
                    <a href="#" class="digits_install_additional_gateways"><?php esc_html_e('Click Here'); ?></a>
                    </p><?php
                } ?>
            </div>

        </td>
    </tr>
    <?php
}


function dig_show_gateway_api_fields($smsgateways, $digit_tapp, $prefix = '')
{
    $list = apply_filters('digits_addon', array());
    $additional_gateway_installed = in_array('additional-gateways', $list);

    foreach ($smsgateways as $name => $details) {
        $value = $details['value'];
        $name = str_replace(array(".", " "), "_", $prefix . digits_strtolower($name));

        $gatewayCreds = get_option('digit_' . digits_strtolower($name));


        foreach ($details['inputs'] as $inputLabel => $input) {
            $inputname = $name . "_" . $input['name'];
            if (isset($gatewayCreds[$input['name']])) {
                $inputValue = stripslashes($gatewayCreds[$input['name']]);
            } else {
                $inputValue = '';
            }
            $optional = 0;
            if (isset($input['optional'])) {
                $optional = $input['optional'];
            }

            $attrs = array();

            if (!$additional_gateway_installed) {
                if (isset($details['require_addon']) && $details['require_addon'] == 1) {
                    $attrs[] = 'disabled="disabled"';
                }
            }
            $attrs = implode(" ", $attrs);
            ?>
            <tr class="<?php echo $name; ?>cred gateway_conf" <?php if ($digit_tapp != $value) {
                echo 'style="display:none;"';
            } ?>>
                <th scope="row"><label for="<?php echo $inputname; ?>"> <?php _e($inputLabel, 'digits');
                        if ($optional == 1) {
                            //  echo ' (Optional)';
                        } ?> </label></th>
                <td>
                    <?php
                    $placeholder = esc_attr__($inputLabel, 'digits');
                    if (isset($input['placeholder'])) {
                        $placeholder = $input['placeholder'];
                    }
                    if (isset($input['textarea'])) {
                        $rows = isset($input['rows']) ? $input['rows'] : 9;
                        ?>
                        <textarea type="text" id="<?php echo $inputname; ?>"
                                  name="<?php echo $inputname; ?>"
                                  class="regular-text"
                                  autocomplete="off"
                                  rows="<?php echo $rows; ?>"
                                  placeholder="<?php echo $placeholder; ?>"
                                  <?php echo $attrs; ?>
                                  dig-optional="<?php echo $optional; ?>"><?php echo $inputValue; ?></textarea>
                        <?php
                    } else if (isset($input['options'])) {
                        $options = $input['options'];
                        ?>
                        <select id="<?php echo $inputname; ?>" name="<?php echo $inputname; ?>"
                                dig-optional="<?php echo $optional; ?>" <?php echo $attrs; ?>>
                            <?php
                            foreach ($options as $option => $option_value) {
                                $option = esc_attr($option);
                                $sel = '';
                                if ($option_value == $inputValue) $sel = 'selected';
                                echo '<option value="' . $option_value . '" ' . $sel . '>' . $option . '</option>';
                            }
                            ?>

                        </select>
                        <?php
                    } else {
                        ?>
                        <input type="text" id="<?php echo $inputname; ?>" name="<?php echo $inputname; ?>"
                               class="regular-text"
                               value="<?php echo $inputValue; ?>" autocomplete="off"
                               placeholder="<?php echo $placeholder; ?>"
                            <?php echo $attrs; ?>
                               dig-optional="<?php echo $optional; ?>"/>
                        <?php
                    }

                    if (isset($input['desc'])) {
                        echo '<p class="dig_ecr_desc">';
                        echo $input['desc'];
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
    }
}

function digits_update_api_settings()
{
    $smsgateways = getGateWayArray();

    digits_update_gateway_api_details($smsgateways, '');
    digits_update_gateway_api_details(getWhatsAppGateWayArray(), 'whatsapp');

    digits_update_gateway_api_details(digits_getEmailGateWayArray(), 'email');

    update_option('digit_whatsapp_gateway', sanitize_text_field($_POST['digit_whatsapp_gateway']));

    update_option('digit_email_gateway', sanitize_text_field($_POST['digit_email_gateway']));

}

function digits_update_gateway_api_details($smsgateways, $prefix)
{

    foreach ($smsgateways as $name => $details) {
        $name = digits_strtolower(str_replace([".", " "], "_", $name));
        $gatewaycred = array();
        foreach ($details['inputs'] as $inputlabel => $input) {

            if (!isset($_POST[$prefix . $name . "_" . $input['name']])) {
                continue;
            }

            if (isset($input['textarea'])) {
                $inputValue = $_POST[$prefix . $name . "_" . $input['name']];
            } else {
                $inputValue = $_POST[$prefix . $name . "_" . $input['name']];
            }

            $gatewaycred[$input['name']] = $inputValue;

        }
        update_option('digit_' . $prefix . digits_strtolower($name), $gatewaycred);
    }
}