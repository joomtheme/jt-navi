#!/usr/bin/env python3
"""Build reproducible Joomla installation archives from the checked-in sources."""
from pathlib import Path
import hashlib
import zipfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / 'dist'
VERSION = '1.0.0'

def archive(directory, target, extras=None):
    entries = {p.relative_to(directory).as_posix(): p.read_bytes()
               for p in directory.rglob('*') if p.is_file()}
    entries.update(extras or {})
    with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as z:
        for name, data in sorted(entries.items()):
            info = zipfile.ZipInfo(name, (2026, 9, 11, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            z.writestr(info, data)
    with zipfile.ZipFile(target) as z:
        assert z.testzip() is None

def main():
    DIST.mkdir(exist_ok=True)
    for xml in ROOT.rglob('*.xml'):
        ET.parse(xml)
    children = {}
    for element in ['com_jtnavi', 'mod_jtnavi']:
        path = DIST / (element + '.zip')
        archive(ROOT / 'extensions' / element, path)
        children['packages/' + path.name] = path.read_bytes()
    children['README.md'] = (ROOT / 'README.md').read_bytes()
    children['TESTING.md'] = (ROOT / 'docs' / 'TESTING.md').read_bytes()
    children['LICENSE.txt'] = (ROOT / 'LICENSE.txt').read_bytes()
    target = DIST / f'pkg_jtnavi-{VERSION}.zip'
    archive(ROOT / 'package', target, children)
    print(target)
    print('SHA256', hashlib.sha256(target.read_bytes()).hexdigest())

if __name__ == '__main__':
    main()
