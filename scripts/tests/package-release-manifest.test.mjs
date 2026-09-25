import assert from 'node:assert/strict';
import test from 'node:test';
import { parseApprovedPackageManifest } from '../package-release-manifest.mjs';

test('accepts a Composer package with its version approved by the release manifest', () => {
  assert.deepEqual(parseApprovedPackageManifest('composer.json', {
    name: 'controleonline/common',
  }, '1.0.1'), {
    name: 'controleonline/common',
    version: '1.0.1',
  });
});

test('rejects a Composer version that conflicts with the approved release', () => {
  assert.equal(parseApprovedPackageManifest('composer.json', {
    name: 'controleonline/common',
    version: '1.0.0',
  }, '1.0.1'), null);
});

test('requires npm package manifests to declare the exact approved version', () => {
  assert.deepEqual(parseApprovedPackageManifest('package.json', {
    name: '@controleonline/ui-common',
    version: '1.2.88',
  }, '1.2.88'), {
    name: '@controleonline/ui-common',
    version: '1.2.88',
  });
  assert.equal(parseApprovedPackageManifest('package.json', {
    name: '@controleonline/ui-common',
    version: '1.2.87',
  }, '1.2.88'), null);
});

test('rejects private packages and names outside the publication scopes', () => {
  assert.equal(parseApprovedPackageManifest('composer.json', {
    name: 'other/common',
  }, '1.0.1'), null);
  assert.equal(parseApprovedPackageManifest('package.json', {
    name: '@controleonline/ui-common',
    version: '1.2.88',
    private: true,
  }, '1.2.88'), null);
});
