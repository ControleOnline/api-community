"""Run with python3 -B -m unittest discover -s tests/Metadata -p 'test_*.py'."""
import json
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
BUNDLE = ROOT / 'docs/patches/api268-marketplace-read'
SCRIPT = ROOT / 'scripts/apply-api268-marketplace-read.py'


class MarketplaceOverlayTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.manifest = json.loads((BUNDLE / 'manifest.json').read_text())
        composer = self.root / 'vendor/composer'
        composer.mkdir(parents=True)
        self.identity = {'name': self.manifest['package'], 'version': self.manifest['version'],
                         'source': {'reference': self.manifest['reference']}}
        (composer / 'installed.json').write_text(json.dumps({'packages': [self.identity]}))
        for entry in self.manifest['files']:
            if entry['before']:
                target = self.root / entry['path']
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes((BUNDLE / 'before' / entry['path']).read_bytes())

    def run_overlay(self, *args):
        return subprocess.run(['python3', str(SCRIPT), '--root', str(self.root), *args],
                              capture_output=True, text=True)

    def test_round_trip_and_idempotent_reapplication(self):
        for args in [('--apply',), ('--apply',), ('--revert',), ('--revert',)]:
            self.assertEqual(0, self.run_overlay(*args).returncode)
        for entry in self.manifest['files']:
            target = self.root / entry['path']
            if entry['before']:
                self.assertEqual((BUNDLE / 'before' / entry['path']).read_bytes(), target.read_bytes())
            else:
                self.assertFalse(target.exists())

    def test_changed_local_file_rejects_entire_overlay(self):
        target = self.root / self.manifest['files'][0]['path']
        target.write_text('preserve local work')
        self.assertNotEqual(0, self.run_overlay('--apply').returncode)
        self.assertEqual('preserve local work', target.read_text())
        self.assertFalse((self.root / self.manifest['files'][1]['path']).exists())

    def test_package_identity_mismatch_rejects_overlay(self):
        self.identity['version'] = 'unrecognized'
        (self.root / 'vendor/composer/installed.json').write_text(json.dumps({'packages': [self.identity]}))
        self.assertNotEqual(0, self.run_overlay('--apply').returncode)
        self.assertFalse((self.root / self.manifest['files'][1]['path']).exists())
