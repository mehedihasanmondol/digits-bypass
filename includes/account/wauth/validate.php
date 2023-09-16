<?php

if (!defined('ABSPATH')) {
    exit;
}

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Webauthn\Server;

class DigitsDeviceAuthValidate
{
    private $publicKeyCredentialCreationOptions;
    private $publicKeyCredentialRequestOptions;

    /**
     * @param mixed $publicKeyCredentialCreationOptions
     */
    public function setPublicKeyCredentialCreationOptions($publicKeyCredentialCreationOptions): void
    {
        $this->publicKeyCredentialCreationOptions = $publicKeyCredentialCreationOptions;
    }

    /**
     * @param mixed $publicKeyCredentialRequestOptions
     */
    public function setPublicKeyCredentialRequestOptions($publicKeyCredentialRequestOptions): void
    {
        $this->publicKeyCredentialRequestOptions = $publicKeyCredentialRequestOptions;
    }


    public function authenticate_user($user_entity, $auth_data)
    {


        try {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            $serverRequest = $creator->fromGlobals();
            $auth_data = rawurldecode($auth_data);

            $publicKeyCredentialSource = $this->get_server()->loadAndCheckAssertionResponse(
                $auth_data,
                $this->publicKeyCredentialRequestOptions,
                $user_entity,
                $serverRequest
            );

            return true;

        } catch (\Throwable $exception) {
            return false;
        }

        return false;
    }

    public function validate($auth_data)
    {
        try {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            $serverRequest = $creator->fromGlobals();

            $publicKeyCredentialSource = $this->get_server()->loadAndCheckAttestationResponse(
                json_encode($auth_data),
                $this->publicKeyCredentialCreationOptions,
                $serverRequest
            );

            return $publicKeyCredentialSource;

        } catch (\Throwable $exception) {
            return false;
        }

        return false;
    }

    /**
     * @return Server|null
     */
    private function get_server()
    {
        return DigitsDeviceAuth::instance()->getServer();
    }
}

