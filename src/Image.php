<?php

namespace SimpleSoftwareIO\QrCode;

use InvalidArgumentException;

/**
 * A GD image read from a string, for ImageMerge.
 */
class Image
{
    /**
     * Holds the GD image.
     *
     * @var \GdImage
     */
    protected $image;

    /**
     * Creates a new Image object.
     *
     * @param string $image An image string
     * @throws InvalidArgumentException when the string is not an image GD can read
     */
    public function __construct($image)
    {
        // Content that is not an image gave a PHP warning, then a TypeError on false.
        $gdImage = $image === '' ? false : @imagecreatefromstring($image);

        if ($gdImage === false) {
            throw new InvalidArgumentException(
                'The image to merge is not one GD can read: png, jpeg, gif, webp... An svg logo works in the svg format only.'
            );
        }

        $this->image = $gdImage;
    }

    /**
     * Returns the width of an image.
     *
     * @return int<1, max>
     */
    public function getWidth()
    {
        return imagesx($this->image);
    }

    /**
     * Returns the height of an image.
     *
     * @return int<1, max>
     */
    public function getHeight()
    {
        return imagesy($this->image);
    }

    /**
     * Returns the GD image.
     *
     * @return \GdImage
     */
    public function getImageResource()
    {
        return $this->image;
    }

    /**
     * Sets the GD image.
     *
     * @param \GdImage $image
     * @return void
     */
    public function setImageResource($image)
    {
        $this->image = $image;
    }
}
