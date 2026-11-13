<?php

namespace SimpleSoftwareIO\QrCode\Eye;

use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Path\Path;

/**
 * Renders the eyes as squares with rounded corners.
 */
final class RoundedEye implements EyeInterface
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
        // The ring keeps the width of one module: the hole is rounded one module less.
        $path = self::roundedSquare(new Path, 3.5, 1.5, true);

        return self::roundedSquare($path, 2.5, 0.5, false);
    }

    public function getInternalPath(): Path
    {
        return self::roundedSquare(new Path, 1.5, 0.75, true);
    }

    /**
     * Adds a square centered on the origin, with rounded corners. A hole is drawn the other way round.
     */
    private static function roundedSquare(Path $path, float $half, float $radius, bool $clockwise): Path
    {
        $edge = $half - $radius;

        if ($clockwise) {
            return $path
                ->move(-$edge, -$half)
                ->line($edge, -$half)
                ->ellipticArc($radius, $radius, 0, false, true, $half, -$edge)
                ->line($half, $edge)
                ->ellipticArc($radius, $radius, 0, false, true, $edge, $half)
                ->line(-$edge, $half)
                ->ellipticArc($radius, $radius, 0, false, true, -$half, $edge)
                ->line(-$half, -$edge)
                ->ellipticArc($radius, $radius, 0, false, true, -$edge, -$half)
                ->close();
        }

        return $path
            ->move(-$half, -$edge)
            ->line(-$half, $edge)
            ->ellipticArc($radius, $radius, 0, false, false, -$edge, $half)
            ->line($edge, $half)
            ->ellipticArc($radius, $radius, 0, false, false, $half, $edge)
            ->line($half, -$edge)
            ->ellipticArc($radius, $radius, 0, false, false, $edge, -$half)
            ->line(-$edge, -$half)
            ->ellipticArc($radius, $radius, 0, false, false, -$half, -$edge)
            ->close();
    }
}
