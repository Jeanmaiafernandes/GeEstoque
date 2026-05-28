<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    /**
     * Gera um código de barras Code 128 como SVG inline (sem cabeçalho XML).
     */
    public function code128Svg(string $value, float $widthFactor = 1.5, float $height = 38): string
    {
        $svg = (new BarcodeGeneratorSVG())->getBarcode($value, BarcodeGeneratorSVG::TYPE_CODE_128, $widthFactor, $height);

        return $this->inline($svg);
    }

    /**
     * Gera um QR Code como SVG inline (sem cabeçalho XML).
     */
    public function qrSvg(string $value, int $size = 110): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($value);

        return $this->inline($svg);
    }

    /**
     * Remove a declaração XML/DOCTYPE para permitir embutir o SVG direto no HTML.
     */
    private function inline(string $svg): string
    {
        $position = strpos($svg, '<svg');

        return $position === false ? $svg : substr($svg, $position);
    }
}
