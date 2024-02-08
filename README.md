# Lazy Secrets

[![Latest Version](https://img.shields.io/packagist/v/battis/lazy-secrets.svg)](https://packagist.org/packages/battis/lazy-secrets)

A (thin) wrapper for google/cloud-secret-manager to reduce boilerplate

## Install

```bash
composer require battis/lazy-secrets
```

## Use

```php
use Battis\LazySecrets\Secrets;

$data = Secrets::get("MY_APP_SECRET");
```

## Background

While the [Google Cloud Secret Manager](https://cloud.google.com/secret-manager/docs)
is a fine way to store (and access) app secrets, it also entails a bunch of
boilerplate code that I don't want to fat finger. So, instead of writing...

```php
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;

$client = new SecretManagerServiceClient();
$project = $_ENV["GOOGLE_CLOUD_PROJECT"];
$key = "MY_APP_SECRET";
$version = "latest";
$secret = $client->accessSecretVersion(
  "projects/$project/secrets/$key/versions/$version"
);
$data = $secret->getPayload()->getData();

// and even (if you're packing a lot into one secret)
$obj = json_decode($data);

// ...and then using the $data or $obj
```

...I'd rather just write:

```php
use Battis\LazySecrets\Secrets;

$data = Secrets::get("MY_APP_SECRET");

// or
Secrets::init($project, true);
$obj = Secrets::get("MY_APP_SECRET");
```

Alternatively, a PSR-16 Simple Cache implementation is also available (for
easy use with dependency injection):

```php
use Battis\LazySecrets\Cache;

// assume that the `GOOGLE_CLOUD_PROJECT` environment variable is set
$secrets = new Cache();

$obj = $secrets->get("MY_APP_SECRET");
```

or

```php
/** src/Example/DependencyConsumer */

namespace Example;

use Psr\SimpleCache\CacheInterface;

class DependencyConsumer
{
  public function __constructor(CacheInterface $cache)
  {
    // ...
  }
}
```

```php
/** src/app.php */

$container = new DI\Container([
  Psr\SimpleCache\CacheInterface::class => DI\create(
    \Battis\LazySecrets\Cache::class
  ),
]);
$consumer = $container->get(DependencyConsumer::class);
```
