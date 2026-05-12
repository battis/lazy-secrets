# @battis/lazy-secrets

[![npm version](https://badge.fury.io/js/@battis%2Flazy-secrets.svg)](https://badge.fury.io/js/@battis%2Fclazy-secrets)

A (thin) wrapper for @google-cloud/cloud-secret-manager to reduce boilerplate

## Install

```bash
npm install @battis/lazy-secrets
```

## Usage

```ts
import { LazySecrets } from '@battis/lazy-secrets';

// get a secret value (automatically parsed from JSON, or returned as the string value if it cannot be decoded)
const secret = await LazySecrets.get('SECRET_NAME');

// set a secret value (automatically encoded as JSON on write)
await LazySecrets.set('SECRET_NAME', { key: 'value' });

// optionally pass expected type
type SecretType = { key: string };
const typedSecret = await LazySecrets.get<SecretType>('SECRET_NAME');
```

This assumes the presence of the environment variable `GOOGLE_CLOUD_PROJECT` which is set automatically by Google App Engine, but needs to be set manually with Google Cloud Run:

```
gcloud run deploy service-name --set-env-vars "GOOGLE_CLOUD_PROJECT=my-project-name"
```
