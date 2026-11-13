<?php

namespace SimpleSoftwareIO\QrCode;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Exception\WriterException;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\CompositeEye;
use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Eye\ModuleEye;
use BaconQrCode\Renderer\Eye\PointyEye;
use BaconQrCode\Renderer\Eye\SimpleCircleEye;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\EpsImageBackEnd;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\ModuleInterface;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use BaconQrCode\Renderer\RendererStyle\GradientType;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use BadMethodCallException;
use InvalidArgumentException;
use RuntimeException;
use SimpleSoftwareIO\QrCode\DataTypes\DataTypeInterface;
use SimpleSoftwareIO\QrCode\DataTypes\EPC;
use SimpleSoftwareIO\QrCode\Eye\CircleEye;
use SimpleSoftwareIO\QrCode\Eye\RoundedEye;

/**
 * Helpers resolved by __call() from the classes of the DataTypes namespace.
 *
 * @method \Illuminate\Support\HtmlString|string|null BTC(string $address, float|null $amount = null, array $options = [])
 * @method \Illuminate\Support\HtmlString|string|null EPC(array $transfer)
 * @method \Illuminate\Support\HtmlString|string|null email(?string $to = null, ?string $subject = null, ?string $body = null)
 * @method \Illuminate\Support\HtmlString|string|null geo(float $latitude, float $longitude)
 * @method \Illuminate\Support\HtmlString|string|null phoneNumber(string $phoneNumber)
 * @method \Illuminate\Support\HtmlString|string|null SMS(string $phoneNumber, ?string $message = null)
 * @method \Illuminate\Support\HtmlString|string|null wiFi(array $settings)
 */
class Generator
{
    /**
     * Holds the selected formatter.
     *
     * @var string
     */
    protected $format = 'svg';

    /**
     * Holds the size of the QrCode in pixels.
     *
     * @var int
     */
    protected $pixels = 100;

    /**
     * Holds the margin size of the QrCode.
     *
     * @var int
     */
    protected $margin = 0;

    /**
     * Holds the selected error correction.
     * L: 7% loss.
     * M: 15% loss.
     * Q: 25% loss.
     * H: 30% loss.
     *
     * @var string|null
     */
    protected $errorCorrection = null;

    /**
     * Holds the selected encoder.  Possible values are
     * ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
     * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
     * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
     * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
     * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
     *
     * @var string
     */
    protected $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING;

    /**
     * The style of the blocks within the QrCode.
     * Possible values are square, dot, and round.
     *
     * @var string
     */
    protected $style = 'square';

    /**
     * The size of the selected style between 0 and 1.
     * This only applies to dot and round.
     *
     * @var float|null
     */
    protected $styleSize = null;

    /**
     * The style to apply to the eye.
     * Possible values are square, circle, rounded and pointy.
     *
     * @var string|null
     */
    protected $eyeStyle = null;

    /**
     * The style of the external part of the eyes, over $eyeStyle.
     *
     * @var string|null
     */
    protected $externalEyeStyle = null;

    /**
     * The style of the internal part of the eyes, over $eyeStyle.
     *
     * @var string|null
     */
    protected $internalEyeStyle = null;

    /**
     * The quality of a webp image, from 0 to 100. Null means lossless.
     *
     * @var int|null
     */
    protected $quality = null;

    /**
     * The zlib compression level of a png image, from 0 to 9. Null means 9.
     *
     * @var int|null
     */
    protected $compression = null;

    /**
     * The foreground color of the QrCode.
     *
     * @var ColorInterface|null
     */
    protected $color = null;

    /**
     * The background color of the QrCode.
     *
     * @var ColorInterface|null
     */
    protected $backgroundColor = null;

    /**
     * An array that holds EyeFills of the color of the eyes.
     *
     * @var array
     */
    protected $eyeColors = [];

    /**
     * The gradient to apply to the QrCode.
     *
     * @var Gradient
     */
    protected $gradient;

    /**
     * Holds an image string that will be merged with the QrCode.
     *
     * @var null|string
     */
    protected $imageMerge = null;

    /**
     * The percentage that a merged image should take over the source image.
     *
     * @var float
     */
    protected $imagePercentage = .2;

    /**
     * Creates a new datatype object and then generates a QrCode.
     *
     * @param $method
     * @param array $arguments
     */
    public function __call($method, array $arguments)
    {
        $dataType = $this->createClass($method);
        $dataType->create($arguments);

        if ($dataType instanceof EPC) {
            // The EPC guidelines require error correction M and a character set that matches
            // the bytes of the QR code. Neither changes the settings of this generator.
            $generator = clone $this;
            $generator->errorCorrection = $this->errorCorrection ?? ErrorCorrectionLevel::M();

            return $generator->generate($dataType->forEncoding($this->encoding));
        }

        return $this->generate(strval($dataType));
    }

    /**
     * Generates the QrCode.
     *
     * @param string $text
     * @param string|null $filename
     * @return void|\Illuminate\Support\HtmlString|string
     * @throws WriterException
     * @throws InvalidArgumentException
     * @throws RuntimeException when png is requested without imagick and with an option GD cannot render
     */
    public function generate(string $text, ?string $filename = null)
    {
        $writer = $this->usesGdForPng()
            ? new Writer($this->getGdRenderer())
            : $this->getWriter($this->getRenderer());

        $qrCode = $writer->writeString($text, $this->encoding, $this->errorCorrection);

        if ($this->imageMerge !== null && in_array($this->format, ['png', 'webp'])) {
            $merger = new ImageMerge(new Image($qrCode), new Image($this->imageMerge));
            $qrCode = $merger->merge($this->imagePercentage);
        }

        if ($this->imageMerge !== null && $this->format === 'svg') {
            $qrCode = (new SvgImageMerge($qrCode, $this->imageMerge))->merge($this->imagePercentage);
        }

        if ($this->format === 'webp') {
            $qrCode = $this->convertToWebp($qrCode);
        }

        if ($filename) {
            file_put_contents($filename, $qrCode);

            return;
        }

        if (class_exists(\Illuminate\Support\HtmlString::class)) {
            return new \Illuminate\Support\HtmlString($qrCode);
        }

        return $qrCode;
    }

    /**
     * Merges an image over the QrCode.
     *
     * @param string $filepath
     * @param float $percentage
     * @param SimpleSoftwareIO\QrCode\boolean|bool $absolute
     * @return Generator
     */
    public function merge(string $filepath, float $percentage = .2, bool $absolute = false): self
    {
        if (function_exists('base_path') && ! $absolute) {
            $filepath = base_path().$filepath;
        }

        $this->imageMerge = file_get_contents($filepath);
        $this->imagePercentage = $percentage;

        return $this;
    }

    /**
     * Merges an image string with the center of the QrCode.
     *
     * @param string  $content
     * @param float $percentage
     * @return Generator
     */
    public function mergeString(string $content, float $percentage = .2): self
    {
        $this->imageMerge = $content;
        $this->imagePercentage = $percentage;

        return $this;
    }

    /**
     * Sets the size of the QrCode.
     *
     * @param int $pixels
     * @return Generator
     */
    public function size(int $pixels): self
    {
        $this->pixels = $pixels;

        return $this;
    }

    /**
     * Sets the format of the QrCode.
     *
     * @param string $format
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function format(string $format): self
    {
        if (! in_array($format, ['svg', 'eps', 'png', 'webp'])) {
            throw new InvalidArgumentException("\$format must be svg, eps, png, or webp. {$format} is not a valid.");
        }

        $this->format = $format;

        return $this;
    }

    /**
     * Sets the foreground color of the QrCode.
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param null|int $alpha
     * @return Generator
     */
    public function color(int $red, int $green, int $blue, ?int $alpha = null): self
    {
        $this->color = $this->createColor($red, $green, $blue, $alpha);

        return $this;
    }

    /**
     * Sets the background color of the QrCode.
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param null|int $alpha
     * @return Generator
     */
    public function backgroundColor(int $red, int $green, int $blue, ?int $alpha = null): self
    {
        $this->backgroundColor = $this->createColor($red, $green, $blue, $alpha);

        return $this;
    }

    /**
     * Sets the foreground color of the QrCode from a hex color.
     *
     * @param string $hex #rgb, #rgba, #rrggbb or #rrggbbaa, with or without the #
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function colorFromHex(string $hex): self
    {
        $this->color = $this->hexToColor($hex);

        return $this;
    }

    /**
     * Sets the background color of the QrCode from a hex color.
     *
     * @param string $hex #rgb, #rgba, #rrggbb or #rrggbbaa, with or without the #
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function backgroundColorFromHex(string $hex): self
    {
        $this->backgroundColor = $this->hexToColor($hex);

        return $this;
    }

    /**
     * Sets the eye color for the provided eye index.
     *
     * @param int $eyeNumber
     * @param int $innerRed
     * @param int $innerGreen
     * @param int $innerBlue
     * @param int $outterRed
     * @param int $outterGreen
     * @param int $outterBlue
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function eyeColor(int $eyeNumber, int $innerRed, int $innerGreen, int $innerBlue, int $outterRed = 0, int $outterGreen = 0, int $outterBlue = 0): self
    {
        if ($eyeNumber < 0 || $eyeNumber > 2) {
            throw new InvalidArgumentException("\$eyeNumber must be 0, 1, or 2.  {$eyeNumber} is not valid.");
        }

        $this->eyeColors[$eyeNumber] = new EyeFill(
            $this->createColor($innerRed, $innerGreen, $innerBlue),
            $this->createColor($outterRed, $outterGreen, $outterBlue)
        );

        return $this;
    }

    /**
     * Sets the eye color for the provided eye index from hex colors,
     * in the same order as eyeColor().
     *
     * @param int $eyeNumber
     * @param string $innerHex
     * @param string $outterHex
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function eyeColorFromHex(int $eyeNumber, string $innerHex, string $outterHex = '#000000'): self
    {
        if ($eyeNumber < 0 || $eyeNumber > 2) {
            throw new InvalidArgumentException("\$eyeNumber must be 0, 1, or 2.  {$eyeNumber} is not valid.");
        }

        $this->eyeColors[$eyeNumber] = new EyeFill($this->hexToColor($innerHex), $this->hexToColor($outterHex));

        return $this;
    }

    public function gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, string $type): self
    {
        $type = strtoupper($type);
        $this->gradient = new Gradient(
            $this->createColor($startRed, $startGreen, $startBlue),
            $this->createColor($endRed, $endGreen, $endBlue),
            GradientType::$type()
        );

        return $this;
    }

    /**
     * Sets a gradient from hex colors.
     *
     * @param string $startHex
     * @param string $endHex
     * @param string $type vertical, horizontal, diagonal, inverse_diagonal or radial
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function gradientFromHex(string $startHex, string $endHex, string $type): self
    {
        $type = strtoupper($type);
        $this->gradient = new Gradient($this->hexToColor($startHex), $this->hexToColor($endHex), GradientType::$type());

        return $this;
    }

    /**
     * Sets the eye style, for the external and internal parts of the eyes.
     *
     * @param string $style square, circle, rounded or pointy
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function eye(string $style): self
    {
        $this->eyeStyle = $this->validEyeStyle($style);

        return $this;
    }

    /**
     * Sets the style of the external part of the eyes, the ring, over the one set with eye().
     *
     * @param string $style square, circle, rounded or pointy
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function externalEye(string $style): self
    {
        $this->externalEyeStyle = $this->validEyeStyle($style);

        return $this;
    }

    /**
     * Sets the style of the internal part of the eyes, the center, over the one set with eye().
     *
     * @param string $style square, circle, rounded or pointy
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function internalEye(string $style): self
    {
        $this->internalEyeStyle = $this->validEyeStyle($style);

        return $this;
    }

    /**
     * Sets the quality of a webp image. Without it, webp is lossless.
     *
     * @param int $quality from 0 to 100
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function quality(int $quality): self
    {
        if ($quality < 0 || $quality > 100) {
            throw new InvalidArgumentException("\$quality must be between 0 and 100. {$quality} is not valid.");
        }

        $this->quality = $quality;

        return $this;
    }

    /**
     * Sets the zlib compression level of a png image. Png is lossless: the level
     * only trades the file size against the time to write it. The default is 9.
     *
     * @param int $level from 0 (none, fastest) to 9 (smallest file)
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function compression(int $level): self
    {
        if ($level < 0 || $level > 9) {
            throw new InvalidArgumentException("\$level must be between 0 and 9. {$level} is not valid.");
        }

        $this->compression = $level;

        return $this;
    }

    /**
     * Sets the style of the blocks for the QrCode.
     *
     * @param string $style
     * @param float $size
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function style(string $style, float $size = 0.5): self
    {
        if (! in_array($style, ['square', 'dot', 'round'])) {
            throw new InvalidArgumentException("\$style must be square, dot, or round. {$style} is not a valid.");
        }

        if ($size < 0 || $size >= 1) {
            throw new InvalidArgumentException("\$size must be between 0 and 1.  {$size} is not valid.");
        }

        $this->style = $style;
        $this->styleSize = $size;

        return $this;
    }

    /**
     * Sets the encoding for the QrCode.
     * Possible values are
     * ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
     * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
     * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
     * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
     * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
     *
     * @param string $encoding
     * @return Generator
     */
    public function encoding(string $encoding): self
    {
        $this->encoding = strtoupper($encoding);

        return $this;
    }

    /**
     * Sets the error correction for the QrCode.
     * L: 7% loss.
     * M: 15% loss.
     * Q: 25% loss.
     * H: 30% loss.
     *
     * @param string $errorCorrection
     * @return Generator
     */
    public function errorCorrection(string $errorCorrection): self
    {
        $errorCorrection = strtoupper($errorCorrection);
        $this->errorCorrection = ErrorCorrectionLevel::$errorCorrection();

        return $this;
    }

    /**
     * Sets the margin of the QrCode.
     *
     * @param int $margin
     * @return Generator
     */
    public function margin(int $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * Fetches the Writer.
     *
     * @param ImageRenderer $renderer
     * @return Writer
     */
    public function getWriter(ImageRenderer $renderer): Writer
    {
        return new Writer($renderer);
    }

    /**
     * Fetches the Image Renderer.
     *
     * @return ImageRenderer
     */
    public function getRenderer(): ImageRenderer
    {
        $renderer = new ImageRenderer(
            $this->getRendererStyle(),
            $this->getFormatter()
        );

        return $renderer;
    }

    /**
     * Returns the Renderer Style.
     *
     * @return RendererStyle
     */
    public function getRendererStyle(): RendererStyle
    {
        return new RendererStyle($this->pixels, $this->margin, $this->getModule(), $this->getEye(), $this->getFill());
    }

    /**
     * Whether a png is rendered with GD, because imagick is not installed.
     * A webp image is a png converted by GD, so this applies to webp too.
     *
     * @return bool
     */
    public function usesGdForPng(): bool
    {
        return in_array($this->format, ['png', 'webp']) && ! $this->imagickIsAvailable();
    }

    /**
     * Fetches the GD renderer used for png when imagick is not installed.
     * GD draws square modules and square eyes only, without gradient.
     *
     * @return GDLibRenderer
     * @throws RuntimeException when an option GD cannot render is set
     */
    public function getGdRenderer(): GDLibRenderer
    {
        $unsupported = [];

        if ($this->style !== 'square') {
            $unsupported[] = "style('{$this->style}')";
        }

        foreach (['eye' => $this->eyeStyle, 'externalEye' => $this->externalEyeStyle, 'internalEye' => $this->internalEyeStyle] as $method => $eyeStyle) {
            if ($eyeStyle !== null && $eyeStyle !== 'square') {
                $unsupported[] = "{$method}('{$eyeStyle}')";
            }
        }

        if ($this->gradient) {
            $unsupported[] = 'gradient()';
        }

        if ($unsupported) {
            throw new RuntimeException(
                "The {$this->format} format needs the imagick extension for ".implode(', ', $unsupported).'. '
                ."Without imagick, {$this->format} is rendered with GD, which draws square modules and square eyes only, without gradient. "
                .'Install imagick, or use the svg or eps format.'
            );
        }

        return new GDLibRenderer($this->pixels, $this->margin, 'png', $this->compression ?? 9, $this->getFill());
    }

    /**
     * Whether the imagick extension is available.
     *
     * @return bool
     */
    protected function imagickIsAvailable(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * Fetches the formatter.
     *
     * @return ImageBackEndInterface
     */
    public function getFormatter(): ImageBackEndInterface
    {
        if (in_array($this->format, ['png', 'webp'])) {
            // ImageMagick reads the png "quality" as the zlib level (tens) and the filter (units).
            return new ImagickImageBackEnd('png', $this->compression === null ? 100 : $this->compression * 10);
        }

        if ($this->format === 'eps') {
            return new EpsImageBackEnd;
        }

        return new SvgImageBackEnd;
    }

    /**
     * Fetches the module.
     *
     * @return ModuleInterface
     */
    public function getModule(): ModuleInterface
    {
        if ($this->style === 'dot') {
            return new DotsModule($this->styleSize);
        }

        if ($this->style === 'round') {
            return new RoundnessModule($this->styleSize);
        }

        return SquareModule::instance();
    }

    /**
     * Fetches the eye style.
     *
     * @return EyeInterface
     */
    public function getEye(): EyeInterface
    {
        $eye = $this->createEye($this->eyeStyle) ?? new ModuleEye($this->getModule());

        if ($this->externalEyeStyle === null && $this->internalEyeStyle === null) {
            return $eye;
        }

        // eye('circle') keeps bacon's square ring; externalEye('circle') asks for a round one.
        $external = $this->externalEyeStyle === 'circle' ? CircleEye::instance() : $this->createEye($this->externalEyeStyle);

        return new CompositeEye($external ?? $eye, $this->createEye($this->internalEyeStyle) ?? $eye);
    }

    /**
     * Creates the eye of a style, or null without a style.
     *
     * @param string|null $style
     * @return EyeInterface|null
     */
    protected function createEye(?string $style): ?EyeInterface
    {
        return match ($style) {
            'square' => SquareEye::instance(),
            'circle' => SimpleCircleEye::instance(),
            'rounded' => RoundedEye::instance(),
            'pointy' => PointyEye::instance(),
            default => null,
        };
    }

    /**
     * Checks an eye style.
     *
     * @param string $style
     * @return string
     * @throws InvalidArgumentException
     */
    protected function validEyeStyle(string $style): string
    {
        if (! in_array($style, ['square', 'circle', 'rounded', 'pointy'])) {
            throw new InvalidArgumentException("\$style must be square, circle, rounded or pointy. {$style} is not a valid eye style.");
        }

        return $style;
    }

    /**
     * Converts a hex color to a RGB or Alpha channel color.
     *
     * @param string $hex #rgb, #rgba, #rrggbb or #rrggbbaa, with or without the #
     * @return ColorInterface
     * @throws InvalidArgumentException
     */
    protected function hexToColor(string $hex): ColorInterface
    {
        $digits = str_starts_with($hex, '#') ? substr($hex, 1) : $hex;

        if (! preg_match('/^(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $digits)) {
            throw new InvalidArgumentException("{$hex} is not a valid hex color. Use #rgb, #rgba, #rrggbb or #rrggbbaa.");
        }

        if (strlen($digits) <= 4) {
            $digits = preg_replace('/(.)/', '$1$1', $digits);
        }

        [$red, $green, $blue] = array_map('hexdec', str_split(substr($digits, 0, 6), 2));
        // The alpha byte (00 to ff) becomes the 0 to 100 alpha of the other color methods.
        $alpha = strlen($digits) === 8 ? (int) round(hexdec(substr($digits, 6, 2)) * 100 / 255) : null;

        return $this->createColor($red, $green, $blue, $alpha);
    }

    /**
     * Converts a png image to webp with GD: lossless, or lossy with quality().
     *
     * @param string $png
     * @return string
     * @throws RuntimeException when GD has no WebP support
     */
    protected function convertToWebp(string $png): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('The webp format needs the GD extension with WebP support.');
        }

        $image = imagecreatefromstring($png);

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, $this->quality ?? (defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : 101));

        return ob_get_clean();
    }

    /**
     * Fetches the color fill.
     *
     * @return Fill
     */
    public function getFill(): Fill
    {
        $foregroundColor = $this->color ?? new Rgb(0, 0, 0);
        $backgroundColor = $this->backgroundColor ?? new Rgb(255, 255, 255);
        $eye0 = $this->eyeColors[0] ?? EyeFill::inherit();
        $eye1 = $this->eyeColors[1] ?? EyeFill::inherit();
        $eye2 = $this->eyeColors[2] ?? EyeFill::inherit();

        if ($this->gradient) {
            return Fill::withForegroundGradient($backgroundColor, $this->gradient, $eye0, $eye1, $eye2);
        }

        return Fill::withForegroundColor($backgroundColor, $foregroundColor, $eye0, $eye1, $eye2);
    }

    /**
     * Creates a RGB or Alpha channel color.
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param null|int $alpha
     * @return ColorInterface
     */
    public function createColor(int $red, int $green, int $blue, ?int $alpha = null): ColorInterface
    {
        if (is_null($alpha)) {
            return new Rgb($red, $green, $blue);
        }

        return new Alpha($alpha, new Rgb($red, $green, $blue));
    }

    /**
     * Creates a new DataType class dynamically.
     *
     * @param string $method
     * @return DataTypeInterface
     */
    protected function createClass(string $method): DataTypeInterface
    {
        $class = $this->formatClass($method);

        if (! class_exists($class)) {
            throw new BadMethodCallException();
        }

        return new $class();
    }

    /**
     * Formats the method name correctly.
     *
     * @param $method
     * @return string
     */
    protected function formatClass(string $method): string
    {
        $method = ucfirst($method);

        $class = "SimpleSoftwareIO\QrCode\DataTypes\\".$method;

        return $class;
    }
}
