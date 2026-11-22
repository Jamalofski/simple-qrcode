<?php

namespace SimpleSoftwareIO\QrCode;

use Illuminate\Support\ServiceProvider;

/**
 * Binds the generator in Laravel: the QrCode facade resolves a new Generator each time.
 */
class QrCodeServiceProvider extends ServiceProvider
{
    /**
     * Registers the generator under the "qrcode" key.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('qrcode', function () {
            return new Generator();
        });
    }

    /**
     * Returns the services the provider registers.
     *
     * @return array<int, class-string>
     */
    public function provides()
    {
        return [Generator::class];
    }
}
