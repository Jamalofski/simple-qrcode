Simple QrCode
=============

A maintained fork of [simplesoftwareio/simple-qrcode](https://github.com/SimpleSoftwareIO/simple-qrcode), the QR code generator for Laravel built on [Bacon/BaconQrCode](https://github.com/Bacon/BaconQrCode).

The original package has had no release since 4.2.0 (February 2021). It requires `bacon/bacon-qr-code` ^2, while `laravel/fortify` requires ^3, so it can no longer be installed next to the official Laravel starter kits. This fork is a drop-in replacement:

- same namespace (`SimpleSoftwareIO\QrCode`), same `QrCode` facade, same API: no code change;
- `bacon/bacon-qr-code` ^3, PHP 8.2 to 8.5, Laravel 11 to 13;
- declares `replace` for `simplesoftwareio/simple-qrcode`, so packages that depend on the original name keep working;
- version numbers continue from the original: 4.3.0 is the first release of the fork.

## Installation

```bash
composer require jamalofski/simple-qrcode
```

Laravel discovers the service provider and the `QrCode` alias automatically.

`png` and `webp` images are rendered with `imagick` when it is installed, and with GD otherwise. GD draws square modules and square eyes only, without gradient: `style('dot')`, `style('round')`, eye styles other than `square` and `gradient()` need `imagick` in `png` and `webp`, and throw an exception without it. `svg` and `eps` support every option.

Since 4.4: `webp` output, hex colors, rounded and pointy eyes with separate external and internal styles, logos merged in `svg`, the png compression level, and the EPC QR code of SEPA credit transfers. See the [CHANGELOG](CHANGELOG.md).

## Migrating from simplesoftwareio/simple-qrcode

Swap the package in `composer.json`, nothing else:

```bash
composer remove simplesoftwareio/simple-qrcode --no-update
composer require jamalofski/simple-qrcode
```

The two packages cannot be installed together: Composer treats the fork as the original.

## Usage

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

QrCode::size(300)->generate('https://example.com');
```

```blade
{!! QrCode::size(200)->generate(Request::url()) !!}
```

The full documentation is in [docs/en/README.md](docs/en/README.md).

The package also works without Laravel, in any PHP 8.2 project, Symfony included: see [Usage Outside of Laravel](docs/en/README.md#usage-outside-of-laravel).

## Examples

![Example 1](docs/imgs/example-1.png) ![Example 2](docs/imgs/example-2.png)

## Translations

[Deutsch](docs/de/README.md) | [Español](docs/es/README.md) | [Français](docs/fr/README.md) | [Italiano](docs/it/README.md) | [Português](docs/pt-br/README.md) | [Русский](docs/ru/README.md) | [日本語](docs/ja/README.md) | [한국어](docs/kr/README.md) | [हिंदी](docs/hi/README.md) | [简体中文](docs/zh-cn/README.md) | [العربية](docs/ar/README.md)

The translations predate version 4 and some sections are out of date. The English documentation is the reference.

## Upgrading from 4.2

- PHP 8.2 or later is required.
- `bacon/bacon-qr-code` ^3 replaces ^2. If you use BaconQrCode classes directly, check its [changelog](https://github.com/Bacon/BaconQrCode/blob/main/CHANGELOG.md).
- The SVG output keeps the same shapes; some path segments are written in a different order, which matters only if you compare raw SVG strings.

## Contributing

Issues and pull requests are welcome on [jamalofski/simple-qrcode](https://github.com/jamalofski/simple-qrcode).

## Credits

Simple QrCode was created by Simple Software LLC and its contributors. This fork keeps their history and their MIT license.

## License

MIT. See [LICENSE](LICENSE).
