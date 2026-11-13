<?php

namespace SimpleSoftwareIO\QrCode\Eye;

use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Path\Path;

/**
 * Renders the eyes as a ring and a disc. Unlike bacon's SimpleCircleEye, used by
 * eye('circle'), the external part is round too.
 */
final class CircleEye implements EyeInterface
{
    /**
     * @var self|null
     */
    private static $instance;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self;
    }

    public function getExternalPath(): Path
    {
        return self::circle(self::circle(new Path, 3.5, true), 2.5, false);
    }

    public function getInternalPath(): Path
    {
        return self::circle(new Path, 1.5, true);
    }

    /**
     * Adds a circle centered on the origin. A hole is drawn the other way round.
     */
    private static function circle(Path $path, float $radius, bool $clockwise): Path
    {
        $side = $clockwise ? $radius : -$radius;

        return $path
            ->move($radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, $side)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, -$radius, 0)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, 0, -$side)
            ->ellipticArc($radius, $radius, 0, false, $clockwise, $radius, 0)
            ->close();
    }
}
