# simple-qrcode (jamalofski)

Maintained fork of `simplesoftwareio/simple-qrcode`, published as `jamalofski/simple-qrcode`.
It is a drop-in replacement: same namespace `SimpleSoftwareIO\QrCode`, same `QrCode` facade,
same public API, `replace` of `simplesoftwareio/simple-qrcode` in composer.json, version
numbers continued from upstream (4.3.0 first). Never rename the namespace or break the API in
a 4.x release: that is what made every earlier fork fail.

Context: issue SimpleSoftwareIO/simple-qrcode#332 (24/10/2026) offered to take over the
original package; no answer after three weeks, hence this fork (13/11/2026). If the
maintainers answer, the goal is still to get the original name marked abandoned on Packagist
in favour of this fork, or transferred.

## Rules

- The upstream repository and its issues are someone else's project: nothing is posted there
  (comment, PR, issue) without Jamal's explicit go on the exact text.
- Publishing (making the repository public, registering on Packagist, tagging a release) is a
  separate, explicit go from Jamal.
- No link to `simplesoftware.io` anywhere except the original copyright line of `LICENSE`: the
  domain now points to a scam site.

## Git, CI and delivery

- Trunk-based: commit on `main`, no pull request unless asked. A session branch is deleted
  once merged.
- Commit messages are in English, like the code, the CHANGELOG and the releases: the
  repository is public. The commits from 13/11 to 22/11/2026 are in French and stay as they
  are, since rewriting them would change the commits the release tags point to.
- A go on a change means commit, merge on `main` and push, in one step. Without a go: commit,
  then ask for the go in one question.
- Tests run locally (`composer test`), with the static analysis (`composer analyse`, PHPStan
  level 7 on `src`, no error allowed). Keep the PHPDoc types right: PHPStan reads them, and the
  public signatures cannot carry native types in 4.x. The `Tests` workflow (PHP 8.2-8.6 x Laravel 11-13) is
  `workflow_dispatch` only, run by hand before a release; nothing runs on push. To check a
  fix, run a subset:
  `gh workflow run tests.yml -R Jamalofski/simple-qrcode -f php='["8.4"]' -f laravel='["13"]'`.
  Always pass `-R` to `gh` here: with the `upstream` remote, gh targets
  SimpleSoftwareIO/simple-qrcode otherwise (20/11/2026, a dispatch that answered 404).
- Actions stay pinned by commit SHA.

## Changelog, releases and README

- `CHANGELOG.md` is the single source of changes. One entry per version, `## x.y.z (YYYY-MM-DD)`,
  with the sections that apply, in this order: `### Behavior changes` (what can need a change in
  the user's code), `Added`, `Changed`, `Fixed`, `Documentation`, `Thanks`. Upstream issues and
  pull requests are links to SimpleSoftwareIO/simple-qrcode.
- The body of a GitHub release is the CHANGELOG entry word for word, its `###` sections raised
  to `##`, with the upgrade commands between its opening lines and its first section, as in the
  4.4.0 release. Never write release notes apart from the CHANGELOG: the two drifted in 4.4.0.
  Print an entry with
  `awk -v v=4.4.0 'index($0, "## " v " ") == 1 {f=1; next} /^## / && f {exit} f' CHANGELOG.md`.
- The README describes the package as it is, with no list of what a version adds; its
  "Migrating" section lists the behavior changes since 4.2.
- `SECURITY.md` lists the latest minor version as the supported one: update it with each
  minor release (it still said 4.3.x after 4.5.0).

## Local test setup (Windows)

The PHP on Jamal's machine has no php.ini. Use a temporary one passed through `PHPRC` that
loads openssl, curl, fileinfo, gd, mbstring and zip, and run `composer.phar` with that PHP.
Laravel 11 is out of security support: `composer update --no-security-blocking --with
"orchestra/testbench:^9.0"` to test it. imagick is not available locally: the png tests run on GD there
(`GeneratorWithoutImagick` in tests/PngTest.php forces GD everywhere), and their imagick
variants run only in the workflow. Every png test decodes the image with
khanamiryan/qrcode-detector-decoder.
