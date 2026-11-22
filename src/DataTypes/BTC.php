<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * A bitcoin URI (BIP 21): wallets open a payment to the address, the amount filled in.
 */
class BTC implements DataTypeInterface
{
    /**
     * The scheme of a bitcoin URI.
     *
     * @var string
     */
    protected $prefix = 'bitcoin:';

    /**
     * The bitcoin address.
     *
     * @var string|null
     */
    protected $address;

    /**
     * The amount to send, in BTC.
     *
     * @var float|int|string|null
     */
    protected $amount;

    /**
     * The label of the payment, if any.
     *
     * @var string|null
     */
    protected $label;

    /**
     * The message of the payment, if any.
     *
     * @var string|null
     */
    protected $message;

    /**
     * The return URL, if any.
     *
     * @var string|null
     */
    protected $returnAddress;

    /**
     * Sets the address, the amount and the options.
     *
     * @param array<int, mixed> $arguments The address, the amount, then the options: label, message and returnAddress
     * @return void
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments);
    }

    /**
     * Returns the bitcoin URI.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buildBitCoinString();
    }

    /**
     * Sets the address, the amount and the options that are given.
     *
     * @param array<int, mixed> $arguments
     * @return void
     */
    protected function setProperties(array $arguments)
    {
        if (isset($arguments[0])) {
            $this->address = $arguments[0];
        }

        if (isset($arguments[1])) {
            $this->amount = $arguments[1];
        }

        if (isset($arguments[2])) {
            $this->setOptions($arguments[2]);
        }
    }

    /**
     * Sets the optional label, message and return URL.
     *
     * @param array<string, string> $options
     * @return void
     */
    protected function setOptions(array $options)
    {
        if (isset($options['label'])) {
            $this->label = $options['label'];
        }

        if (isset($options['message'])) {
            $this->message = $options['message'];
        }

        if (isset($options['returnAddress'])) {
            $this->returnAddress = $options['returnAddress'];
        }
    }

    /**
     * Builds the bitcoin URI. Spaces are encoded as %20, as BIP 21 requires.
     *
     * @return string
     */
    protected function buildBitCoinString()
    {
        $query = http_build_query([
            'amount'    => is_float($this->amount) || is_int($this->amount) ? $this->decimal($this->amount) : $this->amount,
            'label'     => $this->label,
            'message'   => $this->message,
            'r'         => $this->returnAddress,
        ], '', '&', PHP_QUERY_RFC3986);

        $btc = $this->prefix.$this->address.'?'.$query;

        return $btc;
    }

    /**
     * Writes an amount in decimal notation, to the satoshi: BIP 21 does not read 1.0E-5, which
     * PHP writes for amounts under 0.0001.
     *
     * @param float|int $amount
     * @return string
     */
    private function decimal($amount): string
    {
        return rtrim(rtrim(number_format((float) $amount, 8, '.', ''), '0'), '.');
    }
}
