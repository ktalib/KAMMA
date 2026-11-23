<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes as inline SVG (bacon/bacon-qr-code, the library KLAES uses). File labels carry the tracking ID
 * itself, not a web address, so a printed label stays valid when KAMMA's domain changes.
 */
class Qr
{
    public static function svg(string $data, int $size = 160): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd())))->writeString($data);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }
}
