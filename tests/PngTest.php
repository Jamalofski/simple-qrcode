<?php

use BaconQrCode\Renderer\GDLibRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\Generator;
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
            'gradient' => [fn (Generator $g) => $g->gradient(0, 0, 0, 255, 0, 0, 'vertical'), 'gradient()'],
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
        ];
    }

    #[DataProvider('optionsOnlyImagickRenders')]
    public function test_options_only_imagick_renders_still_decode(Closure $option)
    {
        $generator = $option($this->generator(Generator::class)->format('png')->size(300)->margin(1));

        $this->assertSame('imagick only', $this->decode((string) $generator->generate('imagick only')));
    }
}
