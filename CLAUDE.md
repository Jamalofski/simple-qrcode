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
- A go on a change means commit, merge on `main` and push, in one step. Without a go: commit,
  then ask for the go in one question.
- Tests run locally (`composer test`). The `Tests` workflow (PHP 8.2-8.5 x Laravel 11-13) is
  `workflow_dispatch` only, run by hand before a release; nothing runs on push.
- Actions stay pinned by commit SHA.

## Local test setup (Windows)

The PHP on Jamal's machine has no php.ini. Use a temporary one passed through `PHPRC` that
loads openssl, curl, fileinfo, gd, mbstring and zip, and run `composer.phar` with that PHP.
Laravel 11 is out of security support: `composer update --no-security-blocking --with
"orchestra/testbench:^9.0"` to test it. imagick is not available locally, so the png test is
skipped there and runs only in the workflow.
