<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * A tel URI: phones offer to call the number.
 */
class PhoneNumber implements DataTypeInterface
{
    /**
     * The scheme of a tel URI.
     *
     * @var string
     */
    protected $prefix = 'tel:';

    /**
     * The phone number.
     *
     * @var string
     */
    protected $phoneNumber;

    /**
     * Sets the phone number.
     *
     * @param array<int, mixed> $arguments The phone number
     * @return void
     */
    public function create(array $arguments)
    {
        $this->phoneNumber = $arguments[0];
    }

    /**
     * Returns the tel URI.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->prefix.$this->phoneNumber;
    }
}
