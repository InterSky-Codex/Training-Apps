<?php
session_start();

// konfigurasi
$width = 160;
$height = 50;
$length = 6;
$font_size = 20;

// generate kode
$chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // tanpa O,0, I,1 untuk mengurangi kebingungan
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha_code'] = $code;

// Jika GD tersedia, buat PNG; jika tidak, keluarkan SVG agar tetap terlihat
if (function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
    // buat image PNG
    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $fg = imagecolorallocate($img, 40, 40, 40);
    $noise = imagecolorallocate($img, 180, 180, 180);

    imagefill($img, 0, 0, $bg);

    // noise
    for ($i = 0; $i < 6; $i++) {
        imageline($img, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $noise);
    }
    for ($i = 0; $i < 80; $i++) {
        imagesetpixel($img, random_int(0, $width-1), random_int(0, $height-1), $noise);
    }

    // tulis teks — pakai TTF jika ada, fallback ke imagestring
    $font_file = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'Roboto-Regular.ttf';
    if (file_exists($font_file) && function_exists('imagettftext')) {
        $x = 10;
        $y = ($height / 2) + ($font_size / 2) - 4;
        for ($i = 0; $i < strlen($code); $i++) {
            $angle = random_int(-15, 15);
            $char = $code[$i];
            $x_char = $x + $i * (($width - 20) / $length);
            imagettftext($img, $font_size, $angle, (int)$x_char, (int)($y + random_int(-3,3)), $fg, $font_file, $char);
        }
    } else {
        // fallback simple
        $font = 5;
        $cw = imagefontwidth($font);
        $startx = ($width - $cw * strlen($code)) / 2;
        $starty = ($height - imagefontheight($font)) / 2;
        imagestring($img, $font, (int)$startx, (int)$starty, $code, $fg);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-cache, must-revalidate');
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Fallback: keluarkan SVG (tidak butuh GD)
header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, must-revalidate');

$escaped = htmlspecialchars($code, ENT_XML1);
$bgColor = '#ffffff';
$textColor = '#2b2b2b';
$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$width" height="$height" viewBox="0 0 $width $height">
  <rect width="100%" height="100%" fill="$bgColor"/>
  <g font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="700" fill="$textColor">
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle">$escaped</text>
  </g>
</svg>
SVG;

echo $svg;
exit;
?>

