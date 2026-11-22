<?php

namespace SimpleSoftwareIO\QrCode;

use InvalidArgumentException;

/**
 * Merges an image, usually a logo, over the center of a png QrCode, with GD.
 * SvgImageMerge does the same for svg.
 */
class ImageMerge
{
    /**
     * The QrCode.
     *
     * @var Image
     */
    protected $sourceImage;

    /**
     * The image to merge over it.
     *
     * @var Image
     */
    protected $mergeImage;

    /**
     * The height of the QrCode, in pixels.
     *
     * @var int
     */
    protected $sourceImageHeight;

    /**
     * The width of the QrCode, in pixels.
     *
     * @var int
     */
    protected $sourceImageWidth;

    /**
     * The height of the image to merge, in pixels.
     *
     * @var int
     */
    protected $mergeImageHeight;

    /**
     * The width of the image to merge, in pixels.
     *
     * @var int
     */
    protected $mergeImageWidth;

    /**
     * The width to height ratio of the image to merge, rounded to two decimals.
     *
     * @var float
     */
    protected $mergeRatio;

    /**
     * The height of the merged image on the QrCode, in pixels.
     *
     * @var int
     */
    protected $postMergeImageHeight;

    /**
     * The width of the merged image on the QrCode, in pixels.
     *
     * @var int
     */
    protected $postMergeImageWidth;

    /**
     * The top edge of the merged image on the QrCode, in pixels.
     *
     * @var int
     */
    protected $centerY;

    /**
     * The left edge of the merged image on the QrCode, in pixels.
     *
     * @var int
     */
    protected $centerX;

    /**
     * Creates a new ImageMerge object.
     *
     * @param Image $sourceImage The QrCode
     * @param Image $mergeImage The image to merge over it
     */
    public function __construct(Image $sourceImage, Image $mergeImage)
    {
        $this->sourceImage = $sourceImage;
        $this->mergeImage = $mergeImage;
    }

    /**
     * Merges the image over the center of the QrCode and returns the result as a png.
     *
     * @param float $percentage The width of the merged image, as a share of the width of the QrCode
     * @return string
     * @throws InvalidArgumentException when the percentage is over 1
     */
    public function merge($percentage)
    {
        $this->mergeImage($percentage);

        return $this->createImage();
    }

    /**
     * Merges the image like merge(), and returns the GD image instead of encoding it as png.
     *
     * @param float $percentage The width of the merged image, as a share of the width of the QrCode
     * @return \GdImage
     * @throws InvalidArgumentException when the percentage is over 1
     */
    public function mergeImage($percentage)
    {
        $this->setProperties($percentage);

        $img = imagecreatetruecolor($this->sourceImage->getWidth(), $this->sourceImage->getHeight());
        // Start from a fully transparent canvas and keep the alpha channel in the png,
        // so a transparent background stays transparent once the logo is merged.
        imagealphablending($img, false);
        imagesavealpha($img, true);
        // A true color image never runs out of colors: the allocation cannot fail.
        $transparent = (int) imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        imagealphablending($img, true);

        imagecopy(
            $img,
            $this->sourceImage->getImageResource(),
            0,
            0,
            0,
            0,
            $this->sourceImage->getWidth(),
            $this->sourceImage->getHeight()
        );

        imagecopyresampled(
            $img,
            $this->mergeImage->getImageResource(),
            $this->centerX,
            $this->centerY,
            0,
            0,
            $this->postMergeImageWidth,
            $this->postMergeImageHeight,
            $this->mergeImageWidth,
            $this->mergeImageHeight
        );

        $this->sourceImage->setImageResource($img);

        return $img;
    }

    /**
     * Encodes the merged QrCode as a png.
     *
     * @return string
     */
    protected function createImage()
    {
        ob_start();
        imagepng($this->sourceImage->getImageResource());

        return (string) ob_get_clean();
    }

    /**
     * Reads the sizes of both images and computes where the merged image goes.
     *
     * @param float $percentage The width of the merged image, as a share of the width of the QrCode
     * @return void
     * @throws InvalidArgumentException when the percentage is over 1
     */
    protected function setProperties($percentage)
    {
        if ($percentage > 1) {
            throw new InvalidArgumentException('$percentage must be less than 1');
        }

        $this->sourceImageHeight = $this->sourceImage->getHeight();
        $this->sourceImageWidth = $this->sourceImage->getWidth();

        $this->mergeImageHeight = $this->mergeImage->getHeight();
        $this->mergeImageWidth = $this->mergeImage->getWidth();

        $this->calculateOverlap($percentage);
        $this->calculateCenter();
    }

    /**
     * Computes the top left corner of the merged image, so that it sits in the center.
     *
     * @return void
     */
    protected function calculateCenter()
    {
        $this->centerX = intval(($this->sourceImageWidth / 2) - ($this->postMergeImageWidth / 2));
        $this->centerY = intval(($this->sourceImageHeight / 2) - ($this->postMergeImageHeight / 2));
    }

    /**
     * Computes the size of the merged image, keeping its ratio.
     *
     * @param float $percentage The width of the merged image, as a share of the width of the QrCode
     * @return void
     */
    protected function calculateOverlap($percentage)
    {
        $this->mergeRatio = round($this->mergeImageWidth / $this->mergeImageHeight, 2);
        $this->postMergeImageWidth = intval($this->sourceImageWidth * $percentage);
        $this->postMergeImageHeight = intval($this->postMergeImageWidth / $this->mergeRatio);
    }
}
