<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * A helper of the generator, such as QrCode::email(): Generator::__call() creates it from the
 * name of the method, passes it the arguments, then encodes its text.
 */
interface DataTypeInterface
{
    /**
     * Sets the data of the QrCode from the arguments passed to the helper.
     *
     * @param array<int, mixed> $arguments
     * @return void
     */
    public function create(array $arguments);

    /**
     * Returns the text to encode in the QrCode.
     *
     * @return string
     */
    public function __toString();
}
