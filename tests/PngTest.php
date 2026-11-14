<?php

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\Generator;
use SimpleSoftwareIO\QrCode\Image;
use SimpleSoftwareIO\QrCode\ImageMerge;
use Zxing\QrReader;

/**
 * A generator that behaves as if imagick were not installed, to test the GD path everywhere.
 */
class GeneratorWithoutImagick extends Generator
{
    protected function imagickIsAvailable(): bool
    {
        return false;
    }
}

class PngTest extends TestCase
{
    public static function renderers(): array
    {
        return [
            'gd' => [GeneratorWithoutImagick::class],
            'imagick' => [Generator::class],
        ];
    }

    private function generator(string $class): Generator
    {
        if ($class === Generator::class && ! extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not installed.');
        }

        return new $class;
    }

    private function decode(string $png): string
    {
        // Always decode through GD. The decoder's imagick path sets the gray colorspace
        // without converting the pixels (only the red channel survives) and exports them
        // from (1, 1): a dark blue or red module then reads as light, unlike on a phone.
        return (string) (new QrReader($png, QrReader::SOURCE_TYPE_BLOB, false))->text();
    }

    private function pixel(string $png, int $x, int $y): array
    {
        $image = imagecreatefromstring($png);

        return imagecolorsforindex($image, imagecolorat($image, $x, $y));
    }

    #[DataProvider('renderers')]
    public function test_it_generates_a_png_that_decodes(string $class)
    {
        $png = (string) $this->generator($class)->format('png')->size(200)->margin(1)->encoding('UTF-8')->generate('https://example.com/é');

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        $this->assertSame([200, 200], array_slice(getimagesizefromstring($png), 0, 2));
        $this->assertSame('https://example.com/é', $this->decode($png));
    }

    #[DataProvider('renderers')]
    public function test_colors_and_a_merged_logo_still_decode(string $class)
    {
        $png = (string) $this->generator($class)
            ->format('png')
            ->size(300)
            ->margin(1)
            ->color(20, 40, 120)
            ->eyeColor(0, 200, 0, 0, 0, 0, 200)
            ->errorCorrection('H')
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)
            ->generate('merged logo');

        $this->assertSame('merged logo', $this->decode($png));
    }

    #[DataProvider('renderers')]
    public function test_a_merged_logo_decodes_without_setting_the_error_correction(string $class)
    {
        foreach (['https://example.com', 'Hello world, this is a QR code with a logo.'] as $text) {
            foreach (['png', 'webp'] as $format) {
                $image = (string) $this->generator($class)
                    ->format($format)
                    ->size(400)
                    ->margin(2)
                    ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .3, true)
                    ->generate($text);

                $this->assertSame($text, $this->decode($image), "{$format}: {$text}");
            }
        }
    }

    #[DataProvider('renderers')]
    public function test_a_transparent_background_stays_transparent_with_a_merged_logo(string $class)
    {
        $png = (string) $this->generator($class)
            ->format('png')
            ->size(200)
            ->margin(1)
            ->backgroundColor(255, 255, 255, 0)
            ->errorCorrection('H')
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)
            ->generate('transparent');

        // The corner sits in the margin: background only.
        $this->assertSame(127, $this->pixel($png, 1, 1)['alpha']);
    }

    #[DataProvider('renderers')]
    public function test_an_opaque_background_stays_opaque_with_a_merged_logo(string $class)
    {
        $png = (string) $this->generator($class)
            ->format('png')
            ->size(200)
            ->margin(1)
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)
            ->generate('opaque');

        $corner = $this->pixel($png, 1, 1);
        $this->assertSame([255, 255, 255, 0], [$corner['red'], $corner['green'], $corner['blue'], $corner['alpha']]);
    }

    public function test_gd_is_used_for_png_only_when_imagick_is_missing()
    {
        $this->assertTrue((new GeneratorWithoutImagick)->format('png')->usesGdForPng());
        $this->assertFalse((new GeneratorWithoutImagick)->format('svg')->usesGdForPng());
        $this->assertFalse((new GeneratorWithoutImagick)->format('eps')->usesGdForPng());
        $this->assertSame(! extension_loaded('imagick'), (new Generator)->format('png')->usesGdForPng());
        $this->assertInstanceOf(GDLibRenderer::class, (new GeneratorWithoutImagick)->format('png')->getGdRenderer());
    }

    public static function optionsGdCannotRender(): array
    {
        return [
            'dot style' => [fn (Generator $g) => $g->style('dot'), "style('dot')"],
            'round style' => [fn (Generator $g) => $g->style('round'), "style('round')"],
            'circle eye' => [fn (Generator $g) => $g->eye('circle'), "eye('circle')"],
            'rounded eye' => [fn (Generator $g) => $g->eye('rounded'), "eye('rounded')"],
            'circle external eye' => [fn (Generator $g) => $g->externalEye('circle'), "externalEye('circle')"],
            'pointy internal eye' => [fn (Generator $g) => $g->internalEye('pointy'), "internalEye('pointy')"],
            'gradient' => [fn (Generator $g) => $g->gradient(0, 0, 0, 255, 0, 0, 'vertical'), 'gradient()'],
            'custom style' => [fn (Generator $g) => $g->customStyle(new DotsModule(0.8)), 'customStyle()'],
            'custom eye' => [fn (Generator $g) => $g->customEye(SquareEye::instance()), 'customEye()'],
        ];
    }

    #[DataProvider('optionsGdCannotRender')]
    public function test_an_option_gd_cannot_render_throws_a_clear_error(Closure $option, string $named)
    {
        $generator = $option((new GeneratorWithoutImagick)->format('png'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The png format needs the imagick extension for {$named}");

        $generator->generate('nope');
    }

    public function test_square_eyes_are_allowed_without_imagick()
    {
        $png = (string) (new GeneratorWithoutImagick)->format('png')->size(200)->margin(1)->eye('square')->style('square')->generate('square');

        $this->assertSame('square', $this->decode($png));
    }

    public static function optionsOnlyImagickRenders(): array
    {
        return [
            // Upstream #211: a gradient with a margin cut the eyes off.
            'gradient and margin' => [fn (Generator $g) => $g->margin(2)->gradient(0, 0, 0, 0, 0, 140, 'horizontal')],
            'dot style, square eyes' => [fn (Generator $g) => $g->style('dot', 0.9)->eye('square')],
            'dot style, circle eyes' => [fn (Generator $g) => $g->style('dot')->eye('circle')],
            // Upstream #290 was about eps; png goes through another backend.
            'round style' => [fn (Generator $g) => $g->style('round', 0.9)],
            'rounded eyes' => [fn (Generator $g) => $g->eye('rounded')],
            'pointy eyes' => [fn (Generator $g) => $g->eye('pointy')],
            'round external eye, square internal eye' => [fn (Generator $g) => $g->externalEye('circle')->internalEye('square')],
            'rounded external eye, circle internal eye' => [fn (Generator $g) => $g->externalEye('rounded')->internalEye('circle')],
            'webp from imagick' => [fn (Generator $g) => $g->format('webp')->eye('rounded')],
            'custom style and eye' => [fn (Generator $g) => $g->customStyle(new DotsModule(0.8))->customEye(SquareEye::instance())],
            'round style on a cleared area' => [fn (Generator $g) => $g->style('round', 0.7)->errorCorrection('H')->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)->clearUnderLogo()],
        ];
    }

    #[DataProvider('optionsOnlyImagickRenders')]
    public function test_options_only_imagick_renders_still_decode(Closure $option)
    {
        $generator = $option($this->generator(Generator::class)->format('png')->size(300)->margin(1));

        $this->assertSame('imagick only', $this->decode((string) $generator->generate('imagick only')));
    }

    #[DataProvider('renderers')]
    public function test_it_generates_a_lossless_webp_that_decodes(string $class)
    {
        $webp = (string) $this->generator($class)->format('webp')->size(200)->margin(1)->generate('https://example.com/webp');

        $this->assertSame('RIFF', substr($webp, 0, 4));
        $this->assertSame('WEBPVP8L', substr($webp, 8, 8), 'Without quality(), webp is lossless (VP8L).');
        $this->assertSame([200, 200], array_slice(getimagesizefromstring($webp), 0, 2));
        $this->assertSame('https://example.com/webp', $this->decode($webp));
    }

    #[DataProvider('renderers')]
    public function test_quality_makes_a_lossy_webp_that_decodes(string $class)
    {
        $webp = (string) $this->generator($class)->format('webp')->quality(70)->size(200)->margin(1)->generate('lossy');

        $this->assertSame('WEBPVP8 ', substr($webp, 8, 8));
        $this->assertSame('lossy', $this->decode($webp));
    }

    #[DataProvider('renderers')]
    public function test_a_merged_logo_and_a_transparent_background_work_in_webp(string $class)
    {
        $generator = fn () => $this->generator($class)
            ->format('webp')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true);

        $this->assertSame('merged webp', $this->decode((string) $generator()->generate('merged webp')));

        // The decoder reads transparent pixels as black: check the alpha channel only.
        $transparent = (string) $generator()->backgroundColor(255, 255, 255, 0)->generate('transparent webp');
        $this->assertSame(127, $this->pixel($transparent, 1, 1)['alpha']);
    }

    #[DataProvider('renderers')]
    public function test_compression_changes_the_png_size_but_not_the_image(string $class)
    {
        $generator = fn () => $this->generator($class)->format('png')->size(300)->margin(1);
        $smallest = (string) $generator()->generate('compression');
        $none = (string) $generator()->compression(0)->generate('compression');

        $this->assertGreaterThan(strlen($smallest), strlen($none));
        $this->assertSame('compression', $this->decode($none));
        $this->assertSame($this->pixel($smallest, 150, 150), $this->pixel($none, 150, 150));
    }

    #[DataProvider('renderers')]
    public function test_compression_applies_with_a_merged_logo(string $class)
    {
        $generator = fn () => $this->generator($class)
            ->format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true);
        $usual = (string) $generator()->generate('merged');
        $none = (string) $generator()->compression(0)->generate('merged');

        $this->assertGreaterThan(strlen($usual) * 10, strlen($none));
        $this->assertSame('merged', $this->decode($none));
        $this->assertSame($this->pixel($usual, 150, 150), $this->pixel($none, 150, 150));
    }

    public function test_a_merged_logo_without_compression_gives_the_same_png_as_before()
    {
        $logo = __DIR__.'/Images/simplesoftware-icon-grey-blue.png';
        $generator = (new GeneratorWithoutImagick)->format('png')->size(300)->margin(1)->errorCorrection('H');
        $qrCode = (string) (clone $generator)->generate('same as 4.3');

        // What 4.3 did: ImageMerge::merge() over the renderer's png.
        $before = (new ImageMerge(new Image($qrCode), new Image(file_get_contents($logo))))->merge(.2);

        $this->assertSame($before, (string) (clone $generator)->merge($logo, .2, true)->generate('same as 4.3'));
    }

    #[DataProvider('renderers')]
    public function test_small_square_codes_have_sharp_edges(string $class)
    {
        // Upstream #209 and #263: at 90 pixels a module is not a whole number of pixels,
        // and imagick blurred its edges with gray pixels.
        $png = (string) $this->generator($class)->format('png')->size(90)->margin(1)->generate('https://example.com/sharp');
        $image = imagecreatefromstring($png);
        $colors = [];

        for ($y = 0; $y < 90; $y++) {
            for ($x = 0; $x < 90; $x++) {
                $colors[imagecolorat($image, $x, $y)] = true;
            }
        }

        $this->assertCount(2, $colors);
        $this->assertSame('https://example.com/sharp', $this->decode($png));
    }

    private function transparentLogo(int $width = 60, int $height = 60): string
    {
        $logo = imagecreatetruecolor($width, $height);
        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        imagefill($logo, 0, 0, imagecolorallocatealpha($logo, 0, 0, 0, 127));

        ob_start();
        imagepng($logo);

        return ob_get_clean();
    }

    private function darkPixels(string $image, int $from, int $to, ?int $top = null, ?int $bottom = null): int
    {
        $image = imagecreatefromstring($image);
        $dark = 0;

        for ($y = $top ?? $from; $y < ($bottom ?? $to); $y++) {
            for ($x = $from; $x < $to; $x++) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $dark += $color['red'] + $color['green'] + $color['blue'] < 384 ? 1 : 0;
            }
        }

        return $dark;
    }

    #[DataProvider('renderers')]
    public function test_clear_under_logo_leaves_the_logo_on_the_background(string $class)
    {
        $text = 'https://example.com/logo';
        $generator = fn (string $format = 'png') => $this->generator($class)->format($format)->size(300)->margin(1)->mergeString($this->transparentLogo(), .3);

        // The logo is transparent: what lies under it shows. It is 90 pixels wide, from 105 to 195.
        $this->assertGreaterThan(0, $this->darkPixels((string) $generator()->generate($text), 105, 195));

        foreach (['png', 'webp'] as $format) {
            $cleared = (string) $generator($format)->clearUnderLogo()->generate($text);
            $this->assertSame(0, $this->darkPixels($cleared, 105, 195), $format);
            $this->assertSame($text, $this->decode($cleared), $format);
        }

        // With a margin of one module, nothing is drawn within one module of the logo either.
        // A smaller logo, 60 pixels wide from 120 to 180: the cleared area grows with the margin.
        $module = 300 / (Encoder::encode($text, ErrorCorrectionLevel::H())->getMatrix()->getWidth() + 2);
        $band = (string) $this->generator($class)->format('png')->size(300)->margin(1)->mergeString($this->transparentLogo(), .2)->clearUnderLogo(1)->generate($text);
        $this->assertSame(0, $this->darkPixels($band, (int) ceil(120 - $module) + 1, (int) floor(180 + $module) - 1));
        $this->assertSame($text, $this->decode($band));
    }

    #[DataProvider('renderers')]
    public function test_the_cleared_area_follows_the_pixels_of_a_tall_logo(string $class)
    {
        // A 1:3 logo: ImageMerge rounds its ratio to 0.33 and draws it 909 pixels high, from 45
        // to 954, not 900. Version 1, 47.6 pixels a module: from x = 430 to 615, no module is
        // kept, but row 0 starts above the logo and covers its top 3 pixels.
        $generator = fn () => $this->generator($class)->format('png')->size(1000)->mergeString($this->transparentLogo(100, 300), .3);

        $this->assertGreaterThan(0, $this->darkPixels((string) $generator()->generate('a'), 430, 615, 45, 50));
        $this->assertSame(0, $this->darkPixels((string) $generator()->clearUnderLogo()->generate('a'), 430, 615, 45, 955));
    }

    #[DataProvider('renderers')]
    public function test_codes_with_a_logo_on_a_cleared_area_decode(string $class)
    {
        // As the documentation advises: on a short content, a logo of 20% at most.
        foreach (['https://example.com' => [.2], 'Hello world, this is a QR code with a logo.' => [.2, .25]] as $text => $percentages) {
            foreach ($percentages as $percentage) {
                $png = (string) $this->generator($class)
                    ->format('png')
                    ->size(400)
                    ->margin(2)
                    ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', $percentage, true)
                    ->clearUnderLogo()
                    ->generate($text);

                $this->assertSame($text, $this->decode($png), "{$percentage}: {$text}");
            }
        }
    }

    public function test_clear_under_logo_keeps_the_eyes_and_the_format_and_version_information()
    {
        // A logo as wide as the code clears everything else.
        $generator = (new Generator)->mergeString($this->transparentLogo(), 1)->clearUnderLogo();

        foreach (['version 1' => 'a', 'version 7 and more' => str_repeat('a', 70)] as $case => $text) {
            $qrCode = Encoder::encode($text, ErrorCorrectionLevel::H());
            $before = clone $qrCode->getMatrix();
            (fn ($qrCode) => $this->clearModulesUnderLogo($qrCode))->call($generator, $qrCode);
            $after = $qrCode->getMatrix();
            $size = $after->getWidth();
            $kept = ['format' => []];

            // The eyes and their separators, with the format information along them.
            foreach ([[0, 0], [$size - 8, 0], [0, $size - 8]] as [$left, $top]) {
                for ($y = 0; $y < 8; $y++) {
                    for ($x = 0; $x < 8; $x++) {
                        $kept["{$left},{$top}"][] = [$left + $x, $top + $y];
                    }
                }
            }

            for ($i = 0; $i < 9; $i++) {
                array_push($kept['format'], [8, $i], [$i, 8]);
            }

            for ($i = 0; $i < 8; $i++) {
                array_push($kept['format'], [$size - 1 - $i, 8], [8, $size - 1 - $i]);
            }

            if ($qrCode->getVersion()->getVersionNumber() >= 7) {
                for ($i = 0; $i < 6; $i++) {
                    for ($j = $size - 11; $j < $size - 8; $j++) {
                        array_push($kept['format'], [$j, $i], [$i, $j]);
                    }
                }
            }

            $cells = [];
            foreach (array_merge(...array_values($kept)) as [$x, $y]) {
                $this->assertSame($before->get($x, $y), $after->get($x, $y), "{$case}: ({$x}, {$y}) is kept");
                $cells["{$x},{$y}"] = true;
            }

            // Everything else goes, the timing patterns included.
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    if (! isset($cells["{$x},{$y}"])) {
                        $this->assertSame(0, $after->get($x, $y), "{$case}: ({$x}, {$y}) is cleared");
                    }
                }
            }
        }
    }

    public function test_webp_without_imagick_has_the_limits_of_gd()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The webp format needs the imagick extension for style('dot')");

        (new GeneratorWithoutImagick)->format('webp')->style('dot')->generate('nope');
    }
}
