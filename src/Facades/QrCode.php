<?php

namespace SimpleSoftwareIO\QrCode\Facades;

use Illuminate\Support\Facades\Facade;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * @method static \Illuminate\Support\HtmlString|string|null generate(string $text, ?string $filename = null)
 * @method static \SimpleSoftwareIO\QrCode\Generator merge(string $filepath, float $percentage = .2, bool $absolute = false)
 * @method static \SimpleSoftwareIO\QrCode\Generator mergeString(string $content, float $percentage = .2)
 * @method static \SimpleSoftwareIO\QrCode\Generator size(int $pixels)
 * @method static \SimpleSoftwareIO\QrCode\Generator format(string $format)
 * @method static \SimpleSoftwareIO\QrCode\Generator color(int $red, int $green, int $blue, ?int $alpha = null)
 * @method static \SimpleSoftwareIO\QrCode\Generator backgroundColor(int $red, int $green, int $blue, ?int $alpha = null)
 * @method static \SimpleSoftwareIO\QrCode\Generator eyeColor(int $eyeNumber, int $innerRed, int $innerGreen, int $innerBlue, int $outterRed = 0, int $outterGreen = 0, int $outterBlue = 0)
 * @method static \SimpleSoftwareIO\QrCode\Generator gradient(int $startRed, int $startGreen, int $startBlue, int $endRed, int $endGreen, int $endBlue, string $type)
 * @method static \SimpleSoftwareIO\QrCode\Generator colorFromHex(string $hex)
 * @method static \SimpleSoftwareIO\QrCode\Generator backgroundColorFromHex(string $hex)
 * @method static \SimpleSoftwareIO\QrCode\Generator eyeColorFromHex(int $eyeNumber, string $innerHex, string $outterHex = '#000000')
 * @method static \SimpleSoftwareIO\QrCode\Generator gradientFromHex(string $startHex, string $endHex, string $type)
 * @method static \SimpleSoftwareIO\QrCode\Generator eye(string $style)
 * @method static \SimpleSoftwareIO\QrCode\Generator externalEye(string $style)
 * @method static \SimpleSoftwareIO\QrCode\Generator internalEye(string $style)
 * @method static \SimpleSoftwareIO\QrCode\Generator customEye(\BaconQrCode\Renderer\Eye\EyeInterface $eye)
 * @method static \SimpleSoftwareIO\QrCode\Generator style(string $style, float $size = 0.5)
 * @method static \SimpleSoftwareIO\QrCode\Generator customStyle(\BaconQrCode\Renderer\Module\ModuleInterface $module)
 * @method static \SimpleSoftwareIO\QrCode\Generator clearUnderLogo(int $margin = 0)
 * @method static \SimpleSoftwareIO\QrCode\Generator encoding(string $encoding)
 * @method static \SimpleSoftwareIO\QrCode\Generator errorCorrection(string $errorCorrection)
 * @method static \SimpleSoftwareIO\QrCode\Generator margin(int $margin)
 * @method static \SimpleSoftwareIO\QrCode\Generator quality(int $quality)
 * @method static \SimpleSoftwareIO\QrCode\Generator compression(int $level)
 * @method static \Illuminate\Support\HtmlString|string|null BTC(string $address, float|null $amount = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString|string|null calendar(array $event)
 * @method static \Illuminate\Support\HtmlString|string|null EPC(array $transfer)
 * @method static \Illuminate\Support\HtmlString|string|null email(?string $to = null, ?string $subject = null, ?string $body = null)
 * @method static \Illuminate\Support\HtmlString|string|null geo(float $latitude, float $longitude)
 * @method static \Illuminate\Support\HtmlString|string|null phoneNumber(string $phoneNumber)
 * @method static \Illuminate\Support\HtmlString|string|null SMS(string $phoneNumber, ?string $message = null)
 * @method static \Illuminate\Support\HtmlString|string|null wiFi(array $settings)
 *
 * @see \SimpleSoftwareIO\QrCode\Generator
 */
class QrCode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        self::clearResolvedInstance(Generator::class);

        return Generator::class;
    }
}
