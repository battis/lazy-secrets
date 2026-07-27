import { JSONValue } from '@battis/typescript-tricks';
import { SecretManagerServiceClient } from '@google-cloud/secret-manager';

let _projectId = process.env.GOOGLE_CLOUD_PROJECT;
let _client!: SecretManagerServiceClient;

export function init(
  opts?: ConstructorParameters<typeof SecretManagerServiceClient>[0],
  force = false
) {
  // Skip Google Cloud Secret Manager initialization during build
  if (process.env.NEXT_PHASE === 'phase-production-build') {
    return {} as SecretManagerServiceClient;
  }
  if (opts?.projectId) {
    _projectId = opts.projectId;
  }
  if (!_client || force) {
    opts = {
      projectId: opts?.projectId || process.env.GOOGLE_CLOUD_PROJECT,
      ...opts
    };
    _client = new SecretManagerServiceClient(opts);
  }
}

function client() {
  if (!_client) {
    init();
  }
  return _client;
}

export async function get<T extends JSONValue = JSONValue>(
  name: string,
  version = 'latest'
) {
  let value: T | string | undefined;
  const [secret] = await client().accessSecretVersion({
    name: `projects/${_projectId}/secrets/${name}/versions/${version}`
  });
  value = secret.payload?.data?.toString('utf-8');
  if (value) {
    try {
      value = JSON.parse(value) as T;
    } catch (_) {
      // guess it wasn't JSON?
    }
  }
  return value;
}

export async function set<T extends JSONValue = JSONValue>(
  name: string,
  value: T,
  retain = 1
) {
  const parent = `projects/${_projectId}/secrets/${name}`;
  const [latest] = await client().addSecretVersion({
    parent,
    payload: {
      data: Buffer.from(JSON.stringify(value), 'utf-8')
    }
  });
  let counter = 0;
  for await (const version of client().listSecretVersionsAsync({
    parent,
    filter: 'state=ENABLED'
  })) {
    counter++;
    if (version.name !== latest.name && counter > retain) {
      await client().destroySecretVersion(version);
    }
  }
}
