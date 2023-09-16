<?php


if (!defined('ABSPATH')) {
    exit;
}

use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepositoryInterface
{
    const TABLE = 'digits_auth_devices';

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $data = $this->get_by_key_id($publicKeyCredentialId);
        if (!empty($data)) {
            return PublicKeyCredentialSource::createFromArray(json_decode($data->cred_source, true));
        }
        return null;
    }

    private function get_by_key_id($key_id)
    {
        $key_id = base64_encode($key_id);

        global $wpdb;

        $tablename = $this->get_table_name();
        $query = $wpdb->prepare("SELECT * FROM $tablename WHERE key_id = %s LIMIT 1", $key_id);
        return $wpdb->get_row($query);
    }

    public function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // TODO: Implement findAllForUserEntity() method.
        return [];
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserID($user_id, $device_type): array
    {
        $sources = [];
        foreach ($this->get_user_keys($user_id, $device_type) as $data) {
            $sources[] = PublicKeyCredentialSource::createFromArray(json_decode($data->cred_source, true));
        }
        return $sources;
    }

    private function get_user_keys($user_id, $device_type)
    {
        $all = array('platform-all', 'all');

        global $wpdb;

        $tablename = $this->get_table_name();

        if (empty($device_type) || in_array($device_type, $all)) {
            $query = $wpdb->prepare("SELECT * FROM $tablename WHERE user_id = %d", $user_id);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $tablename WHERE user_id = %d AND device_type = %s", $user_id, $device_type);
        }

        return $wpdb->get_results($query);
    }

    /**
     * @return array
     */
    public function listAllDevicesForUserID($user_id, $device_type = 'all', $get_counts = false): array
    {
        if ($get_counts) {
            $devices = ['platform' => 0, 'cross-platform' => 0, 'mobile_devices' => 0];
        } else {
            $devices = [];
        }

        foreach ($this->get_user_keys($user_id, $device_type) as $data) {
            if ($get_counts) {
                $devices[$data->device_type] += 1;
                if ($data->is_mobile == 1) {
                    $devices['mobile_devices'] += 1;
                }
            } else {
                $devices[] = array('device_name' => $data->device_name,
                    'device_info' => $data->device_info,
                    'uniqid' => $data->uniqid,
                    'is_mobile' => $data->is_mobile,
                    'device_type' => $data->device_type);
            }
        }
        return $devices;
    }

    public function addUserDevice($details, $user_id)
    {
        global $wpdb;

        $tablename = $this->get_table_name();
        $data = array();
        $data['key_id'] = $details['key_id'];
        $data['cred_source'] = $details['cred_source'];
        $data['user_id'] = $user_id;
        $data['device_name'] = $details['device_name'];
        $data['device_info'] = $details['device_info'];
        $data['device_type'] = $details['device_type'];
        $data['is_mobile'] = $details['is_mobile'];
        $data['user_agent'] = $details['user_agent'];
        $data['uniqid'] = sha1(uniqid());
        $data['ip'] = digits_get_ip();
        return $wpdb->insert($tablename, $data);
    }

    public function delete_user_key($user_id, $uniqid)
    {
        if (empty($user_id) || empty($uniqid)) {
            return false;
        }

        global $wpdb;

        $tablename = $this->get_table_name();
        $where = array(
            'user_id' => $user_id,
            'uniqid' => $uniqid
        );
        return $wpdb->delete($tablename, $where);
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
    }
}