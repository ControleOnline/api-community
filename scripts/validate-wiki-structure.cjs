const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const wikiPointer = fs.readFileSync(path.join(root, 'docs/wiki.md'), 'utf8');
const gitmodules = fs.readFileSync(path.join(root, '.gitmodules'), 'utf8');

if (wikiPointer !== 'https://github.com/ControleOnline/api-community/wiki\n') {
  throw new Error('docs/wiki.md must contain only the full api-community wiki URL');
}

if (/path\s*=\s*docs\/wiki/.test(gitmodules)) {
  throw new Error('docs/wiki must not be configured as a submodule');
}

if (/api-community\.wiki\.git/.test(gitmodules)) {
  throw new Error('api-community wiki must be referenced by docs/wiki.md, not cloned as docs/wiki');
}

console.log('Validated api-community wiki pointer.');
