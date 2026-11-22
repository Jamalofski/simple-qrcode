<?php

use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\DataTypes\BTC;

class BTCTest extends TestCase
{
    protected $btc;

    public function setUp(): void
    {
        $this->btc = new BTC();
    }

    public function test_it_generates_a_valid_btc_qrcode_with_an_address_and_amount()
    {
        $this->btc->create(['btcaddress', 0.0034]);

        $properFormat = 'bitcoin:btcaddress?amount=0.0034';

        $this->assertEquals($properFormat, strval($this->btc));
    }

    public function test_it_generates_a_valid_btc_qrcode_with_an_address_amount_and_label()
    {
        $this->btc->create(['btcaddress', 0.0034, ['label' => 'label']]);

        $properFormat = 'bitcoin:btcaddress?amount=0.0034&label=label';

        $this->assertEquals($properFormat, strval($this->btc));
    }

    public function test_amounts_are_written_in_decimal_to_the_satoshi()
    {
        // PHP writes 0.00001 as 1.0E-5, which BIP 21 does not read.
        foreach ([
            ['0.00001', 0.00001],
            ['0.00000001', 0.00000001],
            ['0.3', 0.1 + 0.2],
            ['21000000', 21000000],
            ['1.5', 1.5],
            ['0.00001', '0.00001'],
        ] as [$expected, $amount]) {
            $btc = new BTC;
            $btc->create(['btcaddress', $amount]);

            $this->assertSame("bitcoin:btcaddress?amount={$expected}", (string) $btc, var_export($amount, true));
        }
    }

    public function test_spaces_are_encoded_as_percent_20_not_plus()
    {
        // BIP 21 uses RFC 3986 percent-encoding.
        $this->btc->create(['btcaddress', 0.0034, ['label' => 'My shop', 'message' => 'Order 12']]);

        $properFormat = 'bitcoin:btcaddress?amount=0.0034&label=My%20shop&message=Order%2012';

        $this->assertEquals($properFormat, strval($this->btc));
    }

    public function test_it_generates_a_valid_btc_qrcode_with_an_address_amount_label_message_and_return_address()
    {
        $this->btc->create([
            'btcaddress',
            0.0034,
            [
                'label'         => 'label',
                'message'       => 'message',
                'returnAddress' => 'https://www.returnaddress.com',
            ],
        ]);

        $properFormat = 'bitcoin:btcaddress?amount=0.0034&label=label&message=message&r='.urlencode('https://www.returnaddress.com');

        $this->assertEquals($properFormat, strval($this->btc));
    }
}
