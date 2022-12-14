<?php

namespace Battis\LazySecrets;

use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;

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
     * Get a secret value from the Secret Manager (optionally JSON-decoding
     * it)
     * @param string $key
     * @param string|int $version (Optional, defaults to `'latest'`)
     * @param bool $json whether or not to JSON-decode the value (Optional,
     *                   defaults to `false`)
     * @param string $project project ID (optional, defaults to the
     *                        previously `init()` value or the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @return mixed secret data
     */
    public static function get(string $key, $version = null, bool $json = null, string $project = null)
    {
        $project = $project ?? self::$project;
        $version = $version ?? 'latest';
        $data = self::getClient($project)->accessSecretVersion("projects/$project/secrets/$key/versions/$version")->getPayload()->getData();
        if ($json ?? self::$json) {
            $decoded = @json_decode($data);
            if ($decoded !== null || $data === 'null') {
                return $decoded;
            }
        }
        return $data;
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
}
