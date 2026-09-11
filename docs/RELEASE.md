# Publish 1.0.0

1. Perform the final installation/removal and JED Checker checks on `pkg_jtnavi-1.0.0.zip`.
2. Create a GitHub Release with tag `v1.0.0`, targeting `main`. Title: `JT Navi 1.0.0`.
3. Attach the exact `pkg_jtnavi-1.0.0.zip` produced by `python3 tools/build.py`.
4. Use the release notes below. Publish when ready; do not mark the whole release as prerelease.
5. Verify the ZIP download and raw `update.xml` / `changelog.xml` links are accessible.

The update download URL remains unavailable until this release asset is published.
Earlier alpha installations lack the update-server declaration: install 1.0.0 manually once.
For later releases, publish the new ZIP first, then update the feed version, download URL and changelog.
The package updates both its component and module; do not publish separate child update feeds.

## Release notes

First stable release of JT Navi's visitor guides and local source search for Joomla 6.

- Configurable starting buttons with add, remove and reorder controls.
- Separate computer and hosting installation guides.
- Public article and custom source search with expandable results.
- English and Turkish interface and built-in guides.
- Database initialization, API key input and package-name fixes.
- Joomla update-server and changelog support.

Optional OpenAI summaries remain experimental and are disabled by default on new installations.
Successful live AI output has not yet been verified. Existing settings are preserved on upgrade.

Requires Joomla 6.x, PHP 8.3+, MySQL or MariaDB.
Install the attached package ZIP through Joomla. GitHub source archives are not installable packages.
