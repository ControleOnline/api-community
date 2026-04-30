const {execFileSync} = require('child_process');
const path = require('path');

const root = path.resolve(__dirname, '..');
const expectedCommit = '1ecd6876e1591597f4eeddc96a7e93ce19232549';
const submodulePath = 'modules/controleonline/products';

const output = execFileSync(
  'git',
  ['ls-files', '--stage', submodulePath],
  {cwd: root, encoding: 'utf8'},
).trim();

if (!output) {
  throw new Error(`Gitlink ausente para ${submodulePath}.`);
}

const parts = output.split(/\s+/);
const actualCommit = parts[1];

if (actualCommit !== expectedCommit) {
  throw new Error(
    `Gitlink divergente para ${submodulePath}. esperado=${expectedCommit} atual=${actualCommit}`,
  );
}

console.log(
  `Composicao confirmada: ${submodulePath} aponta para ${actualCommit}.`,
);
