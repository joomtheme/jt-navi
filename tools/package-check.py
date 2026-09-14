from pathlib import Path
from xml.etree import ElementTree as ET
import json,re
R=Path(__file__).resolve().parents[1]
version=ET.parse(R/'package/pkg_jtnavi.xml').getroot().findtext('version')
for p in R.rglob('*.xml'):ET.parse(p)
for base in (R/'extensions').iterdir():
 manifest=next(base.glob('*.xml')); tree=ET.parse(manifest).getroot()
 assert tree.findtext('version')==version,(manifest,'version mismatch')
 for section in ['files','administration/files','languages','administration/languages','media']:
  el=tree.find(section)
  if el is not None:
   folder=base/el.get('folder','')
   for child in el:
    assert (folder/child.text).exists(),(manifest,section,child.text)
 for sql in tree.findall('./install/sql/file')+tree.findall('./uninstall/sql/file'):
  assert (base/'admin'/sql.text).is_file()
 for schema in tree.findall('./update/schemas/schemapath'):
  assert (base/'admin'/schema.text).is_dir()
 php=list(base.rglob('*.php'))
 for p in php:
  assert "defined('_JEXEC') or die;" in p.read_text(),p
 langs={}
 for p in base.rglob('*.ini'):
  keys=[]
  for line in p.read_text().splitlines():
   if not line or line.startswith(';'):continue
   assert re.match(r'^[A-Z][A-Z0-9_]*=".*"$',line), (p,line)
   keys.append(line.split('=',1)[0])
  assert len(keys)==len(set(keys)),p
  langs[str(p.relative_to(base)).replace('tr-TR','en-GB')]=set(keys) if 'en-GB' in str(p) else langs.get(str(p.relative_to(base)).replace('tr-TR','en-GB'),set(keys))
 # Both languages have exactly the same keys for each matching file.
 for p in base.rglob('*.ini'):
  if 'tr-TR' not in str(p):continue
  q=Path(str(p).replace('tr-TR','en-GB'))
  parse=lambda path:set(re.findall(r'^([A-Z][A-Z0-9_]+)=',path.read_text(),re.M))
  assert parse(p)==parse(q),p
 # Every own extension language key in PHP/XML is defined somewhere in English.
 allkeys=set()
 for p in base.rglob('*.ini'):
  if 'en-GB' in str(p):allkeys.update(re.findall(r'^([A-Z][A-Z0-9_]+)=',p.read_text(),re.M))
 for p in list(base.rglob('*.php'))+list(base.rglob('*.xml')):
  used=set(re.findall(r'\b(?:COM|MOD)_JTNAVI_[A-Z0-9_]+\b',p.read_text()))
  used={key for key in used if not key.endswith('_')}
  assert not used-allkeys,(p,used-allkeys)
 if base.name.startswith('mod_'):
  data=json.loads((base/'media/joomla.asset.json').read_text())
  for asset in data['assets']:
   assert asset.get('version')==version,(base,asset['name'],'asset version mismatch')
   assert (base/'media'/('css' if asset['type']=='style' else 'js')/Path(asset['uri']).name).is_file()
print('PASS: XML, manifest files/languages/media/SQL/schema paths, direct-access guards, translations and asset references')
