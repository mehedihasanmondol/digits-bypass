<?php


use Mailgun\Mailgun;

if (!defined('ABSPATH')) {
    exit;
}


function digits_get_email_gateway()
{
    return get_option('digit_email_gateway', 2);
}

function digit_send_email($to, $subject, $body, $to_name = null)
{
    $email_gateway = digits_get_email_gateway();
    return digit_send_email_gateway($email_gateway, $to, $subject, $body, $to_name);
}

function digit_send_email_gateway($digit_gateway, $to, $subject, $body, $to_name = null)
{

    $protected_by_html = '';
    $show_protected_by_digits = show_protected_by_digits();
    if ($show_protected_by_digits == 1) {
        $protected_by_html = digits_protected_by_email_html();
    }

    $body = str_replace("{{protected-by-digits}}", $protected_by_html, $body);

    $prefix = 'email';

    $site_url = home_url();
    $site_url = str_replace("http://", "", $site_url);
    $site_url = str_replace("https://", "", $site_url);
    $admin_email = 'no-reply@' . $site_url;

    $from_name = get_bloginfo('name');

    switch ($digit_gateway) {
        case 2:
            $gateway_cred = get_option('digit_' . $prefix . 'wp_mail');
            $from = $gateway_cred['from'];
            if (empty($from)) {
                $from = $admin_email;
            }
            $headers = array(
                'From: ' . $from_name . ' <' . $from . '>',
                'Content-Type: text/html; charset=UTF-8');
            return wp_mail($to, $subject, $body, $headers);
        case 3:
            $gateway_cred = get_option('digit_' . $prefix . 'sendgrid');
            $api_key = $gateway_cred['api_key'];

            if (empty($from)) {
                $from = $admin_email;
            }

            try {
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom($from, $from_name);
                $email->setSubject($subject);
                $email->addTo($to, $to_name);
                $email->addContent(
                    "text/html", $body
                );
                $sendgrid = new \SendGrid($api_key);
                $response = $sendgrid->send($email);
                return $response;
            } catch (Exception $e) {
                return $e->getMessage();
            }

        case 4:
            $gateway_cred = get_option('digit_' . $prefix . 'mailgun');

            if (empty($from)) {
                $from = $admin_email;
            }

            $api_key = $gateway_cred['api_key'];
            $domain = $gateway_cred['domain'];

            try {
                $mg = Mailgun::create($api_key);

                return $mg->messages()->send($domain, [
                    'from' => $from,
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $body
                ]);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        default:
            return false;
    }
}


function digit_send_test_email($gateway, $to)
{
    $subject = 'Test Email -  Digits';
    $body = '<h1>Hello, This is a test mail from Digits</h1>';
    return digit_send_email_gateway($gateway, $to, $subject, $body, null);
}

function digits_protected_by_email_html()
{
    return '<div class="u-row-container hide-mobile" style="padding: 0px;background-color: transparent">
                <div class="u-row"
                     style="Margin: 0 auto;min-width: 320px;max-width: 600px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                        <!--[if (mso)|(IE)]>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 0px;background-color: transparent;" align="center">
                                    <table cellpadding="0" cellspacing="0" border="0" style="width:600px;">
                                        <tr style="background-color: transparent;"><![endif]-->

                        <!--[if (mso)|(IE)]>
                        <td align="center" width="300"
                            style="width: 300px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"
                            valign="top"><![endif]-->
                        <div class="u-col u-col-50"
                             style="max-width: 320px;min-width: 300px;display: table-cell;vertical-align: top;">
                            <div style="width: 100% !important;">
                                <!--[if (!mso)&(!IE)]><!-->
                                <div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;">
                                    <!--<![endif]-->

                                    <table id="u_content_text_12" style="font-family:arial,helvetica,sans-serif;"
                                           role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                        <tbody>
                                        <tr>
                                            <td style="overflow-wrap:break-word;word-break:break-word;padding:6px 2px 6px 6px;font-family:arial,helvetica,sans-serif;"
                                                align="left">

                                                <div class="v-text-align"
                                                     style="color: #3b4961; line-height: 140%; text-align: right; word-wrap: break-word;">
                                                    <p style="font-size: 14px; line-height: 140%;"><span
                                                                style="font-family: helvetica, sans-serif; font-size: 14px; line-height: 19.6px;"><strong><span
                                                                        style="font-size: 16px; line-height: 22.4px;">Protected </span></strong>
                                  </span><span style="font-family: helvetica, sans-serif; font-size: 14px; line-height: 19.6px;"><strong><span
                                                                        style="font-size: 16px; line-height: 22.4px;">by</span></strong>
                                  </span>
                                                    </p>
                                                </div>

                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <!--[if (!mso)&(!IE)]><!-->
                                </div>
                                <!--<![endif]-->
                            </div>
                        </div>
                        <!--[if (mso)|(IE)]></td><![endif]-->
                        <!--[if (mso)|(IE)]>
                        <td align="center" width="300"
                            style="width: 300px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"
                            valign="top"><![endif]-->
                        <div class="u-col u-col-50"
                             style="max-width: 320px;min-width: 300px;display: table-cell;vertical-align: top;">
                            <div style="width: 100% !important;">
                                <!--[if (!mso)&(!IE)]><!-->
                                <div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;">
                                    <!--<![endif]-->

                                    <table id="u_content_image_4" style="font-family:arial,helvetica,sans-serif;"
                                           role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                        <tbody>
                                        <tr>
                                            <td style="overflow-wrap:break-word;word-break:break-word;padding:6px;font-family:arial,helvetica,sans-serif;"
                                                align="left">

                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td class="v-text-align"
                                                            style="padding-right: 0px;padding-left: 0px;" align="left">

                                                            <img align="left" border="0"
                                                                 src="https://digits.b-cdn.net/wp-content/uploads/2020/03/DigitsLogo48retinawhitev3-1.png"
                                                                 alt="" title=""
                                                                 style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 25%;max-width: 72px;"
                                                                 width="72" class="v-src-width v-src-max-width"/>

                                                        </td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <!--[if (!mso)&(!IE)]><!-->
                                </div>
                                <!--<![endif]-->
                            </div>
                        </div>
                        <!--[if (mso)|(IE)]></td><![endif]-->
                        <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                    </div>
                </div>
            </div>';
}