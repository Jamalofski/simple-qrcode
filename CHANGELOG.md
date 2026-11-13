Simple QrCode
=============

##Change Log

#### 4.4.0 (unreleased)
No existing signature changes. Three behaviors do, listed first. `Generator` gains public methods (`colorFromHex`, `backgroundColorFromHex`, `eyeColorFromHex`, `gradientFromHex`, `externalEye`, `internalEye`, `quality`, `compression`): a subclass that already defines one of them must match its new signature.
* Behavior: without `errorCorrection()`, a merged logo raises the error correction from `L` to `H`. At `L`, most codes did not read behind a logo: 1 test code in 5 with a logo of 20%, none with 30%; all of them at `H`.
* Behavior: `merge()` and `mergeString()` now apply to `svg`, where they were ignored (upstream #166): the logo is embedded as a data URI, any raster image PHP recognizes or an svg image. Content that is not an image throws an `InvalidArgumentException`. `eps` still ignores them.
* Behavior: `wiFi()` escapes `\`, `;`, `,`, `"` and `:` in the SSID, the password and the encryption with a backslash, as phone readers expect. A password containing `;` gave a QR code that could not connect. If you escaped these characters yourself, remove your escaping.
* `webp` format, lossless by default and usually smaller than `png`; `quality()` makes it lossy (upstream #306). It is rendered like a `png`, then converted by GD: it takes the `png` options, merged logos included, except `compression()`.
* `compression()` sets the zlib level of a `png`, from 0 to 9, merged logo included (upstream #209, idea and first version in upstream PR #288 by [dxhung89](https://github.com/dxhung89)). A `webp`, or a `png` with a logo, is written faster: the intermediate `png` is no longer compressed at the highest level, then decoded.
* Hex colors: `colorFromHex()`, `backgroundColorFromHex()`, `eyeColorFromHex()` and `gradientFromHex()` take `#rgb`, `#rgba`, `#rrggbb` or `#rrggbbaa` (upstream PR #276 by [fredbradley](https://github.com/fredbradley)). `color()` and the other methods keep their signatures.
* Eyes: new `rounded` and `pointy` styles; `externalEye()` and `internalEye()` style the ring and the center of the eyes over `eye()`, and `externalEye('circle')` draws a round ring (upstream #250, #302, PR #307 by [IggsGrey](https://github.com/IggsGrey)). `eye('circle')` keeps its square ring, as before.
* `EPC()` helper: the EPC QR code of a SEPA credit transfer (GiroCode), following the EPC069-12 guidelines, version 002: IBAN checksum, BIC required outside the European Economic Area, field lengths and the 331-byte limit checked; spaces in the IBAN are removed, non-breaking ones included; error correction `M` and a character set matching the encoding (upstream PR #283 by [RF1705](https://github.com/RF1705)).
* Documentation: use outside Laravel and in Symfony. A `Generator` keeps its settings, so a Symfony service must not be shared: a shared one served a `png` on the `svg` route of a test application.
* Documentation: unless an eye style is set, the `dot` style draws the eyes with dots, and some readers, ZXing among them, cannot find them (upstream #204). The documentation now recommends `eye('square')` or `eye('circle')` with it.
* Documentation: `png` gradients fail on ImageMagick 7.1.2-19 to 7.1.2-21 and 6.9.13-44 to 6.9.13-46, a regression of the CVE-2026-33901 fix that ImageMagick fixed in 7.1.2-22 and 6.9.13-47.
* `composer.json`: the `ext-imagick` suggestion no longer says it is needed for `png`. Since 4.3.0, `png` is rendered with GD without it; `imagick` is needed only for dot and round styles, eye styles other than square and gradients.
* Upstream #290 (round style broken in `eps`) and #211 (a gradient with a margin cut the eyes off in `png`) no longer reproduce with bacon 3.

#### 4.3.0 (2026-11-13)
* First release of the maintained fork, published as `jamalofski/simple-qrcode`. It declares `replace` for `simplesoftwareio/simple-qrcode`: same namespace, same facade, same API.
* Requires `bacon/bacon-qr-code` ^3, so it installs next to `laravel/fortify` and the official Laravel starter kits.
* Requires PHP 8.2 or later. Tested on PHP 8.2 to 8.5 and Laravel 11 to 13.
* `generate()` declares `$filename` as nullable (PHP 8.4 deprecation).
* `png` works without `imagick`: it is rendered with GD (bacon `GDLibRenderer`) when `imagick` is missing. Options GD cannot draw (dot and round styles, circle eyes, gradients) throw a clear `RuntimeException` instead of failing in `imagick`.
* Merged logos keep a transparent background transparent (it turned opaque black).
* `email()` and `BTC()` encode spaces as `%20` instead of `+` (RFC 6068, BIP 21): iOS Mail showed the `+` signs.
* The `QrCode` facade and the generator declare their methods in PHPDoc, for IDE completion of chained calls.
* Documentation: links to the original project website removed (the domain no longer belongs to the project).

#### 2.0.0
* Fixed a bug where the merge image was not reset correctly. (#93)
* Added Laravel auto discovery support. (#82) - Thanks [mazedlx](https://github.com/mazedlx)

#### 1.5.1
* Fixed a bug where a QrCode used within a loop would not generate correctly.

#### 1.5.0
* Added Portuguese translation. -Thanks [francisek](https://github.com/francisek) and [Varpie!](https://github.com/Varpie)
* Added BitCoin helper

#### 1.4.6
* Added Portuguese translation. -Thanks [felipewmartins!](https://github.com/felipewmartins)

#### 1.4.5
* Added Spanish translation. -Thanks [gtarraga!](https://github.com/gtarraga)
* Added Hindi translation. -Thanks [himanshu81494!](https://github.com/himanshu81494)

#### 1.4.4
* Added Italian translation. -Thanks [simocosimo!](https://github.com/simocosimo)

#### 1.4.3
* Updated the docs to our new format.
* Correct some typos in the Russian translation.

#### 1.4.2
* Added Russian translation. -Thanks Victoria!
* Added Chinese translation. -Thanks [blusewang!](https://github.com/blusewang)

#### 1.4.1
* Improved the quality of QrCodes with logos merge on them.  -Thanks [tuupke!](https://github.com/tuupke)

#### 1.4.0
* Added the `mergeString` method.  This method adds the ability to pass in a binary string to create a QrCode with an image on it.  -Thanks [tuupke!](https://github.com/tuupke)

#### 1.3.3
* Allow absolute paths to be used with the `merge` method.

#### 1.3.2
* Changed `bindShared` to `singleton` to support Laravel 5.2  -Thanks [lhdev!](https://github.com/lhdev)

#### 1.3.1
* Fixed a bug where `merge` did not work as expected when used with `size.`

#### 1.3.0
* Added `merge` method.
* Fixed a typo in the readme.

#### 1.2.0
* Added full support for Laravel 5
* Added the following helpers.
  * E-Mail
  * Geo
  * Phone Number
  * SMS
  * WiFi

#### 1.1.1
* Update dependencies.
* Corrected some composer file issues.
* Added some missing Laravel information to the QrCode Service Provider.

####1.1.0
* Added the ability to change the character encoding.
