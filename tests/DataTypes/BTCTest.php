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
