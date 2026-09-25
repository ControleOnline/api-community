const STABLE_VERSION = /^\d+\.\d+\.\d+$/;
const NPM_NAME = /^@controleonline\/[a-z0-9][a-z0-9._-]*$/i;
const COMPOSER_NAME = /^controleonline\/[a-z0-9][a-z0-9._-]*$/i;

export function parseApprovedPackageManifest(file, manifest, approvedVersion) {
  if (!manifest || typeof manifest !== 'object' || manifest.private === true) return null;
  if (!STABLE_VERSION.test(approvedVersion || '')) return null;

  if (file === 'package.json') {
    if (!NPM_NAME.test(manifest.name || '') || !STABLE_VERSION.test(manifest.version || '')) return null;
    if (manifest.version !== approvedVersion) return null;
    return { name: manifest.name, version: manifest.version };
  }

  if (file === 'composer.json') {
    if (!COMPOSER_NAME.test(manifest.name || '')) return null;
    // Composer derives package versions from VCS tags; a version field is optional.
    if (manifest.version && manifest.version !== approvedVersion) return null;
    return { name: manifest.name, version: approvedVersion };
  }

  return null;
}
