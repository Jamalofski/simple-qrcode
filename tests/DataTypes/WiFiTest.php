<?php

use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\DataTypes\WiFi;

class WiFiTest extends TestCase
{
    protected $wifi;

    public function setUp(): void
    {
        $this->wifi = new Wifi();
    }

    public function test_it_generates_a_proper_format_with_just_the_ssid()
    {
        $this->wifi->create([
            0 => [
                'ssid' => 'foo',
            ],
        ]);

        $properFormat = 'WIFI:S:foo;';

        $this->assertEquals($properFormat, strval($this->wifi));
    }

    public function test_it_generates_a_proper_format_for_a_ssid_that_is_hidden()
    {
        $this->wifi->create([
            0 => [
                'ssid'   => 'foo',
                'hidden' => 'true',
            ],
        ]);

        $properFormat = 'WIFI:S:foo;H:true;';

        $this->assertEquals($properFormat, strval($this->wifi));
    }

    public function test_hidden_can_be_a_boolean()
    {
        // PHP wrote true as H:1 and false as H:, which readers do not understand.
        foreach ([[true, 'WIFI:S:foo;H:true;'], [false, 'WIFI:S:foo;H:false;']] as [$hidden, $expected]) {
            $wifi = new WiFi;
            $wifi->create([['ssid' => 'foo', 'hidden' => $hidden]]);

            $this->assertSame($expected, (string) $wifi);
        }
    }

    public function test_it_generates_a_proper_format_for_a_ssid_encryption_and_password()
    {
        $this->wifi->create([
            0 => [
                'ssid'       => 'foo',
                'encryption' => 'WPA',
                'password'   => 'bar',
            ],
        ]);

        $properFormat = 'WIFI:T:WPA;S:foo;P:bar;';

        $this->assertEquals($properFormat, strval($this->wifi));
    }

    public function test_it_generates_a_proper_format_for_a_ssid_encryption_password_and_is_hidden()
    {
        $this->wifi->create([
            0 => [
                'ssid'       => 'foo',
                'encryption' => 'WPA',
                'password'   => 'bar',
                'hidden'     => 'true',
            ],
        ]);

        $properFormat = 'WIFI:T:WPA;S:foo;P:bar;H:true;';

        $this->assertEquals($properFormat, strval($this->wifi));
    }

    public function test_it_escapes_the_special_characters_of_the_ssid_and_password()
    {
        $this->wifi->create([
            0 => [
                'ssid'       => 'Café; "Guest", 2:4',
                'encryption' => 'WPA',
                'password'   => 'a;b,c:d"e\\f',
            ],
        ]);

        $properFormat = 'WIFI:T:WPA;S:Café\\; \\"Guest\\"\\, 2\\:4;P:a\\;b\\,c\\:d\\"e\\\\f;';

        $this->assertEquals($properFormat, strval($this->wifi));
    }
}
