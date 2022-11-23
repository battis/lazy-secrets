# Lazy Secrets

A (thin) wrapper for google/cloud-secret-manager to reduce boilerplate

## Install

```bash
composer require battis/lazy-secrets
```

## Use

```php
use Battis\LazySecrets\Secrets;

$data = Secrets::get('MY_APP_SECRET');
```

## Background

While the [Google Cloud Secret Manager](https://cloud.google.com/secret-manager/docs) is a fine way to store (and access) app secrets, it also entails a bunch of boilerplate code that I don't want to fat finger. So, instead of writing...

```php
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;

$client = new SecretManagerServiceClient();
$project = $_ENV['GOOGLE_CLOUD_PROJECT'];
$key = 'MY_APP_SECRET';
$version = 'latest';
$secret = $client->accessSecretVersion("projects/$project/secrets/$key/versions/$version");
$data = $secret->getPayload()->getData();

// and even (if you're packing a lot into one secret)
$obj = json_decode($data);

// ...and then using the $data or $obj
```

...I'd rather just write:

```php
use Battis\LazySecrets\Secrets;

$data = Secrets::get('MY_APP_SECRET');

// or
$obj = Secrets::get('MY_APP_SECRET, null, true);
```
