<?php

use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\Generator;
use SimpleSoftwareIO\QrCode\SvgImageMerge;

class SvgImageMergeTest extends TestCase
{
    private const SVG = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="300" height="300" viewBox="0 0 300 300"><rect width="300" height="300" fill="#fff"/></svg>'."\n";

    private function image(string $svg): array
    {
        $this->assertSame(1, preg_match('/<image ([^>]*)\/><\/svg>/', $svg, $match), 'The image is the last element of the svg.');
        preg_match_all('/([\w:]+)="([^"]*)"/', $match[1], $attributes);

        return array_combine($attributes[1], $attributes[2]);
    }

    public function test_a_png_logo_is_centered_with_its_ratio()
    {
        // Despite its name, 200x300.png is 536 pixels wide and 354 high: 60 wide gives 39.627 high.
        $png = file_get_contents(__DIR__.'/Images/200x300.png');
        $image = $this->image((new SvgImageMerge(self::SVG, $png))->merge(.2));

        $this->assertSame(['120', '130.187', '60', '39.627'], [$image['x'], $image['y'], $image['width'], $image['height']]);
        $this->assertSame('data:image/png;base64,'.base64_encode($png), $image['xlink:href']);
        $this->assertSame('http://www.w3.org/1999/xlink', $image['xmlns:xlink']);
    }

    public function test_an_svg_logo_keeps_the_ratio_of_its_view_box()
    {
        $logo = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100"/></svg>';
        $image = $this->image((new SvgImageMerge(self::SVG, $logo))->merge(.3));

        $this->assertSame(['105', '127.5', '90', '45'], [$image['x'], $image['y'], $image['width'], $image['height']]);
        $this->assertSame('data:image/svg+xml;base64,'.base64_encode($logo), $image['xlink:href']);
    }

    public function test_an_svg_logo_without_view_box_uses_its_width_and_height()
    {
        $logo = '<svg xmlns="http://www.w3.org/2000/svg" width="50px" height="100"><rect width="50" height="100"/></svg>';
        $image = $this->image((new SvgImageMerge(self::SVG, $logo))->merge(.2));

        $this->assertSame(['60', '120'], [$image['width'], $image['height']]);
    }

    public function test_an_svg_logo_with_a_byte_order_mark_is_recognized()
    {
        $logo = "\xEF\xBB\xBF".'<?xml version="1.0" encoding="UTF-8"?>'."\n".'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>';
        $image = $this->image((new SvgImageMerge(self::SVG, $logo))->merge(.2));

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $image['xlink:href']);
    }

    public function test_an_unknown_image_throws_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('png, jpeg, gif, webp or svg');

        (new SvgImageMerge(self::SVG, 'not an image'))->merge(.2);
    }

    public function test_a_percentage_over_1_throws_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);

        (new SvgImageMerge(self::SVG, file_get_contents(__DIR__.'/Images/200x300.png')))->merge(1.5);
    }

    public function test_the_generator_merges_a_logo_in_svg()
    {
        $svg = (string) (new Generator)
            ->size(300)
            ->errorCorrection('H')
            ->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)
            ->generate('svg logo');

        $image = $this->image($svg);
        $this->assertSame('60', $image['width']);
        $this->assertStringStartsWith('data:image/png;base64,', $image['xlink:href']);
        $this->assertNotFalse(simplexml_load_string($svg), 'The svg stays valid XML.');
    }

    public function test_eps_ignores_a_merged_logo_as_before()
    {
        $eps = (string) (new Generator)->format('eps')->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)->generate('eps');

        $this->assertSame((string) (new Generator)->format('eps')->generate('eps'), $eps);
    }
}
