# Publish 1.0.1 (maintainer action)

1. Build with `python3 tools/build.py` and perform the pending live checks in `docs/TESTING.md`.
2. Create tag `v1.0.1` and a GitHub Release targeting the reviewed commit on `main`. Title: `JT Navi 1.0.1`.
3. Attach the exact `dist/pkg_jtnavi-1.0.1.zip`. Check its SHA256 against the generated sidecar and staged feed.
4. Publish the release with the notes below. Verify the asset is publicly downloadable.
5. Only after step 4, replace the root `update.xml` with `docs/update-1.0.1.xml` (or freshly generated `dist/update.xml`), commit and push. The feed SHA256 must match the exact uploaded ZIP.
6. Verify raw `update.xml` / `changelog.xml`, clear Joomla's update cache and test an in-place update.

**Release publication is intentionally left to the maintainer.** The live root update feed stays on published 1.0.0 until the new asset exists. The staged feed is ready for 1.0.1 but is not used by installed sites. Rebuilding after changing packaged files changes the ZIP hash: refresh the staged feed as well.

The package updates both component and module. Do not publish separate child update feeds. Older alpha installations need a manual package install once to register the update server. Do not uninstall before upgrading: uninstall removes stored data.

## Release notes

Maintenance release for Joomla 6.x visitor guides and local source search.

- Open every source result in a new tab, preserving the original question and guide. Applies to public article results, custom sources, installation references and sources accompanying AI answers.
- Add `noopener noreferrer` protection and English/Turkish screen-reader text announcing the new tab.
- Add explicit JS/CSS asset versions so browsers can fetch the updated frontend files.
- Include repeatable DOM regression checks and a Joomla 6.1.3 review with documented limitations.

Installation choices and show-more/show-less buttons still work inside the current guide.
Existing options and data are preserved on in-place upgrade. No database schema change.
Optional OpenAI summaries remain experimental and are disabled by default on new installations; live paid AI output remains unverified.

Requires Joomla 6.x, PHP 8.3+, MySQL or MariaDB compatible with the installed Joomla release. PostgreSQL is not supported by JT Navi.
Install `pkg_jtnavi-1.0.1.zip` through Joomla's extension installer. GitHub source archives are not installable Joomla packages.
