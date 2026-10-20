#!/usr/bin/env python3
"""Apply/revert the exact local Composer overlay without network or database access."""
import argparse
import hashlib
import json
import os
from pathlib import Path


def digest(data):
    return hashlib.sha256(data).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true')
    parser.add_argument('--revert', action='store_true')
    parser.add_argument('--root', type=Path, default=Path(__file__).resolve().parents[1])
    args = parser.parse_args()
    if args.apply and args.revert:
        parser.error('Choose --apply or --revert.')
    root = args.root.resolve()
    bundle = Path(__file__).resolve().parents[1] / 'docs/patches/manager-read-batch'
    manifest = json.loads((bundle / 'manifest.json').read_text())
    installed = json.loads((root / 'vendor/composer/installed.json').read_text())
    packages = installed.get('packages', []) if isinstance(installed, dict) else installed
    for identity in [manifest, *manifest.get('additionalPackages', [])]:
        package = next((p for p in packages if p['name'] == identity['package']), None)
        if not package or package['version'] != identity['version'] or package.get('source', {}).get('reference') != identity['reference']:
            raise SystemExit('Package identity differs. No files were changed.')
    actions = []
    for item in manifest['files']:
        relative = Path(item['path'])
        destination = root / relative
        if relative.is_absolute() or '..' in relative.parts or destination.is_symlink():
            raise SystemExit('Unsafe destination. No files were changed.')
        current = digest(destination.read_bytes()) if destination.exists() else None
        if current not in (item['before'], item['after']):
            raise SystemExit(f'Unexpected local content: {relative}. No files were changed.')
        side = 'before' if args.revert else 'after'
        target_hash = item[side]
        payload = bundle / side / relative
        data = payload.read_bytes() if target_hash else None
        if data is not None and digest(data) != target_hash:
            raise SystemExit('Overlay payload checksum differs. No files were changed.')
        if current != target_hash:
            actions.append((destination, data))
    if not args.apply and not args.revert:
        print(f'Checked {len(manifest["files"])} files; {len(actions)} would change. No files changed.')
        return
    for destination, data in actions:
        if data is None:
            destination.unlink()
        else:
            destination.parent.mkdir(parents=True, exist_ok=True)
            temporary = destination.with_name(destination.name + '.manager-batch.tmp')
            temporary.write_bytes(data)
            if destination.exists():
                temporary.chmod(destination.stat().st_mode)
            os.replace(temporary, destination)
    print(f'{"Reverted" if args.revert else "Applied"}: {len(actions)} files changed. No network or DB access.')


if __name__ == '__main__':
    main()
