import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {test} from 'node:test';

const workflow = readFileSync(new URL('../../.github/workflows/deploy.yml', import.meta.url), 'utf8');
const secrets = {
  DEV_PASS: 'dev-password-fixture', STAGING_PASS: 'staging-password-fixture',
  CONTROLEONLINE: 'production-key-fixture', PORT: 2222,
};

// Evaluate the workflow's actual boolean selectors using non-secret fixtures.
function input(name, env, auth_mode) {
  const expression = workflow.match(new RegExp(`^          ${name}: \\$\\{\\{ (.+) \\}\\}$`, 'm'))?.[1];
  if (!expression) return '';
  return Function('needs', 'secrets', `return (${expression});`)(
    {configure: {outputs: {env, auth_mode}}}, secrets,
  );
}

for (const [env, auth, password, key, port] of [
  ['dev', 'password', secrets.DEV_PASS, '', 22],
  ['staging', 'password', secrets.STAGING_PASS, '', 22],
  ['master', 'key', '', secrets.CONTROLEONLINE, 2222],
]) {
  test(`${env} selects only its own SSH credentials`, () => {
    const stanza = workflow.match(new RegExp(`            ${env}\\)([\\s\\S]*?)              ;;`))?.[1];
    assert.ok(stanza, `missing ${env} configuration`);
    assert.match(stanza, new RegExp(`AUTH_MODE=${auth};`));
    assert.equal(input('password', env, auth), password);
    assert.equal(input('key', env, auth), key);
    assert.equal(input('passphrase', env, auth), '');
    assert.equal(input('port', env, auth), port);
  });
}

test('staging requires a login password, never a private key or passphrase', () => {
  assert.doesNotMatch(workflow, /STAGING_KEY/);
  assert.match(workflow, /Missing STAGING_PASS \(SSH login password\)/);
  assert.match(workflow, /run: node --test tests\/Workflow\/staging-ssh-auth\.test\.mjs/);
});
