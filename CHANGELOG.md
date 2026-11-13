# Changelog

Changes of `jamalofski/simple-qrcode` and, up to 4.2.0, of `simplesoftwareio/simple-qrcode`. In each version, behavior changes, which can need a change in your code, come first. Upstream links point to [SimpleSoftwareIO/simple-qrcode](https://github.com/SimpleSoftwareIO/simple-qrcode).

## 4.4.0 (2026-11-13)

No existing signature changes.

### Behavior changes
- Without `errorCorrection()`, a merged logo raises the error correction from `L` to `H`. At `L`, most codes did not read behind a logo: 1 test code in 5 with a logo of 20%, none with 30%; all of them at `H`.
- `merge()` and `mergeString()` now apply to `svg`, where they were ignored ([upstream #166](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/166)): the logo is embedded as a data URI, any raster image PHP recognizes or an svg image. Content that is not an image throws an `InvalidArgumentException`. `eps` still ignores them.
- `wiFi()` escapes `\`, `;`, `,`, `"` and `:` in the SSID, the password and the encryption with a backslash, as phone readers expect: a password containing `;` gave a QR code that could not connect. If you escaped these characters yourself, remove your escaping.
- `Generator` gains public methods (`colorFromHex`, `backgroundColorFromHex`, `eyeColorFromHex`, `gradientFromHex`, `externalEye`, `internalEye`, `quality`, `compression`): a subclass that already defines one of them must match its new signature.

### Added
- `webp` format, lossless by default and usually smaller than `png`; `quality()` makes it lossy ([upstream #306](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/306)). It is rendered like a `png`, then converted by GD, which needs WebP support: it takes the `png` options, merged logos included, except `compression()`.
- `compression()` sets the zlib level of a `png`, from 0 to 9, merged logo included. A `png` is lossless: the level changes the size of the file, not the image.
- Hex colors: `colorFromHex()`, `backgroundColorFromHex()`, `eyeColorFromHex()` and `gradientFromHex()` take `#rgb`, `#rgba`, `#rrggbb` or `#rrggbbaa`. `color()` and the other methods keep their signatures.
- Eyes: `rounded` and `pointy` styles; `externalEye()` and `internalEye()` style the ring and the center of the eyes over `eye()`, and `externalEye('circle')` draws a round ring ([upstream #250](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/250), [#302](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/302)). `eye('circle')` keeps its square ring, as before.
- `EPC()`: the EPC QR code of a SEPA credit transfer (GiroCode), following the EPC069-12 guidelines, version 002. The IBAN checksum, the field lengths and the 331-byte limit are checked, and a BIC is required outside the European Economic Area; spaces in the IBAN are removed, non-breaking ones included. Error correction `M` and a character set matching the encoding.

### Changed
- A `webp`, or a `png` with a logo, is written 12 to 30% faster (GD, 1000 px): the intermediate `png` is no longer compressed at the highest level, then decoded.
- `composer.json`: the `ext-imagick` suggestion no longer says it is needed for `png`, which GD renders since 4.3.0.

### Documentation
- Use outside Laravel and in Symfony. A `Generator` keeps its settings, so a Symfony service must not be shared: a shared one served a `png` on the `svg` route of a test application.
- Unless an eye style is set, the `dot` style draws the eyes with dots, and some readers, ZXing among them, cannot find them ([upstream #204](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/204)): use `eye('square')` or `eye('circle')` with it.
- `png` gradients fail on ImageMagick 7.1.2-19 to 7.1.2-21 and 6.9.13-44 to 6.9.13-46, a regression of the CVE-2026-33901 fix that ImageMagick fixed in 7.1.2-22 and 6.9.13-47.

### Thanks
[fredbradley](https://github.com/fredbradley), [dxhung89](https://github.com/dxhung89), [IggsGrey](https://github.com/IggsGrey) and [RF1705](https://github.com/RF1705), co-authors of the hex colors, the png compression, the eye styles and the EPC QR code, from their upstream pull requests ([#276](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/276), [#288](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/288), [#307](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/307), [#283](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/283)).

## 4.3.0 (2026-11-13)

First release of the maintained fork, published as `jamalofski/simple-qrcode`. It declares `replace` for `simplesoftwareio/simple-qrcode`: same namespace, same facade, same API.

### Behavior changes
- Requires PHP 8.2 or later and `bacon/bacon-qr-code` ^3, so it installs next to `laravel/fortify` and the official Laravel starter kits. Tested on PHP 8.2 to 8.5 and Laravel 11 to 13.
- The `svg` output keeps the same shapes, but bacon 3 writes some path segments in a different order: this matters only if you compare raw svg strings.

### Fixed
- `png` works without `imagick`: it is rendered with GD (bacon `GDLibRenderer`) when `imagick` is missing. Options GD cannot draw (dot and round styles, circle eyes, gradients) throw a clear `RuntimeException` instead of failing in `imagick`.
- Merged logos keep a transparent background transparent (it turned opaque black).
- `email()` and `BTC()` encode spaces as `%20` instead of `+` (RFC 6068, BIP 21): iOS Mail showed the `+` signs.
- `generate()` declares `$filename` as nullable (PHP 8.4 deprecation).
- With bacon 3, the round style renders in `eps` ([upstream #290](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/290)) and a gradient with a margin no longer cuts the eyes off in `png` ([upstream #211](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/211)). Both were confirmed while preparing 4.4.0.

### Added
- The `QrCode` facade and the generator declare their methods in PHPDoc, for IDE completion of chained calls.

### Documentation
- Links to the original project website removed: the domain no longer belongs to the project.

### Thanks
[matteo-palazzo](https://github.com/matteo-palazzo) and [Roywcm](https://github.com/Roywcm), whose upstream pull requests ([#268](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/268), [#252](https://github.com/SimpleSoftwareIO/simple-qrcode/pull/252)) fixed the logo transparency.

## 4.2.0 (2021-02-08)

Summary of the upstream release notes.

### Added
- PHP 8.0 support ([upstream #215](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/215)).

### Fixed
- Some images did not look right once merged over a QrCode ([upstream #201](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/201)).

## 4.1.0 (2020-10-06)

Summary of the upstream release notes.

### Behavior changes
- With Laravel, `generate()` returns an `Illuminate\Support\HtmlString` instead of a string ([upstream #195](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/195)). This break should not have gone into a minor version ([upstream #205](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/205)).

### Added
- German translation ([upstream #196](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/196)); Brazilian Portuguese translation updated ([upstream #191](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/191)).

### Fixed
- Non-square images keep their aspect ratio when merged ([upstream #199](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/199)).

## 4.0.0 (2020-10-03)

Summary of the upstream release notes.

### Fixed
- The `bacon/bacon-qr-code` dependency ([upstream #175](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/175)) and the Laravel facade ([upstream #178](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/178)) load correctly.
- An SMS QrCode always includes its message ([upstream #169](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/169)).
- `DeferrableProvider` can be found ([upstream #177](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/177)).
- An alpha channel of `0` is applied ([upstream #172](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/172)).

## 3.0.0 (2020-06-02)

Summary of the upstream release notes.

### Behavior changes
- Requires PHP 7.2 or later.
- Requires `bacon/bacon-qr-code` 2 ([upstream #150](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/150)); `illuminate/support` is no longer required ([upstream #101](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/101)).

### Added
- `square`, `dot` and `round` styles, `square` and `circle` eyes, eye colors and gradients ([upstream #69](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/69)).
- Alpha channels for the foreground and background colors ([upstream #38](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/38)).

### Fixed
- A white square showed in `svg` QrCodes.
- The size was lost when methods were chained ([upstream #132](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/132)).

## 2.0.0 (2017-11-26)
* Fixed a bug where the merge image was not reset correctly ([upstream #93](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/93)).
* Added Laravel auto discovery support ([upstream #82](https://github.com/SimpleSoftwareIO/simple-qrcode/issues/82)). - Thanks [mazedlx](https://github.com/mazedlx)

## 1.5.1 (2016-12-06)
* Fixed a bug where a QrCode used within a loop would not generate correctly.

## 1.5.0 (2016-10-18)
* Added French translation. -Thanks [francisek](https://github.com/francisek) and [Varpie!](https://github.com/Varpie)
* Added BitCoin helper

## 1.4.6 (2016-10-12)
* Added Portuguese translation. -Thanks [felipewmartins!](https://github.com/felipewmartins)

## 1.4.5 (2016-10-11)
* Added Spanish translation. -Thanks [gtarraga!](https://github.com/gtarraga)
* Added Hindi translation. -Thanks [himanshu81494!](https://github.com/himanshu81494)

## 1.4.4 (2016-10-10)
* Added Italian translation. -Thanks [simocosimo!](https://github.com/simocosimo)

## 1.4.3 (2016-04-24)
* Updated the docs to our new format.
* Correct some typos in the Russian translation.

## 1.4.2 (2016-04-16)
* Added Russian translation. -Thanks Victoria!
* Added Chinese translation. -Thanks [blusewang!](https://github.com/blusewang)

## 1.4.1 (2016-03-17)
* Improved the quality of QrCodes with logos merge on them.  -Thanks [tuupke!](https://github.com/tuupke)

## 1.4.0 (2016-03-03)
* Added the `mergeString` method.  This method adds the ability to pass in a binary string to create a QrCode with an image on it.  -Thanks [tuupke!](https://github.com/tuupke)

## 1.3.3 (2016-01-31)
* Allow absolute paths to be used with the `merge` method.

## 1.3.2 (2015-12-22)
* Changed `bindShared` to `singleton` to support Laravel 5.2  -Thanks [lhdev!](https://github.com/lhdev)

## 1.3.1 (2015-09-18)
* Fixed a bug where `merge` did not work as expected when used with `size.`

## 1.3.0 (2015-06-25)
* Added `merge` method.
* Fixed a typo in the readme.

## 1.2.0 (2015-02-28)
* Added full support for Laravel 5
* Added the following helpers.
  * E-Mail
  * Geo
  * Phone Number
  * SMS
  * WiFi

## 1.1.1 (2014-11-15)
* Update dependencies.
* Corrected some composer file issues.
* Added some missing Laravel information to the QrCode Service Provider.

## 1.1.0 (2014-09-26)
* Added the ability to change the character encoding.
