const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const gitmodules = fs.readFileSync(path.join(root, '.gitmodules'), 'utf8');

if (!/path\s*=\s*docs\/wiki/.test(gitmodules)) {
  throw new Error('docs/wiki must be configured as the api-community wiki submodule');
}

if (!/url\s*=\s*https:\/\/github\.com\/ControleOnline\/api-community\.wiki\.git/.test(gitmodules)) {
  throw new Error('docs/wiki must point to https://github.com/ControleOnline/api-community.wiki.git');
}

console.log('Validated api-community wiki submodule.');
