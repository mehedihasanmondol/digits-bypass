<?php


use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

function digits_create_qr($uri){
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        'imageBase64' => false,
        'imageTransparent' => true,
        'cssClass' => 'digits_qr_code_path',
    ]);
    $qrcode = new QRCode($options);
    return $qrcode->render($uri);
}