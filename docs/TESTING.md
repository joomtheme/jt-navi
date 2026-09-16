# 1.0.1 validation status

## Checks performed on 14 September 2026

- Source review against Joomla 6.1 documentation and selected Joomla CMS 6.1.3 core files.
- Existing live demo: hosting guide, installation environment choices, show-more source and missing link target confirmed. No live site files were changed.
- All 18 PHP files parsed using php-parser with PHP 8.3 grammar. This is not `php -l` or PHP runtime execution; no PHP executable was available.
- XML, manifest paths, SQL/schema paths, language key parity, direct-access guards and asset references checked with `python3 tools/package-check.py`.
- JavaScript syntax checked with `node --check extensions/mod_jtnavi/media/js/navi.js`.
- DOM regression check: local/AI source links, internal/external new-tab targets, opener/referrer protection, unsafe URL rejection, text escaping, show more/less, in-page choices, token submission and independent module instances. Network responses are mocked.
- Deterministic package build, nested ZIP integrity, manifest version alignment and staged update SHA256 checked locally.

## Release checks confirmed on 16 September 2026

- The maintainer confirmed that the corrected `pkg_jtnavi-1.0.1.zip` passed JED Checker. This does not constitute JED listing approval.
- The maintainer reported Joomla's successful package-uninstallation message. Data cleanup and every uninstall side effect were not independently inspected.
- Package structural checks and the reproducible build passed after correcting the package name and language client attributes. Compared with the original 1.0.1 ZIP, only `pkg_jtnavi.xml` changed; both child archives were byte-identical.
- GitHub's published release asset digest matched `7ff96051188172482af91ef6c232183774f8ae010a81ae99acaca192e55b5c36`. The live `update.xml` and `docs/update-1.0.1.xml` were updated to that checksum and read back successfully.

## Reproduce

```sh
python3 tools/package-check.py
node --check extensions/mod_jtnavi/media/js/navi.js
npm install --prefix /tmp/jtnavi-qa jsdom@30.0.1
NODE_PATH=/tmp/jtnavi-qa/node_modules node tools/frontend-check.cjs
python3 tools/build.py
```

The DOM test dependency is development-only and must not be shipped inside the Joomla extension.

## Pending live acceptance for the exact 1.0.1 ZIP

- Document a fresh-install smoke test and a 1.0.0-to-1.0.1 upgrade preserving options/custom buttons/sources. The maintainer has confirmed the JED Checker pass and successful uninstall message above; complete data-cleanup verification remains pending.
- Click local article and external guide results: original tab/question remains open; destination opens separately. Include newly expanded cards and a successful AI result.
- Joomla/CDN page cache refresh after upgrade; confirm the new versioned script is loaded. Update template overrides to include the new `NEW_TAB` translation string if overriding the module layout.
- Atum dashboard/Options, Cassiopeia inline/panel/compact layouts, Turkish/English, mobile, keyboard, screen reader, multiple modules, SEF/subdirectory and multilingual routing.
- Restricted, unpublished, expired, future-dated and restricted-parent articles must not appear. Check category/source filters and Turkish collation behavior.
- Real OpenAI success, consent, quota and concurrency checks. AI remains experimental; no paid AI request was made during this review.
- Test an update through Joomla using the now-published 1.0.1 package and updated live feed.

Earlier maintainer-reported checks for the preceding alpha/1.0.0 work are historical evidence, not validation of the new 1.0.1 ZIP. See `AUDIT-1.0.1-TR.md` for scope and remaining limitations.
