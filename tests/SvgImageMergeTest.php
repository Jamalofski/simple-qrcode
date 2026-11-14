<?php

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
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

    public function test_an_illustrator_svg_logo_with_a_doctype_subset_is_recognized()
    {
        $logo = '<?xml version="1.0" encoding="utf-8"?>'."\n"
            .'<!-- Generator: Adobe Illustrator 27.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->'."\n"
            .'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd" ['."\n"
            .'	<!ENTITY ns_extend "http://ns.adobe.com/Extensibility/1.0/">'."\n"
            .'	<!ENTITY ns_ai "http://ns.adobe.com/AdobeIllustrator/10.0/">'."\n"
            .']>'."\n"
            .'<svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 400 200"><rect width="400" height="200"/></svg>';
        $image = $this->image((new SvgImageMerge(self::SVG, $logo))->merge(.2));

        $this->assertSame(['60', '30'], [$image['width'], $image['height']]);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $image['xlink:href']);
    }

    public function test_any_raster_image_php_recognizes_is_embedded()
    {
        $bmp = imagecreatetruecolor(40, 20);
        ob_start();
        imagebmp($bmp);
        $image = $this->image((new SvgImageMerge(self::SVG, ob_get_clean()))->merge(.2));

        $this->assertSame(['60', '30'], [$image['width'], $image['height']]);
        $this->assertStringStartsWith('data:image/bmp;base64,', $image['xlink:href']);
    }

    public function test_something_that_is_not_an_image_throws_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an image');

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

    public function test_a_merged_logo_raises_the_error_correction_to_h_unless_one_is_set()
    {
        $logo = __DIR__.'/Images/simplesoftware-icon-grey-blue.png';
        $merged = fn () => (new Generator)->size(300)->merge($logo, .2, true);

        $this->assertSame((string) $merged()->errorCorrection('H')->generate('level'), (string) $merged()->generate('level'));
        $this->assertNotSame((string) $merged()->errorCorrection('H')->generate('level'), (string) $merged()->errorCorrection('L')->generate('level'));
        // Without a logo, nothing changes.
        $this->assertSame((string) (new Generator)->errorCorrection('L')->generate('level'), (string) (new Generator)->generate('level'));
    }

    public function test_eps_ignores_a_merged_logo_as_before()
    {
        $eps = (string) (new Generator)->format('eps')->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)->generate('eps');

        $this->assertSame((string) (new Generator)->format('eps')->generate('eps'), $eps);
        $this->assertSame($eps, (string) (new Generator)->format('eps')->merge(__DIR__.'/Images/simplesoftware-icon-grey-blue.png', .2, true)->clearUnderLogo()->generate('eps'));
    }

    public function test_clear_under_logo_removes_the_modules_under_an_svg_logo()
    {
        // An svg logo, 200 by 100: its ratio sets the height of the cleared area.
        $logo = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#c00"/></svg>';
        $text = 'https://example.com/svg';
        $merged = fn () => (new Generator)->size(300)->mergeString($logo, .3);

        // The outline of the modules has its corners on whole modules. In module units, the code
        // is n wide and the logo 0.3 n wide and half as high, centered: no corner falls inside it.
        $n = Encoder::encode($text, ErrorCorrectionLevel::H())->getMatrix()->getWidth();
        $cornersUnderTheLogo = function (string $svg) use ($n): int {
            preg_match_all('/[ML](-?[\d.]+) (-?[\d.]+)/', $svg, $corners, PREG_SET_ORDER);

            return count(array_filter($corners, fn ($c) => abs($c[1] - $n / 2) < 0.15 * $n && abs($c[2] - $n / 2) < 0.075 * $n));
        };

        $covered = (string) $merged()->generate($text);
        $cleared = (string) $merged()->clearUnderLogo()->generate($text);

        $this->assertGreaterThan(0, $cornersUnderTheLogo($covered));
        $this->assertSame(0, $cornersUnderTheLogo($cleared));
        $this->assertSame($this->image($covered), $this->image($cleared), 'The logo itself does not change.');
        $this->assertNotFalse(simplexml_load_string($cleared));

        // Without a logo, clearUnderLogo() changes nothing.
        $this->assertSame((string) (new Generator)->generate('plain'), (string) (new Generator)->clearUnderLogo()->generate('plain'));
    }
}
