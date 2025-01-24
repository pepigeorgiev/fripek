<?php
// Create 192x192 icon
$img = imagecreatetruecolor(192, 192);
$bg = imagecolorallocate($img, 255, 255, 255);
$textcolor = imagecolorallocate($img, 0, 0, 0);
imagefill($img, 0, 0, $bg);
imagestring($img, 5, 50, 80, 'Fripek', $textcolor);
imagepng($img, 'public/images/icon-192x192.png');

// Create 512x512 icon
$img = imagecreatetruecolor(512, 512);
$bg = imagecolorallocate($img, 255, 255, 255);
$textcolor = imagecolorallocate($img, 0, 0, 0);
imagefill($img, 0, 0, $bg);
imagestring($img, 5, 200, 250, 'Fripek', $textcolor);
imagepng($img, 'public/images/icon-512x512.png');
?> 