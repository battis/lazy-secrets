<?php

namespace Battis\LazySecrets;

class Secrets
{
    /** @var array<string, Cache> $cache */
    private static $cache = [];

    /**
     * @api singleton
     */
    private function __construct() {}

    private static function getCache(string $projectId = null): Cache
    {
        $key = json_encode($projectId);
        if (!array_key_exists($key, Secrets::$cache)) {
            Secrets::$cache[$key] = new Cache($projectId);
        }
        return Secrets::$cache[$key];
    }

    /**
     * @param string $secretId
     * @param string|\JsonSerializable $data
     * @param string $projectId
     * @return bool
     * @api
     */
    public static function create(
        string $secretId,
        $data = null,
        string $projectId = null
    ): bool {
        return Secrets::getCache($projectId)->create($secretId, $data);
    }

    /**
     * @param string $secretId
     * @param string|\JsonSerializable $data
     * @param string $projectId
     * @return bool
     * @api
     */
    public static function set(
        string $secretId,
        $data,
        string $projectId = null
    ): bool {
        return Secrets::getCache($projectId)->set($secretId, $data);
    }

    /**
     * @param string $secretId
     * @param string $projectId
     * @return mixed
     * @api
     */
    public static function get(
        string $secretId,
        string $projectId = null
    ) {
        return Secrets::getCache($projectId)->get($secretId, null);
    }
}
