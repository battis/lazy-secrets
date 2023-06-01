<?php

namespace Battis\LazySecrets;

use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use Google\Cloud\SecretManager\V1\SecretVersion;
use JsonSerializable;

class Secrets {

    private static ?SecretManagerServiceClient $client = null;

    private static ?string $project = null;

    private static bool $json = false;

    private function __construct()
    {}

    /**
     * Initialize the Secret Manager Service Client (optionally
     * specifying the project ID)
     *
     * If no project ID is specified, the argument passed to the method
     * will be assigned the value of the environment variable
     ( `GOOGLE_CLOUD_PROJECT`. For example"
     * ```php
     * Secrets::init($x);
     * echo $x; // the project ID of the active instance
     * ```
     * @param string $project project ID (optional, defaults to the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @param bool $json Whether or not to decode the secret data as JSON
     */
    private static function init(string &$project = null, bool $json = false) {
        $project = $project ?? $_ENV['GOOGLE_CLOUD_PROJECT'];
        self::$project = $project;
            self::$client = new SecretManagerServiceClient();
        self::$json = $json;
    }

    /**
     * Get the singleton Secret Manager Client (initializing it, if
     * necessary)
     * @param string $project project ID (optional, defaults to the
     *                        previously `init()` value or the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @return SecretManagerServiceClient
     */
    private static function getClient(string &$project = null): SecretManagerServiceClient
    {
        if (self::$client === null || self::$project === null || $project !== null) {
            self::init($project);
        }
        return self::$client;
    }

    /**
     * Get a secret value from the Secret Manager (optionally JSON-decoding
     * it)
     * @param string $key
     * @param bool $json whether or not to JSON-decode the value (Optional,
     *                   defaults to `false`)
     * @param string $project project ID (optional, defaults to the
     *                        previously `init()` value or the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @param string|int $version (Optional, defaults to `'latest'`)
     * @return mixed secret data
     */
    public static function get(string $key, bool $json = null, string $project = null, $version = 'latest')
    {
        $client = self::getClient($project);
        $data = $client->accessSecretVersion("projects/$project/secrets/$key/versions/$version")->getPayload()->getData();
        if ($json ?? self::$json) {
            $decoded = @json_decode($data);
            if ($decoded !== null || $data === 'null') {
                return $decoded;
            }
        }
        return $data;
    }

    /**
     * Set a secret value in the Secret Manager)
     * @param string $key
     * @param JsonSerializable|string $data
     * @param string $project project ID (optional, defaults to the
      *                        previously `init()` value or the
      *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @return SecretVersion
     */
    public static function set(string $key, $data, string $project = null): SecretVersion {
        $client = self::getClient($project);
        $parent = $client->secretName($project, $key);
        return $client->addSecretVersion($parent, new SecretPayload(is_string($data) ? $data : json_encode($data)));
    }
}
