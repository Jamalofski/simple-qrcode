<?php

namespace SimpleSoftwareIO\QrCode;

use InvalidArgumentException;

/**
 * Merges an image over the center of an svg QrCode, as ImageMerge does for png.
 * The image is embedded as a data URI: any raster image PHP recognizes, or svg.
 */
class SvgImageMerge
{
    /**
     * The svg QrCode.
     *
     * @var string
     */
    protected $svg;

    /**
     * The content of the image to merge.
     *
     * @var string
     */
    protected $image;

    /**
     * @param string $svg
     * @param string $image
     */
    public function __construct(string $svg, string $image)
    {
        $this->svg = $svg;
        $this->image = $image;
    }

    /**
     * Merges the image, its width being $percentage of the width of the QrCode.
     *
     * @param float $percentage
     * @return string
     * @throws InvalidArgumentException
     */
    public function merge(float $percentage): string
    {
        if ($percentage > 1) {
            throw new InvalidArgumentException('$percentage must be less than 1');
        }

        $end = strrpos($this->svg, '</svg>');

        if ($end === false) {
            throw new InvalidArgumentException('The QrCode is not an svg image.');
        }

        [$mime, $ratio] = $this->describeImage();
        [$width, $height] = $this->size();

        $imageWidth = $width * $percentage;
        $imageHeight = $imageWidth / $ratio;

        $element = sprintf(
            '<image x="%s" y="%s" width="%s" height="%s" preserveAspectRatio="xMidYMid meet" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="data:%s;base64,%s"/>',
            $this->number(($width - $imageWidth) / 2),
            $this->number(($height - $imageHeight) / 2),
            $this->number($imageWidth),
            $this->number($imageHeight),
            $mime,
            base64_encode($this->image)
        );

        return substr_replace($this->svg, $element, $end, 0);
    }

    /**
     * The width to height ratio of the image to merge.
     *
     * @return float
     * @throws InvalidArgumentException when the content is not an image
     */
    public function imageRatio(): float
    {
        return $this->describeImage()[1];
    }

    /**
     * The width and height of the QrCode, in the units of its viewBox.
     *
     * @return float[]
     */
    protected function size(): array
    {
        $root = $this->rootTag($this->svg);
        $size = $this->viewBoxSize($root) ?? [$this->attribute($root, 'width'), $this->attribute($root, 'height')];

        if (! $size[0] || ! $size[1]) {
            throw new InvalidArgumentException('The size of the svg QrCode cannot be read.');
        }

        return $size;
    }

    /**
     * The media type of the image and its width to height ratio.
     *
     * @return array
     * @throws InvalidArgumentException
     */
    protected function describeImage(): array
    {
        // A raster image is recognized by its bytes; anything else must hold an <svg> root,
        // whatever comes before it (xml declaration, comments, a DOCTYPE with entities).
        $info = @getimagesizefromstring($this->image);

        if ($info && str_starts_with($info['mime'], 'image/')) {
            return [$info['mime'], $info[0] && $info[1] ? $info[0] / $info[1] : 1];
        }

        $root = $this->rootTag($this->image);

        if ($root === '') {
            throw new InvalidArgumentException('The image merged with an svg QrCode must be an image: png, jpeg, gif, webp, svg...');
        }

        [$width, $height] = $this->viewBoxSize($root) ?? [$this->attribute($root, 'width'), $this->attribute($root, 'height')];

        return ['image/svg+xml', $width && $height ? $width / $height : 1];
    }

    /**
     * The opening <svg> tag of an svg document.
     *
     * @param string $svg
     * @return string
     */
    protected function rootTag(string $svg): string
    {
        return preg_match('/<svg[\s>][^>]*>/i', $svg, $match) ? $match[0] : '';
    }

    /**
     * The width and height of the viewBox of a tag, if it has one.
     *
     * @param string $tag
     * @return float[]|null
     */
    protected function viewBoxSize(string $tag): ?array
    {
        if (! preg_match('/\sviewBox\s*=\s*["\']([^"\']*)["\']/i', $tag, $match)) {
            return null;
        }

        $values = preg_split('/[\s,]+/', trim($match[1]));

        if (count($values) !== 4 || (float) $values[2] <= 0 || (float) $values[3] <= 0) {
            return null;
        }

        return [(float) $values[2], (float) $values[3]];
    }

    /**
     * A numeric attribute of a tag, without its unit, or null.
     *
     * @param string $tag
     * @param string $name
     * @return float|null
     */
    protected function attribute(string $tag, string $name): ?float
    {
        if (! preg_match('/\s'.$name.'\s*=\s*["\']\s*([0-9.]+)\s*(?:px)?\s*["\']/i', $tag, $match)) {
            return null;
        }

        return (float) $match[1] ?: null;
    }

    /**
     * Formats a coordinate without useless decimals.
     *
     * @param float $number
     * @return string
     */
    protected function number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 3, '.', ''), '0'), '.');
    }
}
