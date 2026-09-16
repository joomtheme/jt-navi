# JT Navi

Visitor guides and local source search for Joomla 6, by [JoomTheme](https://joomtheme.com).

## Install
Version 1.0.1 is available from [Releases](https://github.com/joomtheme/jt-navi/releases/tag/v1.0.1). Download the installable `pkg_jtnavi-1.0.1.zip` asset.
Upload the ZIP through Joomla's extension installer. Do not install GitHub's automatically generated source archives.
Requires Joomla 6.x, PHP 8.3+, and MySQL or MariaDB.
Install over an existing JT Navi installation to preserve settings. Uninstall removes its stored data.

## Features
- Component dashboard and companion site module; inline, panel and compact layouts.
- Search public article titles and introductions, plus manually entered public source cards.
- Installation guides with separate computer and hosting steps.
- Editable, reorderable starting buttons; two source cards initially, with show more/less.
- Source links open in a new tab with opener/referrer protection; guide choices stay in place.
- English and Turkish; built-in responses follow the site language.
- Optional OpenAI summaries are **experimental**, disabled by default on new installations.
  A successful live AI answer has not yet been verified. Existing AI settings are preserved on upgrade.

## Setup
Open Components > JT Navi > Options to configure sources and article categories.
Publish the JT Navi site module with a position and menu assignment.
Under Suggested topics choose Custom buttons, then add a visible label and a search question for each button.
Custom button text and custom source summaries are entered manually, not automatically translated.

## Privacy and AI
Local retrieval makes no external AI request. URLs in custom source cards are links, not crawled pages.
With AI enabled and visitor consent, the current question and up to five public source excerpts are sent to OpenAI.
Use the `JTNAVI_OPENAI_API_KEY` environment variable, or the administrator API key option.
Keys entered in Options are stored in the Joomla database; protect administrator access and backups.
Question histories are not stored. Aggregate request counts, temporary session limits and the latest diagnostic category/status/time are retained.
Provider failures consume a reserved request slot. Request limits are not monetary budgets.

## Updates
`update.xml` and `changelog.xml` are served from this repository's main branch.
The update download points to the versioned ZIP attached to a GitHub Release.
Publish the exact release asset before announcing availability. See `docs/RELEASE.md`.

## Build
Run `python3 tools/build.py` (Python 3 standard library only).
The installable archive is written to `dist/`. Run `python3 tools/package-check.py` for structural checks.

## Validation
The maintainer confirmed that the corrected 1.0.1 release package passed JED Checker on 16 September 2026 and reported Joomla's successful package-uninstallation message. Passing JED Checker is a technical check, not JED listing approval.

Local package checks and the reproducible build passed for the corrected package. Earlier source checks included PHP grammar parsing and mocked DOM regression tests; these do not replace live runtime testing.

The published ZIP's SHA256 matches the checksum in the live update feed. Full live upgrade testing and successful paid AI output remain unverified. See [testing](docs/TESTING.md) for completed checks and remaining test coverage.

## Known scope
Search covers public article titles/introductions and configured source summaries, not full-site semantic search.
Installation intent uses keyword rules and may confuse extension installation with Joomla installation.
Each question is independent; there is no conversational memory. PostgreSQL is not supported.
The live update feed points to the published 1.0.1 package.

## License and support
GNU GPL version 2 or later; see LICENSE.txt.
[JoomTheme](https://joomtheme.com) · support@joomtheme.com
