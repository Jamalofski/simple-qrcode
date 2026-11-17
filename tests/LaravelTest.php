<?php

use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use SimpleSoftwareIO\QrCode\Generator;
use SimpleSoftwareIO\QrCode\QrCodeServiceProvider;

class LaravelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [QrCodeServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['QrCode' => QrCode::class];
    }

    public function test_the_service_provider_binds_the_generator()
    {
        $this->assertInstanceOf(Generator::class, $this->app->make('qrcode'));
    }

    public function test_the_facade_generates_an_svg_html_string()
    {
        $result = QrCode::size(120)->generate('https://example.com');

        $this->assertInstanceOf(HtmlString::class, $result);
        $this->assertStringContainsString('<svg', (string) $result);
        $this->assertStringContainsString('width="120"', (string) $result);
    }

    public function test_the_alias_resolves_like_in_a_blade_view()
    {
        $html = $this->app['view']->file(__DIR__.'/Views/qrcode.blade.php')->render();

        $this->assertStringContainsString('<svg', $html);
    }

    public function test_merge_reads_a_path_relative_to_the_base_path_with_or_without_a_leading_slash()
    {
        $this->app->setBasePath(__DIR__);
        $logo = base64_encode(file_get_contents(__DIR__.'/Images/simplesoftware-icon-grey-blue.png'));

        // Without the leading slash, merge() looked for <base path>Images/... and failed.
        foreach (['Images/simplesoftware-icon-grey-blue.png', '/Images/simplesoftware-icon-grey-blue.png'] as $path) {
            $svg = (string) QrCode::size(200)->merge($path, .2)->generate('relative path');

            $this->assertStringContainsString('data:image/png;base64,'.$logo, $svg, $path);
        }
    }
}
