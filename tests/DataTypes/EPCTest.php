<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSoftwareIO\QrCode\DataTypes\EPC;
use SimpleSoftwareIO\QrCode\Generator;
use Zxing\QrReader;

class EPCTest extends TestCase
{
    private const TRANSFER = [
        'name' => 'Red Cross of Belgium',
        'iban' => 'BE72 0000 0000 1616',
        'bic' => 'bpotbeb1',
        'amount' => 1,
        'text' => 'Urgency fund',
    ];

    private function epc(array $transfer): EPC
    {
        $epc = new EPC;
        $epc->create([$transfer]);

        return $epc;
    }

    public function test_it_generates_the_payload_of_the_guidelines()
    {
        // The example of the EPC069-12 guidelines, in version 002 and ISO-8859-1.
        $this->assertSame(
            "BCD\n002\n2\nSCT\nBPOTBEB1\nRed Cross of Belgium\nBE72000000001616\nEUR1.00\n\n\nUrgency fund",
            (string) $this->epc(self::TRANSFER)
        );
    }

    public function test_optional_fields_can_be_left_out_and_trailing_fields_are_dropped()
    {
        $this->assertSame(
            "BCD\n002\n1\nSCT\n\nJohn Doe\nDE89370400440532013000",
            $this->epc(['name' => 'John Doe', 'iban' => 'DE89370400440532013000'])->forEncoding('UTF-8')
        );
    }

    public function test_all_fields_are_written_in_their_place()
    {
        $payload = $this->epc([
            'name' => 'Société Générale',
            'iban' => 'FR7630006000011234567890189',
            'amount' => '12.5',
            'purpose' => 'gdds',
            'reference' => 'RF18 5390 0754 7034',
            'information' => 'Thank you',
        ])->forEncoding('ISO-8859-15');

        $this->assertSame(
            "BCD\n002\n8\nSCT\n\nSociété Générale\nFR7630006000011234567890189\nEUR12.50\nGDDS\nRF18539007547034\n\nThank you",
            $payload
        );
    }

    public function test_an_iban_pasted_in_groups_with_any_kind_of_space_is_accepted()
    {
        // Malta: 31 characters, 38 in groups of four. U+00A0 and U+202F come from PDFs and web pages.
        foreach (['MT84 MALT 0110 0001 2345 MTLC AST0 01S', "MT84\u{00A0}MALT\u{00A0}0110\u{00A0}0001\u{202F}2345\tMTLC AST0 01S"] as $iban) {
            $lines = explode("\n", (string) $this->epc(['iban' => $iban, 'bic' => 'MALTMTMT'] + self::TRANSFER));

            $this->assertSame('MT84MALT011000012345MTLCAST001S', $lines[6]);
        }
    }

    public function test_the_bic_is_required_outside_the_european_economic_area()
    {
        $swiss = ['iban' => 'CH93 0076 2011 6238 5295 7', 'bic' => ''] + self::TRANSFER;

        $lines = explode("\n", (string) $this->epc(['bic' => 'UBSWCHZH80A'] + $swiss));
        $this->assertSame('UBSWCHZH80A', $lines[4]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BIC is required for an IBAN outside the European Economic Area');
        $this->epc($swiss);
    }

    public static function amounts(): array
    {
        return [
            'int' => [12, 'EUR12.00'],
            'float rounded to the cent' => [0.1 + 0.2, 'EUR0.30'],
            'string' => ['999999999.99', 'EUR999999999.99'],
            'string, one decimal' => ['0.5', 'EUR0.50'],
        ];
    }

    #[DataProvider('amounts')]
    public function test_the_amount_has_two_decimals($amount, string $line)
    {
        $lines = explode("\n", (string) $this->epc(['amount' => $amount] + self::TRANSFER));

        $this->assertSame($line, $lines[7]);
    }

    public static function invalidTransfers(): array
    {
        return [
            'no name' => [['name' => ''], 'name of the beneficiary is required'],
            'name too long' => [['name' => str_repeat('a', 71)], 'name holds at most 70'],
            'bad IBAN checksum' => [['iban' => 'DE89370400440532013001'], 'not a valid IBAN'],
            'not an IBAN' => [['iban' => '12345'], 'not a valid IBAN'],
            'bad BIC' => [['bic' => 'BPO'], 'not a valid BIC'],
            'amount too small' => [['amount' => 0], 'between 0.01 and 999999999.99'],
            'amount too large' => [['amount' => '1000000000'], 'between 0.01 and 999999999.99'],
            'amount not a number' => [['amount' => NAN], 'between 0.01 and 999999999.99'],
            'infinite amount' => [['amount' => INF], 'between 0.01 and 999999999.99'],
            'three decimals in a string' => [['amount' => '1.005'], 'at most two decimals'],
            'negative amount' => [['amount' => '-5'], 'at most two decimals'],
            'purpose of 3 letters' => [['purpose' => 'ABC'], 'four letters'],
            'reference and text' => [['reference' => 'RF18539007547034'], 'not both'],
            'line break' => [['text' => "two\nlines"], 'line break'],
            'text too long' => [['text' => str_repeat('a', 141)], 'text holds at most 140'],
        ];
    }

    #[DataProvider('invalidTransfers')]
    public function test_an_invalid_transfer_throws_an_exception(array $change, string $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->epc($change + self::TRANSFER);
    }

    public function test_it_throws_an_exception_without_an_array()
    {
        $this->expectException(InvalidArgumentException::class);

        (new EPC)->create(['not an array']);
    }

    public function test_an_encoding_the_guidelines_do_not_list_throws_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot use the SHIFT-JIS encoding');

        $this->epc(self::TRANSFER)->forEncoding('SHIFT-JIS');
    }

    public function test_characters_the_encoding_cannot_hold_throw_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Use the UTF-8 encoding');

        $this->epc(['name' => 'Łódź Sp. z o.o.'] + self::TRANSFER)->forEncoding('ISO-8859-1');
    }

    public function test_the_payload_cannot_exceed_331_bytes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at most 331 bytes');

        $this->epc(['name' => str_repeat('é', 70), 'text' => str_repeat('é', 140), 'information' => str_repeat('é', 70)] + self::TRANSFER)
            ->forEncoding('UTF-8');
    }

    public function test_the_generator_uses_error_correction_m_and_the_character_set_of_its_encoding()
    {
        $epc = $this->epc(self::TRANSFER);

        $this->assertSame(
            (string) (new Generator)->errorCorrection('M')->generate($epc->forEncoding('ISO-8859-1')),
            (string) (new Generator)->EPC(self::TRANSFER)
        );
        $this->assertSame(
            (string) (new Generator)->encoding('UTF-8')->errorCorrection('M')->generate($epc->forEncoding('UTF-8')),
            (string) (new Generator)->encoding('UTF-8')->EPC(self::TRANSFER)
        );
        // An error correction set by the user wins.
        $this->assertSame(
            (string) (new Generator)->errorCorrection('Q')->generate((string) $epc),
            (string) (new Generator)->errorCorrection('Q')->EPC(self::TRANSFER)
        );
    }

    public function test_the_settings_of_the_generator_do_not_change()
    {
        $generator = new Generator;
        $generator->EPC(self::TRANSFER);

        $this->assertSame((string) (new Generator)->generate('after'), (string) $generator->generate('after'));
    }

    public function test_the_qr_code_decodes_to_the_payload()
    {
        $generator = new class extends Generator
        {
            protected function imagickIsAvailable(): bool
            {
                return false;
            }
        };

        $png = (string) $generator->format('png')->size(400)->margin(2)->EPC(self::TRANSFER);
        $text = (new QrReader($png, QrReader::SOURCE_TYPE_BLOB, false))->text();

        $this->assertSame((string) $this->epc(self::TRANSFER), $text);
    }
}
