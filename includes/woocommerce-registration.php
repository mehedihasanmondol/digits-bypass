<?php


use DigitsFormHandler\UserRegistration;

if (!defined('ABSPATH')) {
    exit;
}


add_action('user_register', 'digits_add_custom_reg_fields_wp_new_user');

function digits_add_custom_reg_fields_wp_new_user($user_id)
{

    if (is_user_logged_in() && current_user_can('edit_user')) {
        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);
        update_digp_reg_fields($reg_custom_fields, $user_id);
    }
}

add_action("user_new_form", "digits_add_new_userpage");

function digits_add_new_userpage()
{

    if (is_rtl()) {
        echo '<style>.digcon{float: right;}</style>';
    } ?>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            var createuser = jQuery("#createuser");
            createuser.find("#email").closest(".form-required").removeClass("form-required").find(".description").remove();

            var ul = createuser.find("#user_login");
            ul.attr('id', "#wp_user_login").closest('tr').find('label').attr('for', 'wp_user_login');

            ul.closest("tr").after('<tr class="form-field">' +
                '<th scope="row">' +
                '<label for="user_login"><?php _e("Mobile Number", "digits")?></label>' +
                '</th>' +
                '<td><input name="dig_user_mobile" id="username" value="" type="text" f-mob="1" nan="1" only-mob="1"></td>' +
                '</tr>');


        });
    </script>

    <?php
    echo '<table class="form-table">';
    show_digp_reg_fields(3, null, 0);
    echo '</table>';
    digits_add_style();
    digits_add_scripts();
}


add_action('show_user_profile', 'dig_show_extra_profile_fields', 100, 10);
add_action('edit_user_profile', 'dig_user_profile_update');

function dig_user_profile_update($user)
{
    dig_show_extra_profile_fields($user, true);


}


function dig_show_extra_profile_fields($user, $admin = false)
{
    $phone = get_the_author_meta('digits_phone', $user->ID);
    ?>
    <h3><?php _e('Important Contact Info', 'digits'); ?></h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="phone"><?php _e('Mobile Number', 'digits'); ?></label>
            </th>
            <td style="position: relative;">

                <div class="digits_edit_user_phone_div">
                    <input type="hidden" name="code" id="dig_prof_code">
                    <input type="hidden" name="csrf" id="dig_prof_csrf">
                    <input type="hidden" name="dig_old_phone" class="dig_cur_phone"
                           value="<?php echo esc_attr($phone); ?>"/>
                    <input type="text" autocomplete="off"
                           countryCode="<?php echo esc_attr(get_the_author_meta('digt_countrycode', $user->ID)); ?>"
                           data-dig-mob="1" name="dig_user_mobile" id="username"
                           nan="1"
                           data-type="2"
                           value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', $user->ID)); ?>"
                           class="regular-text mobile_number" f-mob="1"/>
                </div>
                <?php if (is_rtl()) {
                    echo '<br /><br />';
                } ?><span class="description"><?php _e('Please enter your Mobile Number.', 'digits'); ?></span>
            </td>
        </tr>
        <?php
        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);

        show_digp_reg_fields(3, null, $user->ID, $reg_custom_fields);
        ?>
        <?php
        if (current_user_can('edit_users')) {

            $digits_data = get_user_meta($user->ID, 'digits_form_data', true);
            if (!empty($digits_data) && is_array($digits_data)) {


                $defined_fields = digits_wp_wc_fields_list();

                foreach ($reg_custom_fields as $field) {
                    $defined_fields[] = $field['meta_key'];

                }

                foreach ($digits_data as $data_meta_key => $data) {
                    if (in_array($data_meta_key, $defined_fields)) continue;

                    $values = get_user_meta($user->ID, $data_meta_key, true);
                    if (empty($values)) continue;
                    ?>
                    <tr>
                        <th>
                            <label><?php esc_html_e($data['label']); ?></label>
                        </th>
                        <td style="position: relative;">
                            <?php
                            if (is_array($values)) {
                                $values = implode(',', $values);
                                echo '<input type="hidden" name="digits_field_' . esc_html($data_meta_key) . '_array" value="1" />';
                            }
                            ?>
                            <input type="hidden" name="digits_undefined_fields[]"
                                   value="<?php esc_html_e($data_meta_key); ?>"/>
                            <input type="text" name="digits_field_<?php echo esc_html($data_meta_key) ?>"
                                   class="regular-text"
                                   value="<?php esc_html_e($values); ?>"/>
                        </td>
                    </tr>
                    <?php
                }
            }
        }
        ?>

    </table>


    <table class="form-table digits-edit-phone_otp-container" dis="no" style="display: none;">
        <tr>
            <th>
                <label for="profile_update_otp"><?php _e("OTP", "digits"); ?></label>
            </th>
            <td>
                <input type="hidden" name="dig_nounce" class="dig_nounce"
                       value=" <?php echo wp_create_nonce('dig_form') ?>">
                <input type="text" name="profile_update_otp" id="profile_update_otp"
                       class="regular-text digits_otp_field"/>
            </td>
        </tr>
    </table>

    <?php
    if (is_rtl()) {
        echo '<style>.digcon{float: right;}</style>';
    }


    if (is_admin()) {
        $user_id = $user->ID;
        $verified_email = get_user_meta($user_id, UserRegistration::USER_VERIFIED_EMAIL, true);

        $verified_html = '<span class="digits_admin_verify_status_ic digits_admin_verified" title="' . esc_attr__('%s Verified by Digits', 'digits') . '"></span>';
        $not_verified_html = '<span class="digits_admin_verify_status_ic digits_admin_not_verified" title="' . esc_attr__('%s Not Verified by Digits', 'digits') . '"></span>';


        $email_verify_html = '';
        if (!empty($user->user_email)) {
            $email_verify_html = $not_verified_html;

            if (!empty($verified_email) && !empty($user->user_email)) {
                if ($verified_email == $user->user_email) {
                    $email_verify_html = $verified_html;
                }
            }
            $email_verify_html = sprintf($email_verify_html, __('Email', 'digits'));
        }

        $phone_verify_html = '';

        if (!empty($phone)) {
            $phone_verify_html = $verified_html;
            $phone_verified = get_user_meta($user_id, 'digits_phone_verification_skipped', true);
            if (!empty($phone_verified)) {
                $phone_verify_html = $not_verified_html;
            }

            $phone_verify_html = sprintf($phone_verify_html, __('Phone', 'digits'));
        }


        ?>
        <script>
            jQuery(function () {
                jQuery('#email').parent().append('<?php echo $email_verify_html;?>');
                jQuery('.digits_edit_user_phone_div').append('<?php echo $phone_verify_html;?>');
            })
        </script>
        <style>
            .digits_admin_verify_status_ic {
                position: relative;
            }

            .digits_admin_verify_status_ic::before {
                background-repeat: no-repeat;
                content: ' ';
                height: 30px;
                display: inline-block;
                position: relative;
                top: 9px;
            }

            .digits_edit_user_phone_div .digits_admin_verify_status_ic::before {
                top: 2px;
            }

            .digits_admin_verified::before {
                width: 140px;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='138' height='30' viewBox='0 0 138 30'%3E%3Cg id='phone-email-verified' transform='translate(935 1960)'%3E%3Crect id='Rectangle_212' data-name='Rectangle 212' width='138' height='30' rx='4' transform='translate(-935 -1960)' fill='%23fff'/%3E%3Cpath id='Rectangle_212_-_Outline' data-name='Rectangle 212 - Outline' d='M4,1A3,3,0,0,0,1,4V26a3,3,0,0,0,3,3H134a3,3,0,0,0,3-3V4a3,3,0,0,0-3-3H4M4,0H134a4,4,0,0,1,4,4V26a4,4,0,0,1-4,4H4a4,4,0,0,1-4-4V4A4,4,0,0,1,4,0Z' transform='translate(-935 -1960)' fill='%23ebfcf5'/%3E%3Crect id='Rectangle_214' data-name='Rectangle 214' width='30' height='30' rx='4' transform='translate(-935 -1960)' fill='%23ebfcf5'/%3E%3Cg id='Group_162' data-name='Group 162' transform='translate(-928.989 -1954.002)'%3E%3Cpath id='Path_31' data-name='Path 31' d='M8.995,1a3.617,3.617,0,0,1,2.774,1.284q.157-.014.315-.014a3.636,3.636,0,0,1,3.622,3.95,3.637,3.637,0,0,1,0,5.546,3.615,3.615,0,0,1-1.049,2.881,3.66,3.66,0,0,1-2.573,1.068q-.153,0-.3-.013a3.637,3.637,0,0,1-5.558,0q-.155.013-.312.013a3.636,3.636,0,0,1-3.623-3.94,3.637,3.637,0,0,1,0-5.567,3.636,3.636,0,0,1,3.623-3.94q.156,0,.313.014A3.617,3.617,0,0,1,8.995,1Zm2.45,2.8a.727.727,0,0,1-.612-.335,2.181,2.181,0,0,0-3.675,0,.727.727,0,0,1-.772.318,2.181,2.181,0,0,0-2.607,2.6.727.727,0,0,1-.321.772,2.181,2.181,0,0,0,0,3.686.727.727,0,0,1,.321.772,2.181,2.181,0,0,0,2.607,2.6.727.727,0,0,1,.773.319,2.181,2.181,0,0,0,3.681,0,.727.727,0,0,1,.771-.32,2.181,2.181,0,0,0,2.6-2.607.727.727,0,0,1,.318-.772,2.181,2.181,0,0,0,0-3.675.727.727,0,0,1-.318-.772A2.181,2.181,0,0,0,11.6,3.783.727.727,0,0,1,11.445,3.8Z' fill='%2300db82'/%3E%3Cpath id='Path_32' data-name='Path 32' d='M10.181,13.362a.725.725,0,0,1-.514-.213L8.213,11.7a.727.727,0,0,1,1.028-1.028l.94.94,2.394-2.394A.727.727,0,0,1,13.6,10.241L10.7,13.149A.725.725,0,0,1,10.181,13.362Z' transform='translate(-1.913 -2.182)' fill='%2300db82'/%3E%3C/g%3E%3Cpath id='Path_39' data-name='Path 39' d='M-34.12-10.182h-2.023L-32.559,0h2.277l3.589-10.182h-2.028l-2.645,8.014h-.1ZM-22.869.149c1.78,0,3-.87,3.321-2.2l-1.68-.189a1.593,1.593,0,0,1-1.616.984,1.881,1.881,0,0,1-1.949-2.073h5.32v-.552c0-2.68-1.611-3.858-3.49-3.858-2.187,0-3.614,1.606-3.614,3.962C-26.578-1.377-25.171.149-22.869.149Zm-1.919-4.688a1.835,1.835,0,0,1,1.849-1.795A1.7,1.7,0,0,1-21.2-4.539ZM-17.952,0h1.8V-4.489a1.632,1.632,0,0,1,1.72-1.656,3.439,3.439,0,0,1,.835.1V-7.7a4.321,4.321,0,0,0-.646-.05,1.907,1.907,0,0,0-1.884,1.382h-.08V-7.636h-1.745ZM-12.4,0h1.8V-7.636h-1.8Zm.9-8.72a1.01,1.01,0,0,0,1.039-.974,1.011,1.011,0,0,0-1.039-.979,1.013,1.013,0,0,0-1.044.979A1.012,1.012,0,0,0-11.494-8.72Zm6.612,1.084H-6.468v-.6c0-.6.249-.93.92-.93a2.312,2.312,0,0,1,.671.109l.363-1.392a4.487,4.487,0,0,0-1.367-.209,2.184,2.184,0,0,0-2.386,2.3v.721H-9.4v1.392h1.129V0h1.8V-6.244h1.586ZM-3.47,0h1.8V-7.636h-1.8Zm.9-8.72a1.01,1.01,0,0,0,1.039-.974,1.011,1.011,0,0,0-1.039-.979,1.013,1.013,0,0,0-1.044.979A1.012,1.012,0,0,0-2.565-8.72ZM3.56.149c1.78,0,3-.87,3.321-2.2L5.2-2.237a1.593,1.593,0,0,1-1.616.984A1.881,1.881,0,0,1,1.636-3.326h5.32v-.552c0-2.68-1.611-3.858-3.49-3.858C1.278-7.736-.149-6.13-.149-3.773-.149-1.377,1.258.149,3.56.149ZM1.641-4.539A1.835,1.835,0,0,1,3.49-6.334,1.7,1.7,0,0,1,5.23-4.539ZM11.31.134A2.3,2.3,0,0,0,13.5-1.2h.109V0h1.77V-10.182h-1.8v3.808H13.5a2.265,2.265,0,0,0-2.183-1.362c-1.765,0-3.152,1.382-3.152,3.928C8.163-1.293,9.511.134,11.31.134Zm.5-1.477C10.624-1.342,10-2.386,10-3.818s.616-2.441,1.815-2.441c1.158,0,1.795.96,1.795,2.441S12.961-1.342,11.812-1.342ZM20.856,0h1.77V-1.2h.1A2.3,2.3,0,0,0,24.918.134c1.8,0,3.147-1.427,3.147-3.942,0-2.545-1.387-3.928-3.152-3.928A2.265,2.265,0,0,0,22.73-6.374h-.075v-3.808h-1.8Zm1.765-3.818c0-1.482.636-2.441,1.795-2.441,1.2,0,1.815,1.019,1.815,2.441S25.6-1.342,24.415-1.342C23.267-1.342,22.621-2.337,22.621-3.818Zm7.835,6.682A2.6,2.6,0,0,0,33.1.925l3.067-8.551-1.924-.01L32.479-1.869H32.4L30.64-7.636H28.731L31.5.159l-.154.413c-.333.87-.86.93-1.646.7l-.418,1.4A3.374,3.374,0,0,0,30.456,2.864Z' transform='translate(-862 -1940)' fill='%231f3448'/%3E%3Cg id='Group_169' data-name='Group 169' transform='translate(-1105.131 -2377.877)'%3E%3Cg id='Group_165' data-name='Group 165' transform='translate(283.3 426.164)'%3E%3Cg id='Group_164' data-name='Group 164' transform='translate(0)'%3E%3Cpath id='Path_37' data-name='Path 37' d='M283.3,433.818l.99-3.052a35.453,35.453,0,0,1,4.965,2.078c-.264-2.59-.412-4.371-.429-5.344h3.118c-.049,1.419-.214,3.184-.495,5.328a37.052,37.052,0,0,1,5.064-2.062l.99,3.052a26.975,26.975,0,0,1-5.344,1.2,35.292,35.292,0,0,1,3.695,4.058l-2.573,1.831a53.864,53.864,0,0,1-2.936-4.619,35.557,35.557,0,0,1-2.788,4.619l-2.54-1.831a47.141,47.141,0,0,1,3.563-4.058C286.714,434.675,284.966,434.263,283.3,433.818Z' transform='translate(-283.3 -427.5)' fill='%23ffc700'/%3E%3C/g%3E%3C/g%3E%3Cg id='Group_167' data-name='Group 167' transform='translate(284.471 425.9)'%3E%3Cg id='Group_166' data-name='Group 166' transform='translate(0 0)'%3E%3Cpath id='Path_38' data-name='Path 38' d='M300.627,439.855l-.148-.2c-.775-1.056-1.7-2.491-2.738-4.256a31.9,31.9,0,0,1-2.606,4.256l-.148.2-2.936-2.111.165-.2c1.418-1.748,2.507-3,3.249-3.744-1.682-.33-3.3-.709-4.816-1.122l-.247-.066,1.138-3.53.247.082a39.369,39.369,0,0,1,4.586,1.88c-.247-2.342-.363-3.992-.379-4.9V425.9H299.6v.247c-.033,1.32-.181,2.953-.429,4.883a35.993,35.993,0,0,1,4.685-1.864l.231-.082,1.138,3.513-.231.082a26.589,26.589,0,0,1-4.866,1.138,40.812,40.812,0,0,1,3.315,3.728l.165.2Zm-2.9-5.46.231.379c1.056,1.814,2,3.3,2.788,4.388l2.161-1.534a35.092,35.092,0,0,0-3.5-3.827l-.429-.363.561-.066a25.236,25.236,0,0,0,5.064-1.122l-.841-2.573a40.718,40.718,0,0,0-4.8,1.963l-.412.214.066-.462c.264-2,.429-3.678.478-5.047h-2.606c.033,1.006.181,2.705.429,5.064l.05.478-.412-.231a31.419,31.419,0,0,0-4.685-1.979l-.825,2.557c1.567.412,3.249.792,4.982,1.138l.478.1-.363.33a42.557,42.557,0,0,0-3.365,3.827l2.128,1.534a36.794,36.794,0,0,0,2.623-4.388Z' transform='translate(-290.4 -425.9)' fill='%237e39ff'/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3C/g%3E%3C/svg%3E%0A");
            }

            .digits_admin_not_verified::before {
                width: 160px;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='166' height='30' viewBox='0 0 166 30'%3E%3Cg id='phone-email-not-verified' transform='translate(935 1960)'%3E%3Crect id='Rectangle_212' data-name='Rectangle 212' width='166' height='30' rx='4' transform='translate(-935 -1960)' fill='%23fff'/%3E%3Cpath id='Rectangle_212_-_Outline' data-name='Rectangle 212 - Outline' d='M4,1A3,3,0,0,0,1,4V26a3,3,0,0,0,3,3H162a3,3,0,0,0,3-3V4a3,3,0,0,0-3-3H4M4,0H162a4,4,0,0,1,4,4V26a4,4,0,0,1-4,4H4a4,4,0,0,1-4-4V4A4,4,0,0,1,4,0Z' transform='translate(-935 -1960)' fill='%23fff9e5'/%3E%3Crect id='Rectangle_214' data-name='Rectangle 214' width='30' height='30' rx='4' transform='translate(-935 -1960)' fill='%23fff9e5'/%3E%3Cpath id='Path_40' data-name='Path 40' d='M-39.216-10.182H-41.05v6.94h-.089l-4.793-6.94h-1.651V0h1.844V-6.935h.085L-40.857,0h1.641ZM-33.961.149c2.237,0,3.659-1.576,3.659-3.937s-1.422-3.947-3.659-3.947S-37.62-6.155-37.62-3.788-36.2.149-33.961.149Zm.01-1.442c-1.238,0-1.844-1.1-1.844-2.5s.607-2.516,1.844-2.516c1.218,0,1.825,1.119,1.825,2.516S-32.733-1.293-33.951-1.293Zm8.984-6.344h-1.506v-1.83h-1.8v1.83h-1.084v1.392h1.084V-2A2.056,2.056,0,0,0-25.887.1a3.617,3.617,0,0,0,1.059-.179l-.3-1.407a2.244,2.244,0,0,1-.527.07c-.452,0-.815-.159-.815-.885V-6.244h1.506Zm6.423-2.545h-2.023L-16.983,0h2.277l3.589-10.182h-2.028L-15.79-2.168h-.1ZM-7.293.149c1.78,0,3-.87,3.321-2.2l-1.68-.189a1.593,1.593,0,0,1-1.616.984A1.881,1.881,0,0,1-9.217-3.326H-3.9v-.552c0-2.68-1.611-3.858-3.49-3.858C-9.575-7.736-11-6.13-11-3.773-11-1.377-9.6.149-7.293.149ZM-9.212-4.539A1.835,1.835,0,0,1-7.363-6.334a1.7,1.7,0,0,1,1.74,1.795ZM-2.376,0h1.8V-4.489a1.632,1.632,0,0,1,1.72-1.656,3.439,3.439,0,0,1,.835.1V-7.7a4.321,4.321,0,0,0-.646-.05A1.907,1.907,0,0,0-.552-6.364h-.08V-7.636H-2.376ZM3.177,0h1.8V-7.636h-1.8Zm.9-8.72a1.01,1.01,0,0,0,1.039-.974,1.011,1.011,0,0,0-1.039-.979,1.013,1.013,0,0,0-1.044.979A1.012,1.012,0,0,0,4.082-8.72Zm6.612,1.084H9.108v-.6c0-.6.249-.93.92-.93a2.312,2.312,0,0,1,.671.109l.363-1.392a4.487,4.487,0,0,0-1.367-.209,2.184,2.184,0,0,0-2.386,2.3v.721H6.18v1.392H7.308V0h1.8V-6.244h1.586ZM12.106,0h1.8V-7.636h-1.8Zm.9-8.72a1.01,1.01,0,0,0,1.039-.974,1.011,1.011,0,0,0-1.039-.979,1.013,1.013,0,0,0-1.044.979A1.012,1.012,0,0,0,13.011-8.72ZM19.136.149c1.78,0,3-.87,3.321-2.2l-1.68-.189a1.593,1.593,0,0,1-1.616.984,1.881,1.881,0,0,1-1.949-2.073h5.32v-.552c0-2.68-1.611-3.858-3.49-3.858-2.187,0-3.614,1.606-3.614,3.962C15.427-1.377,16.834.149,19.136.149ZM17.217-4.539a1.835,1.835,0,0,1,1.849-1.795,1.7,1.7,0,0,1,1.74,1.795ZM26.886.134A2.3,2.3,0,0,0,29.074-1.2h.109V0h1.77V-10.182h-1.8v3.808h-.075a2.265,2.265,0,0,0-2.183-1.362c-1.765,0-3.152,1.382-3.152,3.928C23.739-1.293,25.087.134,26.886.134Zm.5-1.477c-1.188,0-1.815-1.044-1.815-2.476s.616-2.441,1.815-2.441c1.158,0,1.795.96,1.795,2.441S28.537-1.342,27.388-1.342ZM36.432,0H38.2V-1.2h.1A2.3,2.3,0,0,0,40.494.134c1.8,0,3.147-1.427,3.147-3.942,0-2.545-1.387-3.928-3.152-3.928a2.265,2.265,0,0,0-2.183,1.362h-.075v-3.808h-1.8ZM38.2-3.818c0-1.482.636-2.441,1.795-2.441,1.2,0,1.815,1.019,1.815,2.441s-.626,2.476-1.815,2.476C38.843-1.342,38.2-2.337,38.2-3.818Zm7.835,6.682A2.6,2.6,0,0,0,48.677.925l3.067-8.551-1.924-.01L48.055-1.869h-.08l-1.76-5.767H44.307l2.769,7.8-.154.413c-.333.87-.86.93-1.646.7l-.418,1.4A3.374,3.374,0,0,0,46.032,2.864Z' transform='translate(-850 -1940)' fill='%231f3448'/%3E%3Cg id='Group_169' data-name='Group 169' transform='translate(-1077.3 -2377.877)'%3E%3Cg id='Group_165' data-name='Group 165' transform='translate(283.3 426.164)'%3E%3Cg id='Group_164' data-name='Group 164' transform='translate(0)'%3E%3Cpath id='Path_37' data-name='Path 37' d='M283.3,433.818l.99-3.052a35.453,35.453,0,0,1,4.965,2.078c-.264-2.59-.412-4.371-.429-5.344h3.118c-.049,1.419-.214,3.184-.495,5.328a37.052,37.052,0,0,1,5.064-2.062l.99,3.052a26.975,26.975,0,0,1-5.344,1.2,35.292,35.292,0,0,1,3.695,4.058l-2.573,1.831a53.864,53.864,0,0,1-2.936-4.619,35.557,35.557,0,0,1-2.788,4.619l-2.54-1.831a47.141,47.141,0,0,1,3.563-4.058C286.714,434.675,284.966,434.263,283.3,433.818Z' transform='translate(-283.3 -427.5)' fill='%23ffc700'/%3E%3C/g%3E%3C/g%3E%3Cg id='Group_167' data-name='Group 167' transform='translate(284.471 425.9)'%3E%3Cg id='Group_166' data-name='Group 166' transform='translate(0 0)'%3E%3Cpath id='Path_38' data-name='Path 38' d='M300.627,439.855l-.148-.2c-.775-1.056-1.7-2.491-2.738-4.256a31.9,31.9,0,0,1-2.606,4.256l-.148.2-2.936-2.111.165-.2c1.418-1.748,2.507-3,3.249-3.744-1.682-.33-3.3-.709-4.816-1.122l-.247-.066,1.138-3.53.247.082a39.369,39.369,0,0,1,4.586,1.88c-.247-2.342-.363-3.992-.379-4.9V425.9H299.6v.247c-.033,1.32-.181,2.953-.429,4.883a35.993,35.993,0,0,1,4.685-1.864l.231-.082,1.138,3.513-.231.082a26.589,26.589,0,0,1-4.866,1.138,40.812,40.812,0,0,1,3.315,3.728l.165.2Zm-2.9-5.46.231.379c1.056,1.814,2,3.3,2.788,4.388l2.161-1.534a35.092,35.092,0,0,0-3.5-3.827l-.429-.363.561-.066a25.236,25.236,0,0,0,5.064-1.122l-.841-2.573a40.718,40.718,0,0,0-4.8,1.963l-.412.214.066-.462c.264-2,.429-3.678.478-5.047h-2.606c.033,1.006.181,2.705.429,5.064l.05.478-.412-.231a31.419,31.419,0,0,0-4.685-1.979l-.825,2.557c1.567.412,3.249.792,4.982,1.138l.478.1-.363.33a42.557,42.557,0,0,0-3.365,3.827l2.128,1.534a36.794,36.794,0,0,0,2.623-4.388Z' transform='translate(-290.4 -425.9)' fill='%237e39ff'/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3Cg id='Group_172' data-name='Group 172' transform='translate(-928.989 -1954.002)'%3E%3Cpath id='Path_40-2' data-name='Path 40' d='M8.995,1a3.617,3.617,0,0,1,2.774,1.284q.157-.014.315-.014a3.636,3.636,0,0,1,3.622,3.95,3.637,3.637,0,0,1,0,5.546,3.615,3.615,0,0,1-1.049,2.881,3.66,3.66,0,0,1-2.573,1.068q-.153,0-.3-.013a3.637,3.637,0,0,1-5.558,0q-.155.013-.312.013a3.636,3.636,0,0,1-3.623-3.94,3.637,3.637,0,0,1,0-5.567,3.636,3.636,0,0,1,3.623-3.94q.156,0,.313.014A3.617,3.617,0,0,1,8.995,1Zm2.45,2.8a.727.727,0,0,1-.612-.335,2.181,2.181,0,0,0-3.675,0,.727.727,0,0,1-.772.318,2.181,2.181,0,0,0-2.607,2.6.727.727,0,0,1-.321.772,2.181,2.181,0,0,0,0,3.686.727.727,0,0,1,.321.772,2.181,2.181,0,0,0,2.607,2.6.727.727,0,0,1,.773.319,2.181,2.181,0,0,0,3.681,0,.727.727,0,0,1,.771-.32,2.181,2.181,0,0,0,2.6-2.607.727.727,0,0,1,.318-.772,2.181,2.181,0,0,0,0-3.675.727.727,0,0,1-.318-.772A2.181,2.181,0,0,0,11.6,3.783.727.727,0,0,1,11.445,3.8Z' fill='%23ffc700'/%3E%3Cpath id='Line_24' data-name='Line 24' d='M-.273,3.362A.727.727,0,0,1-1,2.635V-.273A.727.727,0,0,1-.273-1a.727.727,0,0,1,.727.727V2.635A.727.727,0,0,1-.273,3.362Z' transform='translate(9.268 6.364)' fill='%23ffc700'/%3E%3Cpath id='Line_25' data-name='Line 25' d='M-.266.454H-.273A.727.727,0,0,1-1-.273.727.727,0,0,1-.273-1h.007a.727.727,0,0,1,.727.727A.727.727,0,0,1-.266.454Z' transform='translate(9.268 12.18)' fill='%23ffc700'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E%0A");
            }
        </style>
        <?php

    }
}


add_action('user_profile_update_errors', 'validate_info', 10, 3);
function validate_info($errors, $update = null, $user = null)
{

    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);

    $validation_error = new WP_Error();
    $validation_error = validate_digp_reg_fields($reg_custom_fields, $validation_error, false);
    if ($validation_error->get_error_code()) {
        $errors->add("incompletedetails", $validation_error->get_error_message());
    }


    if ((!isset($_POST['dig_user_mobile']) || !isset($_POST['digt_countrycode'])) && !isset($_POST['mobile/email'])) {

        $errors->add('MobileNo', "<strong>" . __("Error", "digits") . "</strong>: " . __("Invalid Mobile Number!", "digits"));

    } else if ((!isset($_POST['digt_countrycode']) || !isset($_POST['mobile/email']) || !isset($_POST['dig_old_phone'])) && !isset($_POST['dig_user_mobile'])) {
        $errors->add('MobileNo', "<strong>" . __("Error", "digits") . "</strong>: " . __("Invalid Mobile Number!", "digits"));
    }

    if (isset($_POST['dig_user_mobile'])) {
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $phone = sanitize_text_field($_POST['dig_user_mobile']);


    } else if (isset($_POST['mobile/email'])) {
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $phone = sanitize_text_field($_POST['mobile/email']);
        $dig_old_phone = sanitize_text_field($_POST['dig_old_phone']);
    }

    if (empty($phone) && !empty($errors->get_error_message('empty_email'))) {
        $errors->add('mailormobile', "<strong>" . __("Error", "digits") . "</strong>: " . __("Please enter your email or Mobile Number!", "digits"));
    }

    if (empty($countrycode) ||
        !is_numeric($countrycode) ||
        strpos($countrycode, '+') !== 0) {
        $errors->add('MobileNo', "<strong>" . __("Error", "digits") . "</strong>: " . __("Invalid Country Code!", "digits"));
    }


    $errors->remove('empty_email');

    if (empty($phone)) {
        return;
    }


    $tempUser = getUserFromPhone($countrycode . $phone);

    if ($tempUser != null) {

        if (!isset($user->ID)) {
            $errors->add('MobileNoAlreadyInUse', "<strong>" . __("Error", "digits") . "</strong>: " . __("Mobile Number already in use!", "digits"));
        } else if ($tempUser->ID != $user->ID) {
            $errors->add('MobileNoAlreadyInUse', "<strong>" . __("Error", "digits") . "</strong>: " . __("Mobile Number already in use!", "digits"));
        }
    }


}


add_filter('woocommerce_checkout_fields', 'digits_override_checkout_fields', 2);

function digits_override_checkout_fields($fields)
{
    $dig_reqfieldbilling = get_option("dig_reqfieldbilling", 0);


    if ($dig_reqfieldbilling == 1) {
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['required'] = true;
        }
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['required'] = false;
        }

    } else if ($dig_reqfieldbilling == 2) {
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['required'] = false;
        }
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['required'] = true;
        }

    }


    $dig_reg_details = digit_get_reg_fields();
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    if ($mobileaccp > 0) {


        $fields['account']['mobile/email']['required'] = true;
        $fields['account']['mobile/email']['id'] = "username";

        $fields['account']['mobile/email']['priority'] = 1;
        $fields['account']['mobile/email']['class'] = array('form-row-wide');

        if ($mobileaccp == 2) {
            $fields['account']['mobile/email']['placeholder'] = __("Mobile Number", "digits");
            $fields['account']['mobile/email']['label'] = __("Mobile Number", "digits");
        } else {
            $fields['account']['mobile/email']['placeholder'] = __("Email/Mobile Number", "digits");
            $fields['account']['mobile/email']['label'] = __("Email/Mobile Number", "digits");
        }
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['label'] = __("Billing Email", "digits");
        }
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['label'] = __("Billing Mobile Number", "digits");
        }

    }


    $password = 'no' === get_option('woocommerce_registration_generate_password') ? false : true;

    if (!$password) {
        $fields['account']['account_password']['placeholder'] = __("Password", "digits");
        $fields['account']['account_password']['priority'] = 2;

        $isPassRequired = $dig_reg_details['dig_reg_password'];
        if ($isPassRequired == 2) {
            $fields['account']['account_password']['required'] = true;
        } else {
            $fields['account']['account_password']['required'] = false;
        }


        $fields['account']['account_password']['id'] = "billing_account_password";
        $fields['account']['account_password']['type'] = "password";
        $fields['account']['account_password']['label'] = __("Password", "digits");
        $fields['account']['account_password']['class'] = array('form-row-wide');

    }


    return $fields;
}

if (!function_exists('wc_create_new_customer')) {
    /**
     * Create a new customer.
     *
     * @param string $email Customer email.
     * @param string $username Customer username.
     * @param string $password Customer password.
     * @param array $args List of arguments to pass to `wp_insert_user()`.
     *
     * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
     */
    function wc_create_new_customer($email, $username = '', $password = '', $args = array())
    {

        $email = strtolower($email);

        if (email_exists($email)) {
            return new WP_Error('registration-error-email-exists', apply_filters('woocommerce_registration_error_email_exists', __('An account is already registered with your email address. Please log in.', 'woocommerce'), $email));
        }

        $validation_error = new WP_Error();

        $nonce_value = wc_get_var($_REQUEST['woocommerce-process-checkout-nonce'], ''); // @codingStandardsIgnoreLine.


        $is_checkout = false;
        if (empty($nonce_value) || !wp_verify_nonce($nonce_value, 'woocommerce-process_checkout')) {
            $is_checkout = false;

            if (empty($_REQUEST['secondmailormobile'])) {
                return dig_wc_create_new_customer($email, $username, $password, $args);
            }

            $m1 = sanitize_text_field($_REQUEST['email']);
            $m2 = sanitize_text_field($_REQUEST['secondmailormobile']);

            if (is_numeric($m1)) {
                $phone_number = $m1;
                $email = $m2;
            } else if (is_numeric($m2)) {
                $phone_number = $m2;
                $email = $m1;
            }

            $otp = sanitize_text_field($_POST['reg_billing_otp']);

            if (is_numeric($m1)) {
                $countrycode = sanitize_text_field($_REQUEST['digfcountrycode']);
            } else if (is_numeric($m2)) {
                $countrycode = sanitize_text_field($_REQUEST['digsfcountrycode2']);
            }

        } else {
            $is_checkout = true;
            $phone_number = $_POST['mobile/email'];
            $otp = sanitize_text_field($_POST['digit_ac_otp']);
            if (isset($_POST['digt_countrycode'])) {
                $countrycode = sanitize_text_field($_POST['digt_countrycode']);
            } else {
                $countrycode = sanitize_text_field($_POST['billing_phone_digt_countrycode']);
            }

        }


        $phone_number = sanitize_mobile_field_dig($phone_number);


        if ($is_checkout) {
            $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
            $reg_custom_fields = json_decode($reg_custom_fields, true);
            $validation_error = validate_digp_reg_fields($reg_custom_fields, $validation_error);
            if ($validation_error->get_error_code()) {
                return $validation_error;
            }
        }


        $code = sanitize_text_field($_POST['code']);
        $csrf = sanitize_text_field($_POST['csrf']);


        if ($is_checkout) {
            $dig_reg_details = digit_get_reg_fields();
        } else {
            $dig_reg_details = digit_get_reg_fields(true);
        }

        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];


        if ($passaccep == 1 && $mobileaccp == 1 && $emailaccep == 1) {
            if (empty($password)) {
                if (empty($email) && !isValidEmail($phone_number)) {
                    return new WP_Error('error', __('Either enter your Mobile Number or use Password!', 'digits'));
                }
            }
        }

        if ($mobileaccp == 2 && !is_numeric($phone_number)) {
            return new WP_Error('error', __('Please enter a valid Mobile Number!', 'digits'));
        }
        if ($emailaccep == 2 && (!isValidEmail($phone_number) && !isValidEmail($email))) {
            return new WP_Error('error', __('Please enter a valid Email!', 'digits'));
        }


        if (isValidEmail($phone_number)) {
            $email = $phone_number;
        }
        if (is_numeric($phone_number)) {


            if (dig_get_checkout_otp_verification() == 1) {
                if (empty($otp) && empty($code)) {

                    if ($is_checkout) {
                        return new WP_Error('error', __('Please signup before placing order!', 'digits'));
                    } else {
                        return new WP_Error('error', __('Please verify your mobile number!', 'digits'));
                    }
                }
            }
            if (!checkwhitelistcode($countrycode)) {
                return new WP_Error('error', __('Invalid Country Code!', 'digits'));
            }
            if (empty($phone_number) && empty($countrycode)) {
                return new WP_Error('error', __('Invalid Mobile Number!', 'digits'));
            }
            if (is_numeric($phone_number) && empty($countrycode)) {
                return new WP_Error('error', __('Invalid Country Code!', 'digits'));
            }
            if (dig_get_checkout_otp_verification() == 1) {


                if (dig_gatewayToUse($countrycode) != 1) {

                    if (is_numeric($phone_number) && empty($otp)) {
                        return new WP_Error('error', __('Invalid OTP!', 'digits'));
                    }
                    if (!verifyOTP($countrycode, $phone_number, $otp, false)) {
                        return new WP_Error('error', __('Unable to verify OTP!', 'digits'));
                    }
                }
            }
            if (getUserFromPhone($countrycode . $phone_number)) {
                return new WP_Error('error', __('Mobile Number already in use!', 'digits'));
            }
        } else if (empty($email)) {
            return new WP_Error('registration-error-invalid-email', __('Please provide a valid email address.', 'woocommerce'));
        }


        if (!is_numeric($phone_number) && !is_email($email)) {
            return new WP_Error('registration-error-invalid-email', __('Please provide a valid email address.', 'woocommerce'));
        }


        $validation_error = new WP_Error();

        $validation_error = apply_filters('digits_validate_email', $validation_error, $email);

        if ($validation_error->get_error_code()) {
            return $validation_error;
        }


        if (empty($username)) {

            $useMobAsUname = get_option('dig_mobilein_uname', 0);


            $isMobUsed = 0;
            if (is_numeric($phone_number) && in_array($useMobAsUname, array(1, 4, 5, 6))) {


                $username = $phone_number;

                if ($useMobAsUname == 1 || $useMobAsUname == 4) {
                    $username = '';
                    if (!empty($countrycode)) {
                        $username = $countrycode;
                    }

                    $username = $username . $phone_number;

                    if ($useMobAsUname == 1) {
                        $username = str_replace("+", "", $username);
                    }
                } else if ($useMobAsUname == 5) {
                    $username = $phone_number;
                } else if ($useMobAsUname == 6) {
                    $username = '0' . $phone_number;
                }

                $isMobUsed = 1;
            } else if ($useMobAsUname == 0) {
                if (!empty($email)) {
                    $username = sanitize_user(current(explode('@', $email)), true);
                } else {
                    $username = sanitize_user(sanitize_text_field($_POST['billing_first_name']), true);
                }
                $isMobUsed = 2;
            } else {
                $username = apply_filters('digits_username', '');
                $isMobUsed = 2;
            }

            if (!isValidEmail($email) && !is_numeric($phone_number)) {
                return new WP_Error('error', __('Invalid Mobile Number or email', 'digits'));
            }
            if (empty($username)) {
                $username = str_replace("+", "", $countrycode) . $phone_number;
                $isMobUsed = 1;
            }


            $append = 1;
            $o_username = $username;

            if (username_exists($username)) {


                if (is_numeric($phone_number) && $isMobUsed == 2) {
                    $username = $phone_number;
                    $isMobUsed = 1;
                } else {
                    $username = sanitize_user(current(explode('@', $email)), true);
                    $isMobUsed = 2;
                }
            }

            if ($isMobUsed == 2 && username_exists($username) && $usernameaccep < 2) {
                $tname = $username;
                $check = username_exists($tname);

                if (!empty($check)) {
                    $suffix = 2;
                    while (!empty($check)) {
                        $alt_ulogin = $tname . $suffix;
                        $check = username_exists($alt_ulogin);
                        $suffix++;
                    }
                    $username = $alt_ulogin;
                } else {
                    $username = $tname;
                }
            }

        }

        if (username_exists($username)) {
            return new WP_Error('error', __("Username is already in use!", "digits"), "error");
        }


        // Handle password creation.
        if (empty($password)) {
            $password = wp_generate_password();

            $passaccep = get_option("digpassaccep", 1);
            if ($passaccep == 0) {
                $password_generated = false;
            } else {
                $password_generated = true;
            }
        } else {
            $password_generated = false;
        }


        // Use WP_Error to handle registration errors.
        $errors = new WP_Error();

        $defaultuserrole = get_option('defaultuserrole');


        if (!empty($otp) || !empty($code) || dig_get_checkout_otp_verification() == 0) {


            $phone_verified = false;

            if (dig_get_checkout_otp_verification() == 0) {
                $phone_verified = false;
                $mob = $countrycode . $phone_number;
            } else {
                if (verifyOTP($countrycode, $phone_number, $otp, true)) {
                    $phone_verified = true;
                    $mob = $countrycode . $phone_number;
                } else {
                    $mob = null;
                }
            }


            if (!empty($mob)) {
                $username = sanitize_user($username, true);
                $customer_id = wp_create_user($username, $password, $email);

                update_user_meta($customer_id, 'digits_phone', $mob);
                update_user_meta($customer_id, 'digt_countrycode', $countrycode);
                update_user_meta($customer_id, 'digits_phone_no', $phone_number);

                $cd = array('ID' => $customer_id, 'role' => $defaultuserrole);
                wp_update_user($cd);

                if(!$phone_verified) {
                    update_user_meta($customer_id, 'digits_phone_verification_skipped', 'pending');
                }

            } else {
                return new WP_Error(__("Mobile number verification failed!", "digits"), "error");
            }
        } else {
            $new_customer_data = apply_filters('woocommerce_new_customer_data',
                array_merge(
                    $args,
                    array(
                        'user_login' => $username,
                        'user_pass' => $password,
                        'user_email' => $email,
                        'role' => $defaultuserrole,
                    )
                ));
            $customer_id = wp_insert_user($new_customer_data);
        }


        $new_customer_data = array(
            'ID' => $customer_id,
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        );


        if (is_wp_error($customer_id)) {

            return new WP_Error('registration-error', '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce'));
        }


        $new_customer_data = apply_filters('woocommerce_new_customer_data', $new_customer_data);

        update_digp_reg_fields($reg_custom_fields, $customer_id);


        wp_update_user($new_customer_data);


        do_action('woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated);


        return $customer_id;
    }

}


function add_dig_cust_field_wc_check()
{
    if (!is_user_logged_in()) {
        echo '<div class="wc_check_dig_custfields">';
        show_digp_reg_fields(2);
        echo '</div>';
    }
}

add_action('woocommerce_after_checkout_registration_form', 'add_dig_cust_field_wc_check');

function dig_updateBillingPhone($phone, $customer_id)
{
    $phone = str_replace("+", "", $phone);
    update_user_meta($customer_id, 'billing_phone', $phone);

    $load_address = "billing";


    $customer = new WC_Customer($customer_id);
    if ($customer) {
        $key = "billing_phone";

        if (is_callable(array($customer, "set_$key"))) {
            $customer->{"set_$key"}(wc_clean($phone));

        } else {
            $customer->update_meta_data($key, wc_clean($phone));
        }
        if (WC()->customer && is_callable(array(WC()->customer, "set_$key"))) {

            WC()->customer->{"set_$key"}(wc_clean($phone));
        }

        $customer->update_meta_data('billing_phone', $phone);
        $customer->save();
    }

    /*do_action( 'woocommerce_after_save_address_validation', $customer_id, $load_address, $address );
    if ( 0 === wc_notice_count( 'error' ) ) {
        do_action( 'woocommerce_customer_save_address', $customer_id, $load_address );
    }*/

}

add_action('woocommerce_checkout_process', 'validate_digits_wc_billing');


function dig_get_checkout_otp_verification()
{
    if (current_user_can('view_register')) {
        return 0;
    } else {
        $skip = get_option('dig_reg_skip_otp_verification', '0');

        if ($skip == '1') {
            return 0;
        } else {
            return 1;
        }
    }
}


function validate_digits_wc_billing()
{


    if (dig_get_checkout_otp_verification() == 0 || is_user_logged_in() || 'yes' !== get_option('woocommerce_enable_signup_and_login_from_checkout') || empty($_POST['mobile/email'])) {
        return;
    }


    if (empty($_REQUEST['createaccount'])) {
        return;
    }

    $phone_number = sanitize_mobile_field_dig($_POST['mobile/email']);


    if (isset($_POST['digt_countrycode'])) {
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    } else {
        $countrycode = sanitize_text_field($_POST['billing_phone_digt_countrycode']);
    }
    $otp = sanitize_text_field($_POST['digit_ac_otp']);


    $code = sanitize_text_field($_POST['code']);
    $csrf = sanitize_text_field($_POST['csrf']);


    if (is_numeric($phone_number)) {


        if (!checkwhitelistcode($countrycode)) {
            wc_add_notice(__('Invalid country code!'), 'error');
        }
        if (!empty($phone_number) && is_numeric($phone_number) && !empty($code) && !empty($csrf)) {
            return;
        }
        if (getUserFromPhone($countrycode . $phone_number)) {
            wc_add_notice(__('Mobile Number already in use!', 'digits'), 'error');
        }

        if (empty($phone_number) && empty($countrycode)) {
            wc_add_notice(__('Please enter Mobile Number!', 'digits'), 'error');
        }
        if (is_numeric($phone_number) && empty($countrycode)) {
            wc_add_notice(__('Please enter country code!', 'digits'), 'error');
        }


    } else if (!isValidEmail($phone_number)) {
        wc_add_notice(__('Invalid  Email!', 'digits'), 'error');
    }

    if (email_exists($phone_number)) {
        wc_add_notice(__('Email already in use!', 'digits'), 'error');
    }

}


add_action('woocommerce_after_checkout_billing_form', 'digit_woocommerce_after_checkout_billing_form');

function digit_woocommerce_after_checkout_billing_form()
{
    $billing_phone_verification = get_option('digits_enable_billing_phone_verification', 0);
    $guest_checkout_verification = get_option('digits_enable_guest_checkout_verification', 0);

    $verify = 'none';

    if (dig_get_checkout_otp_verification() == 1 && !is_user_logged_in()) {
        if (WC()->checkout()->is_registration_enabled()) {
            $verify = 'check';
        }
        if (WC()->checkout()->is_registration_required()) {
            $verify = 'all';
            $billing_phone_verification = '0';
        }
    }

    $p_verify = 'none';
    if ($billing_phone_verification != '0') {
        $p_verify = $billing_phone_verification;
    }

    $g_verify = 'none';
    if (!is_user_logged_in()) {
        if (!WC()->checkout()->is_registration_required()) {
            if ($guest_checkout_verification != '0') {
                $g_verify = $guest_checkout_verification;
            }
        }
    } else {
        echo '<input type="hidden" id="digits_customer_checkout" value="1"/>';
    }
    ?>
    <input type="hidden" id="digits_vcustomer_phone" value="<?php echo esc_attr($verify); ?>"/>
    <input type="hidden" id="digits_vbill_phone" value="<?php echo esc_attr($p_verify); ?>"/>
    <input type="hidden" id="digits_guest_vbill_phone" value="<?php echo esc_attr($g_verify); ?>"/>
    <input type="hidden" name="isPassEnab" id="dig_wc_check_page">
    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">
    <input type="hidden" name="code" id="dig_wc_bill_code">
    <input type="hidden" name="csrf" id="dig_wc_bill_csrf">

    <?php

}


add_action('personal_options_update', 'dig_update_user_profile');
add_action('edit_user_profile_update', 'dig_extra_profile_fields');
function dig_update_user_profile($user_id)
{

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }


    update_dig_profile_fields($user_id);


    $p = sanitize_mobile_field_dig($_POST['dig_user_mobile']);

    if ((empty($p) && empty($_POST['email']))
        ||
        (empty($_POST['email']) && !isValidEmail($_POST['email']))
    ) {
        return;
    }


    if (empty($p)) {
        delete_user_meta($user_id, 'digt_countrycode');
        delete_user_meta($user_id, 'digits_phone_no');
        delete_user_meta($user_id, 'digits_phone');

        return;
    }


    $phone_verified = false;
    if (!current_user_can('edit_user') && !current_user_can('administrator')) {

        $otp = sanitize_text_field($_POST['profile_update_otp']);
        $nounce = $_POST['dig_nounce'];

        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $p = sanitize_text_field($_POST['mobile/email']);
        if (verifyOTP($countrycode, $p, $otp, true)) {
            $phone = $p;
            $phone_verified = true;
        }
    } else {
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $phone = sanitize_mobile_field_dig($_POST['dig_user_mobile']);
    }


    if (is_numeric($phone) && is_numeric($countrycode)) {
        if (getUserFromPhone($countrycode . $phone)) {

        } else if ($phone != null) {

            if (empty($countrycode) ||
                !is_numeric($countrycode) ||
                strpos($countrycode, '+') !== 0) {
                return false;
            }

            update_user_meta($user_id, 'digt_countrycode', $countrycode);
            update_user_meta($user_id, 'digits_phone_no', $phone);
            update_user_meta($user_id, 'digits_phone', $countrycode . $phone);


            if(!$phone_verified) {
                update_user_meta($user_id, 'digits_phone_verification_skipped', 'pending');
            }

            if (get_option('dig_mob_ver_chk_fields', 1) == 0) {
                dig_updateBillingPhone($countrycode . $phone, $user_id);

            }
        }
    }


}

add_action('user_register', 'dig_wp_update_user');
function dig_wp_update_user($user_id)
{

    if (!current_user_can('edit_user') && !current_user_can('administrator')) {
        return false;
    }


    if (!isset($_POST['digt_countrycode']) || !isset($_POST['dig_user_mobile'])) {
        return;
    }

    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    $phone = sanitize_text_field($_POST['dig_user_mobile']);


    if (empty($phone) && empty($_POST['email']) && !isValidEmail($_POST['email'])) {
        return;
    }

    if (empty($phone)) {
        delete_user_meta($user_id, 'digt_countrycode');
        delete_user_meta($user_id, 'digits_phone_no');
        delete_user_meta($user_id, 'digits_phone');

        return;
    }
    if (getUserFromPhone($countrycode . $phone)) {
        return;
    }
    if (!is_numeric($countrycode) || !is_numeric($phone)) {
        return;
    }
    update_user_meta($user_id, 'digt_countrycode', $countrycode);
    update_user_meta($user_id, 'digits_phone_no', $phone);
    update_user_meta($user_id, 'digits_phone', $countrycode . $phone);

    update_user_meta($user_id, 'digits_phone_verification_skipped', 'pending');

    if (dig_get_checkout_otp_verification() == 0) {
        dig_updateBillingPhone($countrycode . $phone, $user_id);
    }
}


function dig_extra_profile_fields($user_id)
{

    if (!current_user_can('edit_user') && !current_user_can('administrator')) {
        return false;
    }


    update_dig_profile_fields($user_id);

    if (!isset($_POST['digt_countrycode']) || !isset($_POST['dig_user_mobile'])) {
        return;
    }

    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    $phone = sanitize_text_field($_POST['dig_user_mobile']);

    if (empty($phone)) {
        delete_user_meta($user_id, 'digt_countrycode');
        delete_user_meta($user_id, 'digits_phone_no');
        delete_user_meta($user_id, 'digits_phone');

        return;
    }
    if (getUserFromPhone($countrycode . $phone)) {
        return;
    }
    if (!is_numeric($countrycode) || !is_numeric($phone)) {
        return;
    }

    update_user_meta($user_id, 'digits_phone_verification_skipped', 'pending');

    update_user_meta($user_id, 'digt_countrycode', $countrycode);
    update_user_meta($user_id, 'digits_phone_no', $phone);
    update_user_meta($user_id, 'digits_phone', $countrycode . $phone);


    if (dig_get_checkout_otp_verification() == 0) {
        dig_updateBillingPhone($countrycode . $phone, $user_id);
    }
}

function update_dig_profile_fields($user_id)
{

    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);

    $errors = new WP_Error();
    $errors = validate_digp_reg_fields($reg_custom_fields, $errors, false);

    if ($errors->get_error_code()) {
        return;
    }
    update_digp_reg_fields($reg_custom_fields, $user_id);


    if (current_user_can('edit_user') && isset($_POST['digits_undefined_fields'])) {

        foreach ($_POST['digits_undefined_fields'] as $field) {
            if (isset($_POST[$field])) continue;
            $field = sanitize_text_field($field);
            $is_array = isset($_POST['digits_field_' . $field . '_array']) ? true : false;

            $field_value = sanitize_text_field($_POST['digits_field_' . $field]);
            $field_value = $is_array ? explode(',', $field_value) : $field_value;
            update_user_meta($user_id, $field, $field_value);

        }
    }
}

/*
 *
 * 1-> WP/BB
 * 2-> WC
 */

function addNewUserNameInLogin($type, $class = '')
{
    if (digits_make_third_party_secure()) {

        return;
    }

    ?>

    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">
    <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide <?php echo $class; ?>"
       id="dig_wc_log_otp_container" otp="1" style="display: none;">
        <label for="dig_wc_log_otp"><?php _e("OTP", "digits"); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="dig_wc_log_otp" id="dig_wc_log_otp"/>
    </p>


    <input type="hidden" name="username" id="loginuname" value=""/>
    <?php
}

function wc_addNewUserNameInLogin()
{
    addNewUserNameInLogin(2);
}

add_action('woocommerce_login_form_start', 'wc_addNewUserNameInLogin');


function digits_render_tp_secure_form($form_name)
{

    $dig_login_details = digit_get_login_fields();
    $captcha = $dig_login_details['dig_login_captcha'];

    $redirect_to = digits_get_redirect_uri($_REQUEST);

    if ($captcha > 0) {
        ?>
        <div class="digits_captcha_row">
            <?php
            $captcha_class = '';
            if ($form_name == 'wp') {
                $captcha_class = 'input';
            }
            dig_show_login_captcha(2, null, 0, $captcha_class, false, $captcha);
            ?>
        </div>
        <?php
    }
    ?>
    <div class="digits-form_footer"></div>
    <?php
    $t = get_option("digits_loginred");
    if (!empty($t)) {
        $redirect_to = -1;
    }
    digits_login_hidden_fields($redirect_to, false);
    ?>
    <input type="hidden" name="action_type" value="" autocomplete="off"/>
    <input type="hidden" name="digits_phone" value="">
    <input type="hidden" name="digits_email" value="">
    <input type="hidden" id="digits_secure_inp" name="digits_secured" value="1">
    <?php
}

/**
 * Modify the string on the login page to prompt for username or email address
 */

function wooc_extra_login()
{
    if (digits_make_third_party_secure()) {
        ?>
        <button onclick="return false"
                class="woocommerce-Button button digits_secure_login-tp digits-form_submit-btn"
        ><?php _e('Continue', 'digits'); ?></button>
        <input type="hidden" class="digits_container_id" name="digits_container_id" value="customer_login">
        <?php
        return;
    }


    $dig_login_details = digit_get_login_fields();
    $passaccep = $dig_login_details['dig_login_password'];
    $otpaccep = $dig_login_details['dig_login_otp'];
    ?>


    <input type="hidden" id="wc_login_cd" val="1">

    <p class="form-row form-row-wide">
    <input type="hidden" id="wc_code_dig" val="<?php if (isset($_POST['digt_countrycode']))
        echo esc_attr(sanitize_text_field($_POST['digt_countrycode'])); ?>">
    <?php
    if ($otpaccep == 1 || $passaccep == 1) {
        ?>
        <div class="loginViaContainer">
            <?php

            if ($passaccep == 1 && $otpaccep == 1) {
                ?>
                <span class="digor"><?php _e("OR", "digits"); ?><br/><br/></span>
                <?php
            } else if ($passaccep == 0) {
                echo '<input type="hidden" value="1" id="wc_dig_reg_form" />';
            }
            if ($otpaccep == 1) {
                ?>
                <button onclick="return false" class="woocommerce-Button button digits_login_via_otp dig_wc_mobileLogin"
                        name="loginviasms"><?php _e('Login With OTP', 'digits'); ?></button>
                <?php if (dig_isWhatsAppEnabled()) { ?>
                    <button onclick="return false"
                            class="woocommerce-Button button dig_wc_mobileLogin dig_wc_mobileWhatsApp"
                            name="loginviawhatsapp"><?php _e('Login With WhatsApp', 'digits'); ?></button>
                    <?php
                }
                ?>

                <?php
            }
            ?>
        </div>
        <?php

        echo "<div  class=\"dig_resendotp dig_wc_login_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
        </p>

        <?php
    }
}

add_action('woocommerce_login_form_end', 'wooc_extra_login');


function digits_wc_extra_captcha()
{
    if (digits_make_third_party_secure()) {
        digits_render_tp_secure_form('wc');
    } else {
        $dig_login_details = digit_get_login_fields();
        $captcha = $dig_login_details['dig_login_captcha'];
        if ($captcha == 1) {
            dig_show_login_captcha(2);
        }
    }
}

add_action('woocommerce_login_form', 'digits_wc_extra_captcha');


/**
 * Add new register fields for WooCommerce registration.
 */
function wooc_extra_register_fields_dig()
{

    $dig_reg_details = digit_get_reg_fields();

    $nameaccep = $dig_reg_details['dig_reg_name'];

    if ($nameaccep > 0) {
        ?>


        <p id="dig_cs_name"
           class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide dig-custom-field">
            <label for="reg_billing_first_name"><?php _e('First Name', 'digits'); ?>
                <?php if ($nameaccep == 2) {
                    echo '<span class="required">*</span>';
                } ?>
            </label>
            <input style="padding-left: 0.75em;" type="text" class="input-text"
                   name="tem_billing_first_name"
                   id="reg_billing_first_name"
                   value="<?php if (!empty($_POST['billing_first_name'])) {
                       esc_attr_e($_POST['billing_first_name']);
                   } ?>" <?php if ($nameaccep == 2) {
                echo 'required';
            } ?> autocomplete="name"/>
        </p>
        <?php
    }
    ?>


    <input type="hidden" id="digit_name" name="billing_first_name"/>
    <input type="hidden" id="digit_emailaddress" name="emailaddress"/>
    <input type="hidden" id="digit_mobile" name="mobile"/>


    <?php
}


function wooc_extra_register_fields_custom()
{
    show_digp_reg_fields(2);
}

add_action('woocommerce_register_form', 'wooc_extra_register_fields_dig');

add_action('woocommerce_register_form', 'wooc_add_extra_otp_reg_field', 1000, 1);

function wooc_add_extra_otp_reg_field()
{

    ?>

    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">
    <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide"
       id="reg_billing_otp_container" style="display: none;">
        <label for="reg_billing_otp"><?php _e("OTP", "digits"); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="reg_billing_otp" id="reg_billing_otp"/>
    </p>

    <?php


}

add_action('woocommerce_register_form', 'wooc_add_extra_reg_field');
function wooc_add_extra_reg_field()
{
    $dig_reg_details = digit_get_reg_fields();


    $emailaccep = $dig_reg_details['dig_reg_email'];
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    $reqoropt = __("*", 'digits');
    if ($emailaccep == 1) {
        $reqoropt = "(" . __("Optional", 'digits') . ")";
    }

    ?>

    <input type="hidden" name="code" class="register_code"/>
    <input type="hidden" name="csrf" class="register_csrf"/>
    <input type="hidden" name="dig_reg_mail" class="dig_reg_mail">

    <?php
    if ($emailaccep > 0 && $mobileaccp > 0) {


        $emailmob = __('Email/Mobile Number', 'digits');

        if ($emailaccep == 2 || $mobileaccp == 2) {
            $emailmob = __('Email', 'digits');

        }
        ?>
        <div id="dig_cs_email"
             class="dig_wc_mailsecond dig-custom-field" <?php if ($emailaccep > 1 || $mobileaccp > 1)
            echo 'style="display:block;"' ?>>
            <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
                <label for="secondmailormobile"><span
                            id="dig_secHolder"><?php echo $emailmob; ?></span><span> <?php echo $reqoropt; ?></span></label>
                <input class="woocommerce-Input woocommerce-Input--text input-text secmailormob"
                       name="secondmailormobile" id="secondmailormobile"
                       type="text" <?php if ($emailaccep == 2)
                    echo "required" ?>>
            </p>
        </div>
        <?php
    }
    wooc_extra_register_fields_custom();
}


add_action("woocommerce_lostpassword_form", "digit_lostpass");
function digit_lostpass()
{
    ?>

    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">
    <p class="woocommerce-form-row form-row" id="digit_forgot_otp_container" style="display: none;">
        <label for="digit_forgot_otp"><?php _e("OTP", "digits"); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="dig_otp" id="digit_forgot_otp" autocomplete="one-time-code"/>
        <?php
        echo "<div  class=\"dig_resendotp dig_wc_forgot_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
    </p>

    <?php

}


add_action('woocommerce_before_checkout_registration_form', 'digits_checkout_create_account_text');
function digits_checkout_create_account_text($checkout)
{
    if ($checkout->is_registration_required()) : ?>

        <p class="form-row form-row-wide create-account">
        <h6 class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <?php esc_html_e('Create an account', 'woocommerce'); ?>
        </h6>
        </p>

    <?php endif;
}

/*}
add_action('woocommerce_register_form_end','addNewSubmitButton');
*/

add_action('woocommerce_edit_account_form_start', 'wc_edit_act');
function wc_edit_act()
{
    $user = wp_get_current_user();
    ?>
    <input type="hidden" name="code" id="dig_wc_prof_code">
    <input type="hidden" name="csrf" id="dig_wc_prof_csrf">
    <input type="hidden" name="dig_old_phone" id="dig_wc_cur_phone"
           value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', $user->ID)); ?>"/>

    <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">

        <label for="account_email"><?php _e("Mobile Number", "digits"); ?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--email input-text dig_wc_nw_phone"
               name="account_email" id="username" mob="1"
               f-mob="1"
               countryCode="<?php echo esc_attr(get_the_author_meta('digt_countrycode', $user->ID)); ?>"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', $user->ID)); ?>">

    </p>

    <?php
}

add_action('woocommerce_edit_account_form', 'wc_edit_ac_end');

function wc_edit_ac_end()
{

    ?>
    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">
    <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide"
       id="digit_ac_otp_container"
       style="display: none;">
        <label for="digit_ac_otp"><?php _e("OTP", "digits"); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp"/>
    </p>
    <?php

}

add_action('woocommerce_edit_account_form_end', 'wc_edit_ac_end_add_resend');
function wc_edit_ac_end_add_resend()
{

    echo "<div  class=\"dig_resendotp dig_wc_acc_edit_resend\" style='text-align:center;' id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>";

}


add_action('woocommerce_register_form_end', 'add_dig_otp_wc');
function add_dig_otp_wc()
{

    echo '<input type="hidden" class="dig_wc_reg_form" value="1" />';
    $dig_reg_details = digit_get_reg_fields();
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    if ($mobileaccp == 0) {
        return;
    }

    if ($dig_reg_details['dig_reg_password'] == 1) {
        echo '<input class="woocommerce-Button button otp_reg_dig_wc" name="register" value="' . __('Register with OTP', 'digits') . '" type="submit" >';
    }

    ?>
    <?php if (dig_isWhatsAppEnabled()) {
    echo '<input class="woocommerce-Button button otp_reg_dig_wc otp_reg_dig_whatsapp" name="register" value="' . __('Register with WhatsApp', 'digits') . '" type="submit" >';
}


    echo "<div  class=\"dig_resendotp dig_wc_register_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>";


    echo '<input type="hidden" class="dig_wc_reg_form_end" value="1" />';
}


add_action('woocommerce_lostpassword_form', 'wc_dig_lost_pass');
function wc_dig_lost_pass()
{
    ?>

    <input type="hidden" name="code" id="digits_wc_code"/>
    <input type="hidden" name="csrf" id="digits_wc_csrf"/>
    <input type="hidden" name="dig_nounce" class="dig_nounce"
           value="<?php echo wp_create_nonce('dig_form') ?>">

    <div class="changePassword" style="display: none;">
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
            <label for="reg_password"><?php _e('New Password', 'digits'); ?> <span
                        class="required">*</span></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text" name="digits_password"
                   id="dig_wc_password" type="password">
        </p>
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
            <label for="reg_password"><?php _e('Confirm Password', 'digits'); ?> <span
                        class="required">*</span></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text" name="digits_cpassword"
                   id="dig_wc_cpassword" type="password">
        </p>
    </div>
    <?php
}

/**
 * Validate the extra register fields.
 *
 * @param WP_Error $validation_errors Errors.
 * @param string $username Current username.
 * @param string $email Current email.
 *
 * @return WP_Error
 */

function dig_wooc_validate_extra_register_fields($errors, $username, $email)
{
    if (isset($_POST['digits_phone']) && empty($_POST['digits_phone'])) {
        $errors->add('digits_phone_error', '<strong>' . __("Error", "digits") . '</strong>:' . __("Mobile Number is required!", 'digits'));
    } else {

    }

    return $errors;
}

add_filter('woocommerce_registration_errors', 'dig_wooc_validate_extra_register_fields', 10, 3);


function dig_woocommerce_lost_password_message($var)
{

    if (isset($_GET['reset-link-sent'])) {
        return $var;
    }

    return __('Lost your password? Please enter your mobile number to receive OTP or email address to get a link to create a new password.', 'digits');
}

add_action('woocommerce_lost_password_message', 'dig_woocommerce_lost_password_message');


function register_digits_exporter($exporters)
{
    $exporters['digits-customer-data'] = array(
        'exporter_friendly_name' => __('Digits'),
        'callback' => 'digits_personal_data_exporter',
    );

    return $exporters;
}

add_filter('wp_privacy_personal_data_exporters', 'register_digits_exporter', 10);


function digits_personal_data_exporter($email_address)
{
    $email_address = trim($email_address);
    $export_items = array();
    $export_data = array();

    $user = get_user_by("email", $email_address);

    if (!$user) {
        return array(
            'data' => array(),
            'done' => true,
        );
    }

    $user_id = $user->ID;
    $mob = get_the_author_meta('digits_phone', $user_id);

    if (!empty($mob)) {
        $export_data[] = array(
            'name' => __('Mobile Number', 'digits'),
            'value' => $mob
        );
    }

    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    foreach ($reg_custom_fields as $label => $values) {
        $label = cust_dig_filter_string($label);
        $e_value = get_user_meta($user_id, $label, true);
        if (!empty($e_value)) {
            if (is_array($e_value)) {
                $e_value = implode(", ", $e_value);
            }
            $export_data[] = array(
                'name' => $label,
                'value' => $e_value
            );
        }
    }
    if (!array_filter($export_data)) {
        return array(
            'data' => array(),
            'done' => true,
        );
    }


    $export_items[] = array(
        'group_id' => 'user',
        'group_label' => __('User'),
        'item_id' => "user-{$user->ID}",
        'data' => $export_data,
    );

    return array(
        'data' => $export_items,
        'done' => true,
    );

}


function register_digits_plugin_eraser($erasers)
{
    $erasers['digits-customer-data'] = array(
        'eraser_friendly_name' => __('Digits Data Eraser'),
        'callback' => 'digits_data_eraser',
    );

    return $erasers;
}

add_filter('wp_privacy_personal_data_erasers', 'register_digits_plugin_eraser', 10);

function digits_data_eraser($email_address)
{
    if (empty($email_address)) {
        return array(
            'items_removed' => false,
            'items_retained' => false,
            'messages' => array(),
            'done' => true,
        );
    }

    $email_address = trim($email_address);


    $user = get_user_by("email", $email_address);
    $user_id = $user->ID;


    $items_removed = 0;
    $items_retained = 0;
    $messages = array();

    $mob = get_the_author_meta('digits_phone', $user_id);

    if (!empty($mob)) {

        delete_user_meta($user_id, "digt_countrycode");
        delete_user_meta($user_id, "digits_phone_no");
        delete_user_meta($user_id, "digits_phone");
        $messages[] = __("Removed", "digits") . " user " . __("Mobile Number", "digits");
        $items_removed++;
    }


    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    foreach ($reg_custom_fields as $label => $values) {
        $label = cust_dig_filter_string($label);
        $e_value = get_user_meta($user_id, $label, true);
        if (!empty($e_value)) {
            delete_user_meta($user_id, $label);
            $items_removed++;
            $messages[] = __("Removed", "digits") . " user " . $label;
        }
    }


    $done = true;


    return array(
        'items_removed' => $items_removed,
        'items_retained' => $items_retained,
        'messages' => $messages,
        'done' => $done,
    );

}


function dig_sanitize_username($username, $raw_username, $strict)
{
    $username = preg_replace('/\s+/', '', $username);

    $username = wp_strip_all_tags($raw_username);

    $username = remove_accents($username);

    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);

    //Kill entities
    $username = preg_replace('/&.+?;/', '', $username);

    if ($strict) {
        //  $username = preg_replace('|[^a-z\p{Arabic}\p{Cyrillic}0-9 _.\-@]|iu', '', $username);
    }

    $username = trim($username);

    //Done
    return $username;
}

add_filter('sanitize_user', 'dig_sanitize_username', 10, 3);


function dig_sanitize_options($options)
{
    if (empty($options)) {
        return $options;
    }
    $new = array();
    foreach ($options as $v) {
        $new[] = dig_filter_string($v);
    }

    return $new;

}

/**
 * WooCommerce Customer Functions
 *
 * Functions for customers.
 *
 * @package WooCommerce/Functions
 */
function dig_wc_create_new_customer($email, $username = '', $password = '', $args = array())
{
    if (empty($email) || !is_email($email)) {
        return new WP_Error('registration-error-invalid-email', __('Please provide a valid email address.', 'woocommerce'));
    }

    if (email_exists($email)) {
        return new WP_Error('registration-error-email-exists', apply_filters('woocommerce_registration_error_email_exists', __('An account is already registered with your email address. Please log in.', 'woocommerce'), $email));
    }

    if ('yes' === get_option('woocommerce_registration_generate_username', 'yes') && empty($username)) {
        $username = wc_create_new_customer_username($email, $args);
    }

    $username = sanitize_user($username);

    if (empty($username) || !validate_username($username)) {
        return new WP_Error('registration-error-invalid-username', __('Please enter a valid account username.', 'woocommerce'));
    }

    if (username_exists($username)) {
        return new WP_Error('registration-error-username-exists', __('An account is already registered with that username. Please choose another.', 'woocommerce'));
    }

    // Handle password creation.
    $password_generated = false;
    if ('yes' === get_option('woocommerce_registration_generate_password') && empty($password)) {
        $password = wp_generate_password();
        $password_generated = true;
    }

    if (empty($password)) {
        return new WP_Error('registration-error-missing-password', __('Please enter an account password.', 'woocommerce'));
    }

    // Use WP_Error to handle registration errors.
    $errors = new WP_Error();

    do_action('woocommerce_register_post', $username, $email, $errors);

    $errors = apply_filters('woocommerce_registration_errors', $errors, $username, $email);

    if ($errors->get_error_code()) {
        return $errors;
    }

    $new_customer_data = apply_filters(
        'woocommerce_new_customer_data',
        array_merge(
            $args,
            array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'role' => 'customer',
            )
        )
    );

    $customer_id = wp_insert_user($new_customer_data);

    if (is_wp_error($customer_id)) {
        return $customer_id;
    }

    do_action('woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated);

    return $customer_id;
}


add_action('digits_user_created', 'digits_wc_update_new_details', 10);
function digits_wc_update_new_details($user_id)
{
    $user = get_user_by('ID', $user_id);

    if (!class_exists('WooCommerce')) {
        return false;
    }

    if (!$user) {
        return false;
    }
    $enable_wc_autofill = get_option('dig_autofill_wc_billing', 1);
    if (!$enable_wc_autofill || $enable_wc_autofill != 1) {
        return;
    }

    $billing_email = get_user_meta($user->ID, 'billing_email', true);
    if (!empty($billing_email)) {
        return false;
    }
    if (!empty($user->first_name)) {
        update_user_meta($user_id, 'billing_first_name', $user->first_name);
    }
    if (!empty($user->last_name)) {
        update_user_meta($user_id, 'billing_last_name', $user->last_name);
    }

    if (!empty($user->user_email)) {
        update_user_meta($user_id, 'billing_email', $user->user_email);
    }


    $phone = digits_get_mobile($user->ID);
    if (!empty($phone)) {

        $country_list = dig_country_list();
        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($phone);
            $region = $phoneUtil->getRegionCodeForCountryCode($numberProto->getCountryCode());
            $geocoder = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();
            $country = $geocoder->getDescriptionForNumber($numberProto, 'en_us');
            $country_iso_code = array_search($country, $country_list);

            update_user_meta($user_id, 'billing_phone', $phone);

            update_user_meta($user_id, 'billing_country', $country_iso_code);
        } catch (Exception $e) {

        }
    }
}