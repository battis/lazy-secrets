<?php

namespace Battis\LazySecrets;

use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    public const GCP_ENV_KEY = "GOOGLE_CLOUD_PROJECT";

    private string $projectId;
    private string $projectName;
    private SecretManagerServiceClient $client;

    /**
     * Construct a new instance for a specific project
     *
     * @param string $projectId Optional. If no project ID is provided, the
     *     environment will be checked for the standard `GOOGLE_CLOUD_PROJECT`
     *     variable to be used.
     */
    public function __construct(string $projectId = null)
    {
        $this->client = new SecretManagerServiceClient();
        $this->projectId = $projectId ?? $_ENV[self::GCP_ENV_KEY];
        assert(
            !empty($this->projectId),
            new InvalidArgumentException(
                "missing project ID as argument or environment variable"
            )
        );
        $this->projectName = $this->client::projectName($this->projectId);
    }

    /**
     * Fetches a value from the project's secrets.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        try {
            /** @var mixed $data */
            $data = $default;
            $payload = $this->client
              ->accessSecretVersion(
                  $this->client::secretVersionName($this->projectId, $key, "latest")
              )
              ->getPayload();
            if ($payload) {
                $data = $payload->getData();
                /** @var mixed $value */
                $value = @json_decode($data);
                if ($value !== null || json_last_error() === JSON_ERROR_NONE) {
                    /** @var mixed $data */
                    $data = $value;
                }
            }
            return $data;
        } catch (ApiException $e) {
            return $default;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function create(string $key, $value = null): bool
    {
        try {
            $this->client->createSecret(
                $this->projectName,
                $key,
                new Secret([
                "replication" => new Replication(["automatic" => new Automatic()]),
        ])
            );
            if ($value !== null) {
                $this->set($key, $value);
            }
            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key   The key of the item to store.
     * @param mixed $value The value of the item to store. Must be serializable.
     * @param null|int|\DateInterval $ttl Included for PSR-16 compatibility. Non-null arguments will destroy the prior version of the secret.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $prevVersion = null;
        try {
            if ($ttl != null) {
                $prevVersion = $this->client->accessSecretVersion(
                    $this->client::secretVersionName($this->projectId, $key, "latest")
                );
            }
        } catch (ApiException $e) {
            // ignore
        }
        try {
            $this->client->addSecretVersion(
                $this->client::secretName($this->projectId, $key),
                new SecretPayload(["data" => $this->serialize($value)])
            );
            if ($ttl != null && $prevVersion) {
                $this->client->destroySecretVersion($prevVersion->getName());
            }
            return true;
        } catch (ApiException $e) {
            return $this->create($key, $value);
        }
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {
        try {
            $this->client->deleteSecret(
                $this->client::secretName($this->projectId, $key)
            );
            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        try {
            /** @var Secret $secret */
            foreach (
                $this->client->listSecrets($this->projectName)->iterateAllElements()
                as $secret
            ) {
                $this->client->deleteSecret($secret->getName());
            }
            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return array<string, mixed> A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $results = [];
        foreach ($keys as $secretId) {
            assert(
                is_string($secretId),
                new InvalidArgumentException("invalid secret ID type")
            );
            /** @var mixed */
            $results[$secretId] = $this->get($secretId, $default);
        }
        return $results;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Included for PSR-16 compatibility. Non-null arguments will destroy the prior version of the secret.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $success = true;
        /** @var mixed $data */
        foreach ($values as $secretId => $data) {
            assert(
                is_string($secretId),
                new InvalidArgumentException("invalid secret ID type")
            );
            $success = $success && $this->set($secretId, $data, $ttl);
        }
        return $success;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys): bool
    {
        $success = true;
        foreach ($keys as $secretId) {
            assert(
                is_string($secretId),
                new InvalidArgumentException("invalid secret ID type")
            );
            $success = $success && $this->delete($secretId);
        }
        return $success;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it, making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        try {
            return !!$this->client->getSecret(
                $this->client::secretName($this->projectName, $key)
            );
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException if the $value neither a string nor JSON serializable
     */
    private function serialize($value): string
    {
        if (is_string($value)) {
            return $value;
        } else {
            $encoded = @json_encode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $encoded;
            }
        }
        throw new InvalidArgumentException("unserializable value");
    }
}
