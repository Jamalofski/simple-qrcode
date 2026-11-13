Simple QrCode
=============

##Change Log

#### 4.3.0 (unreleased)
* First release of the maintained fork, published as `jamalofski/simple-qrcode`. It declares `replace` for `simplesoftwareio/simple-qrcode`: same namespace, same facade, same API.
* Requires `bacon/bacon-qr-code` ^3, so it installs next to `laravel/fortify` and the official Laravel starter kits.
* Requires PHP 8.2 or later. Supports PHP 8.2 to 8.5 and Laravel 11 to 13.
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
