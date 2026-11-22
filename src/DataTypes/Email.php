<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

use BaconQrCode\Exception\InvalidArgumentException;

/**
 * A mailto URI (RFC 6068): phones open a new e-mail, the address, subject and body filled in.
 */
class Email implements DataTypeInterface
{
    /**
     * The scheme of a mailto URI.
     *
     * @var string
     */
    protected $prefix = 'mailto:';

    /**
     * The e-mail address, if any.
     *
     * @var string|null
     */
    protected $email;

    /**
     * The subject of the e-mail, if any.
     *
     * @var string|null
     */
    protected $subject;

    /**
     * The body of the e-mail, if any.
     *
     * @var string|null
     */
    protected $body;

    /**
     * Sets the address, the subject and the body.
     *
     * @param array<int, mixed> $arguments The address, the subject, then the body, each one optional
     * @return void
     * @throws InvalidArgumentException when the address is not valid
     */
    public function create(array $arguments)
    {
        $this->setProperties($arguments);
    }

    /**
     * Returns the mailto URI.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->buildEmailString();
    }

    /**
     * Builds the mailto URI. Spaces are encoded as %20, as RFC 6068 requires.
     *
     * @return string
     */
    protected function buildEmailString()
    {
        $email = $this->prefix.$this->email;

        if (isset($this->subject) || isset($this->body)) {
            $data = [
                'subject' => $this->subject,
                'body'    => $this->body,
            ];
            $email .= '?'.http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        }

        return $email;
    }

    /**
     * Sets the address, the subject and the body that are given.
     *
     * @param array<int, mixed> $arguments
     * @return void
     * @throws InvalidArgumentException when the address is not valid
     */
    protected function setProperties(array $arguments)
    {
        if (isset($arguments[0])) {
            $this->setEmail($arguments[0]);
        }
        if (isset($arguments[1])) {
            $this->subject = $arguments[1];
        }
        if (isset($arguments[2])) {
            $this->body = $arguments[2];
        }
    }

    /**
     * Sets the address once it is checked.
     *
     * @param string $email
     * @return void
     * @throws InvalidArgumentException when the address is not valid
     */
    protected function setEmail($email)
    {
        if ($this->isValidEmail($email)) {
            $this->email = $email;
        }
    }

    /**
     * Checks an e-mail address.
     *
     * @param string $email
     * @return bool true, as an invalid address throws
     * @throws InvalidArgumentException when the address is not valid
     */
    protected function isValidEmail($email)
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email provided');
        }

        return true;
    }
}
