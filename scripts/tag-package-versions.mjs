import { parseApprovedPackageManifest } from './package-release-manifest.mjs';

const owner = 'ControleOnline';
const apiBase = 'https://api.github.com';
const token = process.env.PACKAGE_TAG_TOKEN;
const dryRun = process.argv.includes('--dry-run');
const approvalPath = new URL('../package-release-approvals.json', import.meta.url);
const packageRepositoryNames = new Set([
  'api-platform-accounting',
  'api-platform-common',
  'api-platform-contract',
  'api-platform-ead',
  'api-platform-financial',
  'api-platform-integration',
  'api-platform-logistic',
  'api-platform-multi-tenancy',
  'api-platform-orders',
  'api-platform-people',
  'api-platform-products',
  'api-platform-queue',
  'api-platform-report',
  'api-platform-tasks',
  'api-platform-users',
  'api-platform-websocket-server',
  'messages-sdk-php',
  'smoke-tests-playground',
  'spc-sdk-php',
  'whatsapp-sdk-php',
]);

async function request(path, options = {}) {
  const response = await fetch(`${apiBase}${path}`, {
    ...options,
    headers: {
      Accept: 'application/vnd.github+json',
      Authorization: `Bearer ${token}`,
      'X-GitHub-Api-Version': '2022-11-28',
      ...(options.headers || {}),
    },
  });

  if (response.status === 404) return null;
  if (!response.ok) {
    throw new Error(`GitHub API ${options.method || 'GET'} ${path} failed (${response.status}).`);
  }
  return response.status === 204 ? {} : response.json();
}

async function manifestAt(repository, sha, approvedVersion) {
  for (const file of ['package.json', 'composer.json']) {
    const content = await request(`/repos/${owner}/${repository}/contents/${file}?ref=${encodeURIComponent(sha)}`);
    if (!content) continue;
    const manifest = JSON.parse(Buffer.from(content.content, 'base64').toString('utf8'));
    const pkg = parseApprovedPackageManifest(file, manifest, approvedVersion);
    if (pkg) return pkg;
  }
  return null;
}

async function resolveCommitSha(repository, reference) {
  let object = reference?.object;
  for (let depth = 0; depth < 10; depth += 1) {
    if (object?.type === 'commit') return object.sha;
    if (object?.type !== 'tag') return null;
    const annotatedTag = await request(`/repos/${owner}/${repository}/git/tags/${object.sha}`);
    if (!annotatedTag) return null;
    object = annotatedTag.object;
  }
  throw new Error(`Tag reference for ${repository} is nested too deeply.`);
}

async function main() {
  const approvals = JSON.parse(await (await import('node:fs/promises')).readFile(approvalPath, 'utf8'));
  if (!Array.isArray(approvals.releases)) throw new Error('package-release-approvals.json must contain a releases array.');
  if (approvals.releases.length === 0) {
    console.log('No approved package releases to tag.');
    return;
  }
  if (!token) {
    throw new Error('PACKAGE_TAG_TOKEN is required to create tags in the approved package repositories.');
  }

  const availablePackageRepositories = new Map();
  for (const name of packageRepositoryNames) {
    const repository = await request(`/repos/${owner}/${name}`);
    if (repository && !repository.archived && !repository.disabled && repository.default_branch) {
      availablePackageRepositories.set(name, repository);
    }
  }

  let created = 0;
  for (const release of approvals.releases) {
    const repository = availablePackageRepositories.get(release.repository);
    if (!repository) {
      console.log(`repository-unavailable ${release.repository}`);
      continue;
    }
    if (!/^\d+\.\d+\.\d+$/.test(release.version || '') || !/^[0-9a-f]{40}$/i.test(release.sha || '')) {
      throw new Error(`Invalid approved release entry for ${release.repository}.`);
    }
    const pkg = await manifestAt(repository.name, release.sha, release.version);
    if (!pkg || pkg.version !== release.version) {
      throw new Error(`Approved SHA for ${repository.name} does not contain stable package version ${release.version}.`);
    }

    const tag = `v${release.version}`;
    const existing = await request(`/repos/${owner}/${repository.name}/git/ref/tags/${tag}`);
    if (existing) {
      const taggedCommit = await resolveCommitSha(repository.name, existing);
      if (taggedCommit !== release.sha) {
        throw new Error(`Existing ${repository.name} ${tag} points to ${taggedCommit || 'an unknown object'}, not approved SHA ${release.sha}.`);
      }
      console.log(`present ${repository.name} ${tag}`);
      continue;
    }

    const comparison = await request(`/repos/${owner}/${repository.name}/compare/${encodeURIComponent(repository.default_branch)}...${release.sha}`);
    if (!comparison || !['behind', 'identical'].includes(comparison.status)) {
      console.log(`not-merged ${repository.name} ${tag}`);
      continue;
    }
    if (dryRun) {
      console.log(`would-create ${repository.name} ${tag} at approved SHA ${release.sha}`);
      continue;
    }

    const tagObject = await request(`/repos/${owner}/${repository.name}/git/tags`, {
      method: 'POST',
      body: JSON.stringify({
        tag,
        message: `Release ${pkg.name} ${tag}`,
        object: release.sha,
        type: 'commit',
        tagger: { name: 'ControleOnline Package Version Worker', email: 'packages@controleonline.com', date: new Date().toISOString() },
      }),
      headers: { 'Content-Type': 'application/json' },
    });
    await request(`/repos/${owner}/${repository.name}/git/refs`, {
      method: 'POST',
      body: JSON.stringify({ ref: `refs/tags/${tag}`, sha: tagObject.sha }),
      headers: { 'Content-Type': 'application/json' },
    });
    created += 1;
    console.log(`created ${repository.name} ${tag} at approved SHA ${release.sha}`);
  }

  console.log(`Package tag scan complete; ${created} tag(s) created${dryRun ? ' in dry-run mode' : ''}.`);
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
