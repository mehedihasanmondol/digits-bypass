<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/PublicKeyCredentialSourceRepository.php';
require_once dirname(__FILE__) . '/validate.php';

use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;


DigitsDeviceAuth::instance();

class DigitsDeviceAuth
{
    const REGISTER_DEVICE_SESSION_KEY = 'register_auth_device';
    const AUTHENTICATE_DEVICE_SESSION_KEY = 'authenticate_device';
    protected static $_instance = null;
    /**
     * @var PublicKeyCredentialRpEntity|null
     */
    public $rpEntity = null;
    /**
     * @var PublicKeyCredentialSourceRepository|null
     */
    public $publicKeyCredentialSourceRepository = null;
    /**
     * @var Server|null
     */
    public $server = null;
    /**
     * @var PublicKeyCredentialUserEntity|null
     */
    public $userEntity = null;
    /**
     * @var AuthenticatorSelectionCriteria|null
     */
    public $authenticatorSelectionCriteria = null;
    public $device_name;
    public $device_type;

    public function __construct()
    {
    }

    public static function create_new_device_public_key($user_id, $device_name, $device_type, $request_identifier = false)
    {
        $user = get_user_by('ID', $user_id);
        $instance = self::instance();
        $instance->setDeviceType($device_type);
        $publicKeyCreationOptions = $instance->generatePublicKeyCredentialCreationOptions($user);
        $details = array('publicKeyCreationOptions' => $publicKeyCreationOptions->jsonSerialize());
        $details['device_name'] = $device_name;
        $details['device_type'] = $device_type;

        if ($device_type == 'platform') {
            $details['device_info'] = digits_get_os();
        } else {
            $details['device_info'] = 'key';
        }

        $details['is_mobile'] = wp_is_mobile() ? 1 : 0;
        $details['user_agent'] = $_SERVER['HTTP_USER_AGENT'];


        DigitsSessions::delete_user_key($user_id, self::REGISTER_DEVICE_SESSION_KEY);
        DigitsSessions::set(self::REGISTER_DEVICE_SESSION_KEY, json_encode($details), 3600, $request_identifier);

        return $publicKeyCreationOptions;
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

    /**
     * @param mixed $device_type
     */
    public function setDeviceType($device_type): void
    {

        if ($device_type == 'cross-platform') {
            $this->device_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
        } else if ($device_type == 'platform') {
            $this->device_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
        } else {
            $this->device_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;
        }
    }

    /**
     * @return PublicKeyCredentialCreationOptions
     * @throws Exception
     */
    public function generatePublicKeyCredentialCreationOptions($user)
    {
        return $this->getServer()->generatePublicKeyCredentialCreationOptions(
            $this->getUserEntity($user),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->getUserSecurityDevices($user->ID),
            $this->getAuthenticatorSelectionCriteria()
        );
    }

    /**
     * @return Server|null
     */
    public function getServer()
    {
        if ($this->server == null) {
            $this->server = new Server(
                $this->getRpEntity(),
                $this->getPublicKeyCredentialSourceRepository()
            );
        }
        return $this->server;
    }

    /**
     * @return PublicKeyCredentialRpEntity|null
     */
    public function getRpEntity()
    {
        if ($this->rpEntity == null) {
            $site_name = get_bloginfo('name');

            $this->rpEntity = new PublicKeyCredentialRpEntity(
                $site_name,
                $this->get_url()
            );
        }
        return $this->rpEntity;
    }

    private function get_url()
    {
        $url = home_url();
        $url = str_replace("http://", "", $url);
        $url = str_replace("https://", "", $url);
        return rtrim($url, "/");
    }

    /**
     * @return PublicKeyCredentialSourceRepository|null
     */
    public function getPublicKeyCredentialSourceRepository()
    {
        if ($this->publicKeyCredentialSourceRepository == null) {
            $this->publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository();
        }
        return $this->publicKeyCredentialSourceRepository;
    }

    /**
     * @return PublicKeyCredentialUserEntity|null
     * @throws Exception
     */
    public function getUserEntity($user = false)
    {
        if ($this->userEntity == null) {
            if (!$user) {
                $user = wp_get_current_user();
            }
            if (empty($user)) {
                throw new Exception(__('Please login to continue', 'digits'));
            }
            $this->userEntity = new PublicKeyCredentialUserEntity(
                $user->user_login,
                sha1($user->ID),
                $user->user_email
            );
        }
        return $this->userEntity;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getUserSecurityDevices($user_id = 0, $device_type = '')
    {
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }

        $credentialSources = $this->getPublicKeyCredentialSourceRepository()->findAllForUserID($user_id, $device_type);
        $devices = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);
        return $devices;
    }

    /**
     * @return AuthenticatorSelectionCriteria|null
     */
    public function getAuthenticatorSelectionCriteria(): ?AuthenticatorSelectionCriteria
    {
        if ($this->authenticatorSelectionCriteria == null) {
            $this->authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
                $this->getAuthenticatorDeviceType(),
                false,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED
            );
        }
        return $this->authenticatorSelectionCriteria;
    }

    public function getAuthenticatorDeviceType()
    {
        if ($this->device_type != null) {
            return $this->device_type;
        }
        return AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function listUserSecurityDevices($user_id)
    {
        $devices = $this->getPublicKeyCredentialSourceRepository()->listAllDevicesForUserID($user_id, 'all', false);
        return $devices;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUserSecurityDevicesType($user_id, $device_type)
    {
        $devices = $this->getPublicKeyCredentialSourceRepository()->listAllDevicesForUserID($user_id, $device_type, false);
        return $devices;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUserSecurityDevicesCategoryWise($user_id, $device_type)
    {
        $devices = $this->getPublicKeyCredentialSourceRepository()->listAllDevicesForUserID($user_id, $device_type, true);
        return $devices;
    }

    public function deleteUserSecurityDevice($user_id, $uniqid)
    {
        $this->getPublicKeyCredentialSourceRepository()->delete_user_key($user_id, $uniqid);
    }

    /**
     * @param mixed $device_name
     */
    public function setDeviceName($device_name): void
    {
        $this->device_name = $device_name;
    }

    public function process_register_new_device($auth_data, $add_to_user_device)
    {

        $data_str = DigitsSessions::get(self::REGISTER_DEVICE_SESSION_KEY);

        if (empty($data_str)) {
            return new WP_Error("invalid_data", __("Please try again!", 'digits'));
        }
        $validator = new DigitsDeviceAuthValidate();
        $data = json_decode($data_str, true);
        $publicKeyCredentialCreationArray = $data['publicKeyCreationOptions'];
        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($publicKeyCredentialCreationArray);
        $validator->setPublicKeyCredentialCreationOptions($publicKeyCredentialCreationOptions);
        $validate = $validator->validate($auth_data);

        if (empty($validate)) {
            return new WP_Error("error", __("Please try again!", 'digits'));
        }


        $data['key_id'] = base64_encode($validate->getPublicKeyCredentialId());
        $data['cred_source'] = json_encode($validate->jsonSerialize());

        if (!$add_to_user_device) {
            DigitsSessions::delete(self::REGISTER_DEVICE_SESSION_KEY);
            return base64_encode(serialize($data));
        }

        if (!is_user_logged_in()) {
            return new WP_Error("error", __("Please try again!", 'digits'));
        }


        $user_id = get_current_user_id();

        return $this->add_user_new_device($data, $user_id);
    }

    public function add_user_new_device($data, $user_id)
    {
        $this->getPublicKeyCredentialSourceRepository()->addUserDevice($data, $user_id);
        DigitsSessions::delete_user_key($user_id, self::REGISTER_DEVICE_SESSION_KEY);
        return $data['device_name'];
    }


    public static function generate_auth_public_key($user_id, $device_type, $step_no)
    {
        $step_name = self::AUTHENTICATE_DEVICE_SESSION_KEY . '_' . $step_no . '_' . $user_id;

        $instance = self::instance();
        $instance->setDeviceType($device_type);
        $publicKeyRequestOptions = $instance->generatePublicKeyCredentialRequestOptions($user_id, $device_type);
        $details = array('publicKeyRequestOptions' => $publicKeyRequestOptions->jsonSerialize());

        DigitsSessions::delete($step_name);
        DigitsSessions::set($step_name, json_encode($details), 3600);

        return $details['publicKeyRequestOptions'];
    }

    public static function authenticate_user_device($user, $step_no, $auth_data)
    {
        $user_id = $user->ID;

        $step_name = self::AUTHENTICATE_DEVICE_SESSION_KEY . '_' . $step_no . '_' . $user_id;

        $validator = new DigitsDeviceAuthValidate();

        $data = DigitsSessions::get($step_name);

        if (empty($data)) {
            return new WP_Error("invalid_data", __("Please try again!", 'digits'));
        }


        $user_entity = self::instance()->getUserEntity($user);

        $data = json_decode($data, true);
        $publicKeyCredentialRequestOptions = $data['publicKeyRequestOptions'];
        $publicKeyCredentialRequestArray = PublicKeyCredentialRequestOptions::createFromArray($publicKeyCredentialRequestOptions);
        $validator->setPublicKeyCredentialRequestOptions($publicKeyCredentialRequestArray);
        $validate = $validator->authenticate_user($user_entity, $auth_data);
        if (!empty($validate)) {
            return true;
        }

        return new WP_Error("error", __("Key verification failed, if issue persists please try reloading!", 'digits'));
    }

    /**
     * @return PublicKeyCredentialRequestOptions
     * @throws Exception
     */
    public function generatePublicKeyCredentialRequestOptions($user_id, $device_type)
    {
        return $this->getServer()->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $this->getUserSecurityDevices($user_id, $device_type)
        );
    }

}

