<?php

namespace SimpleSoftwareIO\QrCode\DataTypes;

/**
 * A geo URI (RFC 5870): phones open the location in a map application.
 */
class Geo implements DataTypeInterface
{
    /**
     * The scheme of a geo URI.
     *
     * @var string
     */
    protected $prefix = 'geo:';

    /**
     * The separator between the latitude and the longitude.
     *
     * @var string
     */
    protected $separator = ',';

    /**
     * The latitude.
     *
     * @var float|string
     */
    protected $latitude;

    /**
     * The longitude.
     *
     * @var float|string
     */
    protected $longitude;

    /**
     * Sets the latitude and the longitude.
     *
     * @param array<int, mixed> $arguments The latitude, then the longitude
     * @return void
     */
    public function create(array $arguments)
    {
        $this->latitude = $arguments[0];
        $this->longitude = $arguments[1];
    }

    /**
     * Returns the geo URI.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->prefix.$this->latitude.$this->separator.$this->longitude;
    }
}
