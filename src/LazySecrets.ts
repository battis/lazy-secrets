import { JSONValue } from '@battis/typescript-tricks';
import { SecretManagerServiceClient } from '@google-cloud/secret-manager';

let _client!: SecretManagerServiceClient;
function client() {
  if (!_client) {
    _client = new SecretManagerServiceClient({
      projectId: process.env.GOOGLE_CLOUD_PROJECT
    });
  }
  return _client;
}

export async function get<T extends JSONValue = JSONValue>(
  name: string,
  version = 'latest'
) {
  let value: T | string | undefined = undefined;
  const [secret] = await client().accessSecretVersion({
    name: `projects/${process.env.GOOGLE_CLOUD_PROJECT}/secrets/${name}/versions/${version}`
  });
  if (
    secret.payload?.data &&
    secret.payload !== null &&
    secret.payload.data !== null
  ) {
    try {
      value = JSON.parse(secret.payload.data.toString('utf-8')) as T;
    } catch (_) {
      value = secret.payload.data.toString();
    }
  }
  return value;
}

export async function set<T extends JSONValue = JSONValue>(
  name: string,
  value: T
) {
  const parent = `projects/${process.env.GOOGLE_CLOUD_PROJECT}/secrets/${name}`;
  const [latest] = await client().addSecretVersion({
    parent,
    payload: {
      data: Buffer.from(JSON.stringify(value), 'utf-8')
    }
  });
  const [versions] = await client().listSecretVersions({ parent });
  for (const version of versions) {
    if (version.name !== latest.name && version.state !== 'DESTROYED') {
      await client().destroySecretVersion(version);
    }
  }
}
