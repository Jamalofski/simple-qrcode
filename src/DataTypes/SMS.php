<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * An sms URI: phones open a text message to the number, the message filled in.
 */
class SMS implements DataTypeInterface
{
    /**
     * The scheme of an sms URI.
     *
     * @var string
     */
    protected $prefix = 'sms:';

    /**
     * The separator between the phone number and the message.
     *
     * @var string
     */
    protected $separator = '&body=';

    /**
     * The phone number.
     *
     * @var string
     */
    protected $phoneNumber;

    /**
     * The message, if any.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Sets the phone number and the message.
     *
     * @param array<int, mixed> $arguments The phone number, then the message
     * @return void
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments);
    }

    /**
     * Returns the sms URI.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buildSMSString();
    }

    /**
     * Sets the phone number and the message that are given.
     *
     * @param array<int, mixed> $arguments
     * @return void
     */
    protected function setProperties(array $arguments)
    {
        if (isset($arguments[0])) {
            $this->phoneNumber = $arguments[0];
        }
        if (isset($arguments[1])) {
            $this->message = $arguments[1];
        }
    }

    /**
     * Builds the sms URI.
     *
     * @return string
     */
    protected function buildSMSString()
    {
        $sms = $this->prefix.$this->phoneNumber;

        if (isset($this->message)) {
            $sms .= $this->separator.$this->message;
        }

        return $sms;
    }
}
