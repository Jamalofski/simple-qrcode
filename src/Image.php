<?php

namespace SimpleSoftwareIO\QrCode;

/**
 * A GD image read from a string, for ImageMerge.
 */
class Image
{
    /**
     * Holds the GD image, or false when the string was not an image.
     *
     * @var \GdImage|false
     */
    protected $image;

    /**
     * Creates a new Image object.
     *
     * @param string $image An image string
     */
    public function __construct($image)
    {
        $this->image = imagecreatefromstring($image);
    }

    /**
     * Returns the width of an image.
     *
     * @return int
     */
    public function getWidth()
    {
        return imagesx($this->image);
    }

    /**
     * Returns the height of an image.
     *
     * @return int
     */
    public function getHeight()
    {
        return imagesy($this->image);
    }

    /**
     * Returns the GD image.
     *
     * @return \GdImage|false
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
