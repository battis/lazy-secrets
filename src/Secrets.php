<?php

namespace Battis\LazySecrets;

use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretVersion;
use JsonSerializable;

class Secrets {

    private static ?SecretManagerServiceClient $client = null;

    private static ?string $projectId = null;

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
    private static function init(string &$projectId = null) {
        $project = $project ?? $_ENV['GOOGLE_CLOUD_PROJECT']; // set by Google App Engine
        self::$projectId = $projectId;
        self::$client = new SecretManagerServiceClient();
    }

    /**
     * Get the singleton Secret Manager Client (initializing it, if
     * necessary)
     * @param string $project project ID (optional, defaults to the
     *                        previously `init()` value or the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @return SecretManagerServiceClient
     */
    public static function getClient(string &$projectId = null): SecretManagerServiceClient
    {
        if (self::$client === null || self::$projectId === null || $projectId !== null) {
            self::init($projectId);
        }
        return self::$client;
    }

    /**
     * @param string $secretId
     * @param string|JsonSerializable $data
     * @param string $projectId
     * @return string
     */
    public static function create(string $secretId, $data = null, string $projectId = null): string {
        $client = self::getClient($projectId);
        $secret = $client->createSecret($client->projectName($projectId), $secretId, new Secret(new Replication(new Automatic())));
        if ($data !== null) {
            self::set($secretId, $data);
        }
        return $secret->getName();
    }

    /**
     * @param string $secretId
     * @param string|JsonSerializable $data
     * @param string $projectId
     * @return string
     */
    public static function set(string $secretId, $data, string $projectId = null): string {
        $client = self::getClient($projectId);
        return $client->addSecretVersion($client->secretName($projectId, $secretId), [$data => (is_string($data) ? $data : json_encode($data))])->getName();
    }

    /**
     * @param string $secretId
     * @param string $versionId (Optional, default `'latest'`)
     * @param string $projectId
     * @return mixed
     */
    public static function get(string $secretId, string $versionId = 'latest', string $projectId) {
        $client = self::getClient($projectId);
        $data = $client->accessSecretVersion($client->secretVersionName($projectId, $secretId, $versionId))->getPayload()->getData();
        $json = @json_decode($data);
        if ($json !== null || json_last_error() === JSON_ERROR_NONE) {
            $data = $json;
        }
        return $data;
    }
}
