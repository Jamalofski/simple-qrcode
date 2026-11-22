<?php

namespace SimpleSoftwareIO\QrCode;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Encoder\QrCode as EncodedQrCode;
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
use BaconQrCode\Renderer\RendererInterface;
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
use LogicException;
use RuntimeException;
use SimpleSoftwareIO\QrCode\DataTypes\DataTypeInterface;
use SimpleSoftwareIO\QrCode\DataTypes\EPC;
use SimpleSoftwareIO\QrCode\Eye\CircleEye;
use SimpleSoftwareIO\QrCode\Eye\RoundedEye;

/**
 * Generates QR codes in svg, eps, png and webp. The setters return the generator, so they
 * chain, and generate() comes last. The helpers below are resolved by __call() from the
 * classes of the DataTypes namespace.
 *
 * @method \Illuminate\Support\HtmlString|string BTC(string $address, float|null $amount = null, array<string, string> $options = [])
 * @method \Illuminate\Support\HtmlString|string calendar(array<string, mixed> $event)
 * @method \Illuminate\Support\HtmlString|string EPC(array<string, mixed> $transfer)
 * @method \Illuminate\Support\HtmlString|string email(?string $to = null, ?string $subject = null, ?string $body = null)
 * @method \Illuminate\Support\HtmlString|string geo(float $latitude, float $longitude)
 * @method \Illuminate\Support\HtmlString|string phoneNumber(string $phoneNumber)
 * @method \Illuminate\Support\HtmlString|string SMS(string $phoneNumber, ?string $message = null)
 * @method \Illuminate\Support\HtmlString|string wiFi(array<string, mixed> $settings)
 */
class Generator
{
    /**
     * The format of the QrCode: svg, eps, png or webp.
     *
     * @var string
     */
    protected $format = 'svg';

    /**
     * The width and height of the QrCode, in pixels.
     *
     * @var int
     */
    protected $pixels = 100;

    /**
     * The margin around the QrCode, in modules.
     *
     * @var int
     */
    protected $margin = 0;

    /**
     * The error correction level, or null for the default: L, or H with a merged logo.
     * L restores 7% of the codewords, M 15%, Q 25% and H 30%.
     *
     * @var ErrorCorrectionLevel|null
     */
    protected $errorCorrection = null;

    /**
     * The character encoding of the text, ISO-8859-1 by default. Possible values are
     * ISO-8859-1, ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
     * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
     * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
     * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
     * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
     *
     * @var string
     */
    protected $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING;

    /**
     * The style of the modules: square, dot or round.
     *
     * @var string
     */
    protected $style = 'square';

    /**
     * The size of the dots or the roundness, more than 0 and up to 1. The square style
     * ignores it.
     *
     * @var float|null
     */
    protected $styleSize = null;

    /**
     * The style of the eyes: square, circle, rounded or pointy. Null draws them with the
     * module of the style.
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
     * The zlib compression level of a png image, from 0 to 9. Null keeps the usual level.
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
     * The colors of the eyes, by eye number: 0 top left, 1 top right, 2 bottom left.
     *
     * @var array<int, EyeFill>
     */
    protected $eyeColors = [];

    /**
     * The gradient of the modules, if any.
     *
     * @var Gradient|null
     */
    protected $gradient;

    /**
     * The content of the image merged over the QrCode, if any.
     *
     * @var string|null
     */
    protected $imageMerge = null;

    /**
     * The width of the merged image, as a share of the width of the QrCode.
     *
     * @var float
     */
    protected $imagePercentage = .2;

    /**
     * The margin, in modules, of the area cleared under a merged image. Null leaves the
     * modules under the image.
     *
     * @var int|null
     */
    private $logoClearance = null;

    /**
     * A module renderer set with customStyle(), over style().
     *
     * @var ModuleInterface|null
     */
    private $customModule = null;

    /**
     * An eye renderer set with customEye(), over eye().
     *
     * @var EyeInterface|null
     */
    private $customEye = null;

    /**
     * Resolves a helper, such as email() or wiFi(): creates the data type of the same name
     * and generates its QrCode.
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     * @return \Illuminate\Support\HtmlString|string
     * @throws BadMethodCallException when no data type has that name
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
     * Generates the QrCode: returns it, or writes it to $filename and returns null. With Laravel,
     * the QrCode comes as an HtmlString, so that Blade prints it as it is.
     *
     * @param string $text
     * @param string|null $filename
     * @return ($filename is null ? \Illuminate\Support\HtmlString|string : null)
     * @throws WriterException
     * @throws InvalidArgumentException
     * @throws RuntimeException when png is requested without imagick and with an option GD cannot render
     */
    public function generate(string $text, ?string $filename = null)
    {
        if ($this->format === 'webp' && ! function_exists('imagewebp')) {
            throw new RuntimeException('The webp format needs the GD extension with WebP support.');
        }

        $renderer = $this->usesGdForPng() ? $this->getGdRenderer() : $this->getRenderer();

        if ($this->clearsUnderLogo()) {
            $writer = new Writer($this->clearingUnderLogo($renderer));
        } else {
            $writer = $renderer instanceof ImageRenderer ? $this->getWriter($renderer) : new Writer($renderer);
        }

        // A merged logo hides part of the code: at level L, most codes no longer read.
        // Without an explicit level, it is raised to H. eps ignores the logo.
        $errorCorrection = $this->errorCorrection
            ?? ($this->imageMerge !== null && $this->format !== 'eps' ? ErrorCorrectionLevel::H() : null);

        try {
            $qrCode = $writer->writeString($text, $this->encoding, $errorCorrection);
        // bacon's Encoder throws it, but Writer::writeString() does not declare it.
        } catch (WriterException $e) { // @phpstan-ignore catch.neverThrown
            throw $e->getMessage() === 'Data too big' ? $this->tooLong($text, $errorCorrection, $e) : $e;
        }

        if ($this->rendersIntermediatePng()) {
            $qrCode = $this->finishRasterImage($qrCode);
        }

        if ($this->imageMerge !== null && $this->format === 'svg') {
            $qrCode = (new SvgImageMerge($qrCode, $this->imageMerge))->merge($this->imagePercentage);
        }

        if ($filename) {
            file_put_contents($filename, $qrCode);

            return null;
        }

        if (class_exists(\Illuminate\Support\HtmlString::class)) {
            return new \Illuminate\Support\HtmlString($qrCode);
        }

        return $qrCode;
    }

    /**
     * Merges an image, usually a logo, over the center of the QrCode.
     *
     * @param string $filepath Relative to the base path of the Laravel application unless $absolute; used as it is outside Laravel
     * @param float $percentage The width of the image, as a share of the width of the QrCode
     * @param bool $absolute Whether $filepath is used as it is
     * @return Generator
     * @throws InvalidArgumentException when the file cannot be read
     */
    public function merge(string $filepath, float $percentage = .2, bool $absolute = false): self
    {
        // base_path() joins the two with a separator: 'public/logo.png' works as well as
        // '/public/logo.png', where base_path().$filepath needed the leading slash.
        if (function_exists('base_path') && ! $absolute) {
            $filepath = base_path($filepath);
        }

        // A file that cannot be read gave a PHP warning, then failed later on false.
        $content = @file_get_contents($filepath);

        if ($content === false) {
            throw new InvalidArgumentException("The image to merge cannot be read: {$filepath}.");
        }

        $this->imageMerge = $content;
        $this->imagePercentage = $percentage;

        return $this;
    }

    /**
     * Merges an image given as a string, usually a logo, over the center of the QrCode.
     *
     * @param string $content The content of the image
     * @param float $percentage The width of the image, as a share of the width of the QrCode
     * @return Generator
     */
    public function mergeString(string $content, float $percentage = .2): self
    {
        $this->imageMerge = $content;
        $this->imagePercentage = $percentage;

        return $this;
    }

    /**
     * Sets the width and height of the QrCode.
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
     * @param string $format svg, eps, png or webp
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
     * Sets the color of the modules.
     *
     * @param int $red from 0 to 255
     * @param int $green from 0 to 255
     * @param int $blue from 0 to 255
     * @param int|null $alpha from 0, transparent, to 100, opaque
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
     * @param int $red from 0 to 255
     * @param int $green from 0 to 255
     * @param int $blue from 0 to 255
     * @param int|null $alpha from 0, transparent, to 100, opaque
     * @return Generator
     */
    public function backgroundColor(int $red, int $green, int $blue, ?int $alpha = null): self
    {
        $this->backgroundColor = $this->createColor($red, $green, $blue, $alpha);

        return $this;
    }

    /**
     * Sets the color of the modules from a hex color.
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
     * Sets the colors of an eye. Despite their names, the first color goes to the ring and
     * the second to the center, as bacon's EyeFill receives them.
     *
     * @param int $eyeNumber 0 top left, 1 top right, 2 bottom left
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
     * Sets the colors of an eye from hex colors, in the same order as eyeColor().
     *
     * @param int $eyeNumber 0 top left, 1 top right, 2 bottom left
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

    /**
     * Sets a gradient on the modules.
     *
     * @param int $startRed from 0 to 255
     * @param int $startGreen from 0 to 255
     * @param int $startBlue from 0 to 255
     * @param int $endRed from 0 to 255
     * @param int $endGreen from 0 to 255
     * @param int $endBlue from 0 to 255
     * @param string $type vertical, horizontal, diagonal, inverse_diagonal or radial
     * @return Generator
     */
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
        $this->customEye = null;

        return $this;
    }

    /**
     * Draws the modules with your own bacon/bacon-qr-code module, in place of style().
     *
     * @param ModuleInterface $module
     * @return Generator
     */
    public function customStyle(ModuleInterface $module): self
    {
        $this->customModule = $module;
        $this->style = 'square';
        $this->styleSize = null;

        return $this;
    }

    /**
     * Draws the eyes with your own bacon/bacon-qr-code eye, in place of eye().
     * externalEye() and internalEye() still apply over it.
     *
     * @param EyeInterface $eye
     * @return Generator
     */
    public function customEye(EyeInterface $eye): self
    {
        $this->customEye = $eye;
        $this->eyeStyle = null;

        return $this;
    }

    /**
     * Clears the modules under a merged image instead of covering them, leaving the image
     * on the background. The eyes, their separators and the format information stay.
     *
     * @param int $margin the empty band kept around the image, in modules
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function clearUnderLogo(int $margin = 0): self
    {
        if ($margin < 0) {
            throw new InvalidArgumentException("\$margin must be 0 or more. {$margin} is not valid.");
        }

        $this->logoClearance = $margin;

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
     * Sets the zlib compression level of a png image, merged logo included. Png is
     * lossless: the level only trades the file size against the time to write it.
     *
     * @param int $level from 0 (fastest, largest file) to 9 (smallest file)
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
     * Sets the style of the modules.
     *
     * @param string $style square, dot or round
     * @param float $size The size of the dots or the roundness, more than 0 and up to 1
     * @return Generator
     * @throws InvalidArgumentException
     */
    public function style(string $style, float $size = 0.5): self
    {
        if (! in_array($style, ['square', 'dot', 'round'])) {
            throw new InvalidArgumentException("\$style must be square, dot, or round. {$style} is not a valid.");
        }

        // The square style ignores the size; the dots and the roundness need more than 0, up to 1.
        if ($size < 0 || $size > 1 || ($size == 0 && $style !== 'square')) {
            throw new InvalidArgumentException("\$size must be between 0 and 1, and more than 0 for dot and round.  {$size} is not valid.");
        }

        $this->style = $style;
        $this->styleSize = $size;
        $this->customModule = null;

        return $this;
    }

    /**
     * Sets the character encoding of the text. Possible values are
     * ISO-8859-1, ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
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
     * Sets the error correction level. L restores 7% of the codewords, M 15%, Q 25% and H 30%:
     * the higher the level, the larger the QrCode for the same text.
     *
     * @param string $errorCorrection L, M, Q or H
     * @return Generator
     */
    public function errorCorrection(string $errorCorrection): self
    {
        $errorCorrection = strtoupper($errorCorrection);
        $this->errorCorrection = ErrorCorrectionLevel::$errorCorrection();

        return $this;
    }

    /**
     * Sets the margin around the QrCode.
     *
     * @param int $margin in modules
     * @return Generator
     */
    public function margin(int $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * Creates the bacon writer of a renderer.
     *
     * @param ImageRenderer $renderer
     * @return Writer
     */
    public function getWriter(ImageRenderer $renderer): Writer
    {
        return new Writer($renderer);
    }

    /**
     * Creates the bacon renderer for svg, eps, and png or webp with imagick.
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
     * Creates the bacon renderer style: size, margin, module, eye and colors.
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

        if ($this->customModule !== null) {
            $unsupported[] = 'customStyle()';
        }

        if ($this->customEye !== null) {
            $unsupported[] = 'customEye()';
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

        return new GDLibRenderer($this->pixels, $this->margin, 'png', $this->pngLevel(9, 1), $this->getFill());
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
     * Creates the bacon back end of the format: imagick for png and webp, eps or svg.
     *
     * @return ImageBackEndInterface
     */
    public function getFormatter(): ImageBackEndInterface
    {
        if (in_array($this->format, ['png', 'webp'])) {
            // ImageMagick reads the png "quality" as the zlib level (tens) and the filter (units).
            // Antialiasing only blurs the edges of square modules and eyes when a module does not
            // fall on whole pixels, as in small images. bacon 3.0 ignores the third argument.
            return new ImagickImageBackEnd('png', $this->pngLevel(100, 10), ! $this->drawsOnlySquares());
        }

        if ($this->format === 'eps') {
            return new EpsImageBackEnd;
        }

        return new SvgImageBackEnd;
    }

    /**
     * Returns the module of the style, or the one set with customStyle().
     *
     * @return ModuleInterface
     */
    public function getModule(): ModuleInterface
    {
        if ($this->customModule !== null) {
            return $this->customModule;
        }

        if ($this->style === 'dot') {
            // style() always sets the size; a subclass that sets the style alone gets its default.
            return new DotsModule($this->styleSize ?? 0.5);
        }

        if ($this->style === 'round') {
            return new RoundnessModule($this->styleSize ?? 0.5);
        }

        return SquareModule::instance();
    }

    /**
     * Returns the eye of the eye styles, or the one set with customEye().
     *
     * @return EyeInterface
     */
    public function getEye(): EyeInterface
    {
        $eye = $this->customEye ?? $this->createEye($this->eyeStyle) ?? new ModuleEye($this->getModule());

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
    private function createEye(?string $style): ?EyeInterface
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
    private function validEyeStyle(string $style): string
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
    private function hexToColor(string $hex): ColorInterface
    {
        $digits = str_starts_with($hex, '#') ? substr($hex, 1) : $hex;

        if (! preg_match('/^(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $digits)) {
            throw new InvalidArgumentException("{$hex} is not a valid hex color. Use #rgb, #rgba, #rrggbb or #rrggbbaa.");
        }

        if (strlen($digits) <= 4) {
            $digits = implode('', array_map(fn (string $digit): string => $digit.$digit, str_split($digits)));
        }

        [$red, $green, $blue] = array_map(fn (string $pair): int => (int) hexdec($pair), str_split(substr($digits, 0, 6), 2));
        // The alpha byte (00 to ff) becomes the 0 to 100 alpha of the other color methods.
        $alpha = strlen($digits) === 8 ? (int) round(hexdec(substr($digits, 6, 2)) * 100 / 255) : null;

        return $this->createColor($red, $green, $blue, $alpha);
    }

    /**
     * Whether the renderer's png is only an intermediate image, decoded by GD to merge
     * a logo or to write a webp.
     *
     * @return bool
     */
    private function rendersIntermediatePng(): bool
    {
        return $this->format === 'webp' || ($this->format === 'png' && $this->imageMerge !== null);
    }

    /**
     * The compression the renderer applies to its png, in the renderer's own scale.
     * An intermediate png is decoded right away: it is written as fast as possible.
     *
     * @param int $usual The renderer's usual value
     * @param int $perLevel The renderer's value for one zlib level
     * @return int
     */
    private function pngLevel(int $usual, int $perLevel): int
    {
        if ($this->rendersIntermediatePng()) {
            return $perLevel;
        }

        return $this->compression === null ? $usual : $this->compression * $perLevel;
    }

    /**
     * Merges the logo and writes the final png or webp from the renderer's png.
     *
     * @param string $png
     * @return string
     */
    private function finishRasterImage(string $png): string
    {
        $image = $this->imageMerge === null
            ? imagecreatefromstring($png)
            : (new ImageMerge(new Image($png), new Image($this->imageMerge)))->mergeImage($this->imagePercentage);

        if ($image === false) {
            throw new RuntimeException('GD cannot read the png of the renderer.');
        }

        ob_start();

        if ($this->format === 'png') {
            // Without compression(), the level ImageMerge always used.
            imagepng($image, null, $this->compression ?? -1);

            return (string) ob_get_clean();
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagewebp($image, null, $this->quality ?? (defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : 101));

        return (string) ob_get_clean();
    }

    /**
     * Says why bacon's "Data too big" happened: the content goes beyond the largest QR code.
     *
     * @param string $text
     * @param ErrorCorrectionLevel|null $errorCorrection
     * @param WriterException $previous
     * @return WriterException
     */
    private function tooLong(string $text, ?ErrorCorrectionLevel $errorCorrection, WriterException $previous): WriterException
    {
        $level = ($errorCorrection ?? ErrorCorrectionLevel::L())->name();
        $logo = $this->errorCorrection === null && $level === 'H' ? ', the level a merged logo uses unless you set one' : '';

        return new WriterException(
            "The content is too long for a QR code: ".strlen($text)." bytes at error correction {$level}{$logo}. "
            .'The largest QR code holds 2,953 bytes of text at L, 2,331 at M, 1,663 at Q and 1,273 at H. '
            .'Shorten the content, for example with a link to it, or lower the error correction.',
            0,
            $previous
        );
    }

    /**
     * Whether the modules and the eyes are all squares, which antialiasing can only blur.
     * The module and the eye are checked themselves, as a subclass may return its own.
     *
     * @return bool
     */
    private function drawsOnlySquares(): bool
    {
        if (! $this->getModule() instanceof SquareModule) {
            return false;
        }

        $squares = [SquareEye::instance(), new ModuleEye(SquareModule::instance())];
        $eyes = $squares;

        foreach ($squares as $external) {
            foreach ($squares as $internal) {
                $eyes[] = new CompositeEye($external, $internal);
            }
        }

        // Loose comparison: same class and same properties.
        return in_array($this->getEye(), $eyes);
    }

    /**
     * Whether the modules under a merged image are cleared. eps ignores the image.
     *
     * @return bool
     */
    private function clearsUnderLogo(): bool
    {
        return $this->logoClearance !== null && $this->imageMerge !== null && $this->format !== 'eps';
    }

    /**
     * Wraps a renderer so that it clears the modules under the merged image first.
     *
     * @param RendererInterface $renderer
     * @return RendererInterface
     */
    private function clearingUnderLogo(RendererInterface $renderer): RendererInterface
    {
        $clear = fn (EncodedQrCode $qrCode) => $this->clearModulesUnderLogo($qrCode);

        return new class($renderer, $clear) implements RendererInterface
        {
            public function __construct(private RendererInterface $renderer, private \Closure $clear)
            {
            }

            public function render(EncodedQrCode $qrCode): string
            {
                ($this->clear)($qrCode);

                return $this->renderer->render($qrCode);
            }
        };
    }

    /**
     * Clears the modules that the merged image overlaps, even partly, and those of the margin
     * around it, keeping the eyes, their separators, and the format and version information.
     *
     * A subclass that changes the margin through getRendererStyle() is not followed here,
     * nor is getWriter() called on this path: both are older than this option.
     *
     * @param EncodedQrCode $qrCode
     * @return void
     */
    private function clearModulesUnderLogo(EncodedQrCode $qrCode): void
    {
        $matrix = $qrCode->getMatrix();
        $size = $matrix->getWidth();
        [$imageLeft, $imageTop, $imageRight, $imageBottom] = $this->logoArea($size);

        // A module from x to x + 1 overlaps the area when x + 1 > start and x < end.
        $range = function (float $start, float $end) use ($size): array {
            $start -= $this->logoClearance;
            $end += $this->logoClearance;

            return [max(0, (int) floor($start + 1e-9)), min($size - 1, (int) ceil($end - 1e-9) - 1)];
        };

        [$left, $right] = $range($imageLeft, $imageRight);
        [$top, $bottom] = $range($imageTop, $imageBottom);
        $versionInformation = $qrCode->getVersion()->getVersionNumber() >= 7;

        for ($y = $top; $y <= $bottom; $y++) {
            for ($x = $left; $x <= $right; $x++) {
                if (! $this->isKeptUnderLogo($x, $y, $size, $versionInformation)) {
                    $matrix->set($x, $y, 0);
                }
            }
        }
    }

    /**
     * Whether a module belongs to an eye, its separator, or the format or version information,
     * which a reader needs before it can read anything else.
     *
     * @param int $x
     * @param int $y
     * @param int $size
     * @param bool $versionInformation
     * @return bool
     */
    private function isKeptUnderLogo(int $x, int $y, int $size, bool $versionInformation): bool
    {
        if (($y < 9 && ($x < 9 || $x >= $size - 8)) || ($x < 9 && $y >= $size - 8)) {
            return true;
        }

        return $versionInformation
            && (($y < 6 && $x >= $size - 11 && $x < $size - 8) || ($x < 6 && $y >= $size - 11 && $y < $size - 8));
    }

    /**
     * Where the merged image lies, in modules from the top left corner of the matrix:
     * [left, top, right, bottom]. It follows SvgImageMerge in svg, and ImageMerge, which
     * rounds to whole pixels, in png and webp.
     *
     * @param int $size The width of the matrix, in modules
     * @return float[]
     */
    private function logoArea(int $size): array
    {
        $pixels = $this->pixels;
        $module = $pixels / ($size + 2 * $this->margin);

        if ($this->format === 'svg') {
            $width = $pixels * $this->imagePercentage;
            $height = $width / $this->logoRatio();
            $left = ($pixels - $width) / 2;
            $top = ($pixels - $height) / 2;
        } else {
            $width = intval($pixels * $this->imagePercentage);
            $height = intval($width / round($this->logoRatio(), 2));
            $left = intval($pixels / 2 - $width / 2);
            $top = intval($pixels / 2 - $height / 2);
        }

        return array_map(
            fn ($pixel) => $pixel / $module - $this->margin,
            [$left, $top, $left + $width, $top + $height]
        );
    }

    /**
     * The width to height ratio of the merged image: a raster image or, in svg, an svg image.
     *
     * @return float
     * @throws InvalidArgumentException when the merged content is not an image
     * @throws LogicException when there is no merged image, which clearsUnderLogo() rules out
     */
    private function logoRatio(): float
    {
        $image = $this->imageMerge;

        if ($image === null) {
            throw new LogicException('There is no merged image to clear the modules under.');
        }

        $info = @getimagesizefromstring($image);

        if ($info && $info[0] && $info[1]) {
            return $info[0] / $info[1];
        }

        if ($this->format !== 'svg') {
            throw new InvalidArgumentException("The image merged with a {$this->format} QrCode must be a raster image PHP recognizes: png, jpeg, gif, webp...");
        }

        return (new SvgImageMerge('', $image))->imageRatio();
    }

    /**
     * Creates the bacon fill: background, modules or gradient, and the colors of the eyes.
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
     * Creates a bacon color, with an alpha channel when one is given.
     *
     * @param int $red from 0 to 255
     * @param int $green from 0 to 255
     * @param int $blue from 0 to 255
     * @param int|null $alpha from 0, transparent, to 100, opaque
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
     * Creates the data type of a helper, from its name.
     *
     * @param string $method
     * @return DataTypeInterface
     * @throws BadMethodCallException when no data type has that name
     */
    protected function createClass(string $method): DataTypeInterface
    {
        $class = $this->formatClass($method);

        if (! class_exists($class) || ! is_subclass_of($class, DataTypeInterface::class)) {
            throw new BadMethodCallException('Call to undefined method '.static::class."::{$method}().");
        }

        return new $class();
    }

    /**
     * The class of the data type of a helper: email() gives DataTypes\Email.
     *
     * @param string $method
     * @return string
     */
    protected function formatClass(string $method): string
    {
        $method = ucfirst($method);

        $class = "SimpleSoftwareIO\QrCode\DataTypes\\".$method;

        return $class;
    }
}
