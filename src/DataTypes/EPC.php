<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

use InvalidArgumentException;

/**
 * EPC QR code for a SEPA credit transfer (EPC069-12, version 002), also known as
 * GiroCode or SEPA QR code: banking apps read it to fill in the transfer.
 */
class EPC implements DataTypeInterface
{
    /**
     * The character sets of the EPC guidelines, by QR code encoding.
     *
     * @var array
     */
    protected const CHARACTER_SETS = [
        'UTF-8' => 1,
        'ISO-8859-1' => 2,
        'ISO-8859-2' => 3,
        'ISO-8859-4' => 4,
        'ISO-8859-5' => 5,
        'ISO-8859-7' => 6,
        'ISO-8859-10' => 7,
        'ISO-8859-15' => 8,
    ];

    /**
     * The largest payload the guidelines allow, in bytes.
     */
    protected const MAX_BYTES = 331;

    /**
     * The fields of the transfer.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Generates the DataType Object and sets all of its properties.
     *
     * @param array $arguments
     * @throws InvalidArgumentException
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments[0] ?? null);
    }

    /**
     * Returns the payload for the default encoding of the QR code, ISO-8859-1.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->forEncoding('ISO-8859-1');
    }

    /**
     * Returns the payload, its character set matching the encoding of the QR code.
     *
     * @param string $encoding
     * @return string
     * @throws InvalidArgumentException
     */
    public function forEncoding(string $encoding): string
    {
        $encoding = strtoupper($encoding);

        if (! isset(self::CHARACTER_SETS[$encoding])) {
            throw new InvalidArgumentException(
                "An EPC QR code cannot use the {$encoding} encoding. Use UTF-8 or one of "
                .implode(', ', array_slice(array_keys(self::CHARACTER_SETS), 1)).'.'
            );
        }

        $fields = $this->fields;
        $lines = [
            'BCD',
            '002',
            (string) self::CHARACTER_SETS[$encoding],
            'SCT',
            $fields['bic'],
            $fields['name'],
            $fields['iban'],
            $fields['amount'] === '' ? '' : 'EUR'.$fields['amount'],
            $fields['purpose'],
            $fields['reference'],
            $fields['text'],
            $fields['information'],
        ];

        // The last field set ends the payload: no trailing line feeds.
        $payload = rtrim(implode("\n", $lines), "\n");

        $bytes = $encoding === 'UTF-8' ? $payload : @iconv('UTF-8', $encoding, $payload);

        if ($bytes === false) {
            throw new InvalidArgumentException("The transfer contains characters that {$encoding} cannot encode. Use the UTF-8 encoding.");
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidArgumentException('An EPC QR code holds at most '.self::MAX_BYTES.' bytes; this transfer needs '.strlen($bytes).'.');
        }

        return $payload;
    }

    /**
     * Checks and sets the fields of the transfer.
     *
     * @param mixed $arguments
     * @throws InvalidArgumentException
     */
    protected function setProperties($arguments)
    {
        if (! is_array($arguments)) {
            throw new InvalidArgumentException('EPC() takes an array with at least the name and the iban of the beneficiary.');
        }

        $name = $this->text($arguments, 'name', 70);
        $iban = strtoupper(str_replace(' ', '', $this->text($arguments, 'iban', 34)));
        $bic = strtoupper(str_replace(' ', '', $this->text($arguments, 'bic', 11)));
        $purpose = strtoupper($this->text($arguments, 'purpose', 4));
        $reference = str_replace(' ', '', $this->text($arguments, 'reference', 35));
        $text = $this->text($arguments, 'text', 140);
        $information = $this->text($arguments, 'information', 70);

        if ($name === '') {
            throw new InvalidArgumentException('The name of the beneficiary is required.');
        }

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) || ! $this->ibanChecksumIsValid($iban)) {
            throw new InvalidArgumentException("{$iban} is not a valid IBAN.");
        }

        if ($bic !== '' && ! preg_match('/^[A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?$/', $bic)) {
            throw new InvalidArgumentException("{$bic} is not a valid BIC.");
        }

        if ($purpose !== '' && ! preg_match('/^[A-Z]{4}$/', $purpose)) {
            throw new InvalidArgumentException("The purpose must be a code of four letters. {$purpose} is not valid.");
        }

        if ($reference !== '' && $text !== '') {
            throw new InvalidArgumentException('An EPC QR code holds a structured reference or a text, not both.');
        }

        $this->fields = [
            'name' => $name,
            'iban' => $iban,
            'bic' => $bic,
            'amount' => $this->amount($arguments['amount'] ?? null),
            'purpose' => $purpose,
            'reference' => $reference,
            'text' => $text,
            'information' => $information,
        ];
    }

    /**
     * Checks the two check digits of an IBAN (ISO 13616, modulo 97), so a typo
     * does not reach a banking app.
     *
     * @param string $iban
     * @return bool
     */
    protected function ibanChecksumIsValid(string $iban): bool
    {
        $digits = '';

        foreach (str_split(substr($iban, 4).substr($iban, 0, 4)) as $character) {
            $digits .= ctype_digit($character) ? $character : (string) (ord($character) - 55);
        }

        $remainder = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        return $remainder === 1;
    }

    /**
     * A text field, trimmed, on one line and not longer than the guidelines allow.
     *
     * @param array $arguments
     * @param string $key
     * @param int $maxLength
     * @return string
     * @throws InvalidArgumentException
     */
    protected function text(array $arguments, string $key, int $maxLength): string
    {
        $value = trim((string) ($arguments[$key] ?? ''));

        if (preg_match('/[\r\n]/', $value)) {
            throw new InvalidArgumentException("The {$key} cannot contain a line break.");
        }

        if (preg_match_all('/./us', $value) > $maxLength) {
            throw new InvalidArgumentException("The {$key} holds at most {$maxLength} characters.");
        }

        return $value;
    }

    /**
     * The amount in euros, with two decimals, or an empty string without amount.
     *
     * @param mixed $amount
     * @return string
     * @throws InvalidArgumentException
     */
    protected function amount($amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        // A float is rounded to the cent (0.1 + 0.2 is not 0.3); a string must be exact.
        if (is_int($amount) || is_float($amount)) {
            $amount = round((float) $amount, 2);
        } elseif (is_string($amount) && preg_match('/^\d+(?:\.\d{1,2})?$/', trim($amount))) {
            $amount = (float) trim($amount);
        } else {
            throw new InvalidArgumentException('The amount must be a number of euros with at most two decimals. '.var_export($amount, true).' is not valid.');
        }

        if ($amount < 0.01 || $amount > 999999999.99) {
            throw new InvalidArgumentException('The amount must be between 0.01 and 999999999.99 euros.');
        }

        return number_format($amount, 2, '.', '');
    }
}
