<?php

namespace Battis\LazySecrets;

use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use JsonSerializable;

class Secrets
{
    private static ?SecretManagerServiceClient $client = null;

    private function __construct()
    {
    }

    /**
     * Get the singleton Secret Manager Client (initializing it, if
     * necessary)
     * @param string $project project ID (optional, defaults to the
     *                        previously `init()` value or the
     *                        `GOOGLE_CLOUD_PROJECT` environment variable)
     * @return SecretManagerServiceClient
     */
    public static function getClient(): SecretManagerServiceClient
    {
        if (self::$client === null) {
            self::$client = new SecretManagerServiceClient();
        }
        return self::$client;
    }

    public static function getProjectId(string $projectId = null): string
    {
        return $projectId ?? $_ENV['GOOGLE_CLOUD_PROJECT'];
    }

    /**
     * @param string $secretId
     * @param string|JsonSerializable $data
     * @param string $projectId
     * @return string
     */
    public static function create(
        string $secretId,
        $data = null,
        string $projectId = null
    ): string {
        $secret = self::getClient()->createSecret(
            self::getClient()->projectName(self::getProjectId($projectId)),
            $secretId,
            new Secret(new Replication(new Automatic()))
        );
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
    public static function set(
        string $secretId,
        $data,
        string $projectId = null
    ): string {
        return self::getClient()
            ->addSecretVersion(
                self::getClient()->secretName(
                    self::getProjectId($projectId),
                    $secretId
                ),
                new SecretPayload([
                    'data' => is_string($data) ? $data : json_encode($data),
                ])
            )
            ->getName();
    }

    /**
     * @param string $secretId
     * @param string $versionId (Optional, default `'latest'`)
     * @param string $projectId
     * @return mixed
     */
    public static function get(
        string $secretId,
        $associativeArray = false,
        string $versionId = 'latest',
        string $projectId = null
    ) {
        $data = self::getClient()
            ->accessSecretVersion(
                self::getClient()->secretVersionName(
                    self::getProjectId($projectId),
                    $secretId,
                    $versionId
                )
            )
            ->getPayload()
            ->getData();
        $json = @json_decode($data, $associativeArray);
        if ($json !== null || json_last_error() === JSON_ERROR_NONE) {
            $data = $json;
        }
        return $data;
    }
}
