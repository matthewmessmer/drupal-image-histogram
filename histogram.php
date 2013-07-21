<?php


function histogram_make_histogram($image, $color = "#000000", $forcebw = FALSE, $histType = 1, $histW = 256) {
  set_time_limit(50000);
  if (!isset($image)) {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    break;
  }
//HISTOGRAM VARIABLES
  $source_file = file_create_url($image);
  $path = str_replace('public:/', variable_get('file_public_path', 'sites/default/files'), $image);
  $file_basename = drupal_basename($image);
  $maxheight = 100;
  $iscolor = FALSE;
  $im = ImageCreateFromJpeg($source_file);
  $directory = str_replace($file_basename,"",$path);
  $directory .= 'histograms';
  if (!is_dir($directory)) {
    drupal_mkdir($directory);
  };

  $imgw = imagesx($im);
  $imgh = imagesy($im);
  $n = $imgw * $imgh;
  $histo = array();
  $histoR = array();
  $histoG = array();
  $histoB = array();

  $histoRcompiled = array();
  $histoGcompiled = array();
  $histoBcompiled = array();

// ZERO HISTOGRAM VALUES

  for ($i = 0; $i < 256; $i++) {
    $histo[$i] = 0;
    $histoR[$i] = 0;
    $histoG[$i] = 0;
    $histoB[$i] = 0;
  }

// CALCULATE PIXELS

  for ($i = 0; $i < $imgw; $i++) {
    for ($j = 0; $j < $imgh; $j++) {
      $rgb = ImageColorAt($im, $i, $j);
      $r = ($rgb >> 16) & 0xFF;
      $g = ($rgb >> 8) & 0xFF;
      $b = $rgb & 0xFF;
      $V = round(($r + $g + $b) / 3);
      $histo[$V] += 1;

      $V = round($r * 1);
      $histoR[$V] += 1;
      $V = round($g * 1);
      $histoG[$V] += 1;
      $V = round($b * 1);
      $histoB[$V] += 1;
    }
  }
  imagedestroy($im);

// COLOR OR GRAYSCALE
  if ($forcebw != TRUE) {
    for ($a = 0; $a < count($histoR); $a++) {
      if ($histoR[$a] != $histoG[$a] || $histoG[$a] != $histoB[$a]) {
        $iscolor = TRUE;
        break;
      }
    }
  }

//CREATE HISTOGRAM IMAGE
  if (($histType == '1') or ($iscolor == FALSE)) {
    $imR = imagecreatetruecolor(256, 100)
    or die ("Cannot Initialize new GD image stream");
  }
  else {
    $imR = imagecreatetruecolor(256, 300)
    or die ("Cannot Initialize new GD image stream");
  }

// CONVERT BACKGROUND COLOR TO RGB

  $rgbcolor = histogram_html2rgb($color);


//RGB

  if ($iscolor) {
    // MAKE BACKGROUND
    $back = imagecolorallocate($imR, $rgbcolor[0], $rgbcolor[1], $rgbcolor[2]);

    // compute bounds of vertical axis

    // sort the histograms to find tallest bins
    $sHistoR = $histoR;
    $sHistoG = $histoG;
    $sHistoB = $histoB;
    sort($sHistoR);
    sort($sHistoG);
    sort($sHistoB);

    // we allow clipping of at most the 5 tallest histogram bins, but clipping
    // also needs to be useful. i.e. if clipping does not change the vertical
    // range much, then don't do it. The following heuristic code enforces this.
    $lerpR = min(max(($sHistoR[255] / $sHistoR[250] - 1.15) / 2.0, 0.0), 1.0);
    $lerpG = min(max(($sHistoG[255] / $sHistoG[250] - 1.15) / 2.0, 0.0), 1.0);
    $lerpB = min(max(($sHistoB[255] / $sHistoB[250] - 1.15) / 2.0, 0.0), 1.0);
    $histoClipR = (1.0 - $lerpR) * $sHistoR[255] + $lerpR * $sHistoR[250];
    $histoClipG = (1.0 - $lerpG) * $sHistoG[255] + $lerpG * $sHistoG[250];
    $histoClipB = (1.0 - $lerpB) * $sHistoB[255] + $lerpB * $sHistoB[250];
    $histoClip = max($histoClipR, $histoClipG, $histoClipB);

    if ($histType == '1') {
      // COMBINED COLOR HISTOGRAM
      imagefilledrectangle($imR, 0, 0, 256, 100, $back);

      // CREATE GRAPH
      for ($a = 0; $a < 256; $a++) {
        $heightsRGB = array(
          min($histoR[$a] / $histoClip, 1.0) * $maxheight,
          min($histoG[$a] / $histoClip, 1.0) * $maxheight,
          min($histoB[$a] / $histoClip, 1.0) * $maxheight
        );
        $lineOrder = array(0, 1, 2);
        array_multisort($heightsRGB, $lineOrder);

        // Draw 3 vertical lines.
        // First a white line, for the extent that all the histograms overlap,
        // Then as we cross each histogram, remove that appropriate color
        // component.
        $lineRGB = array(255, 255, 255);
        $lineColor = ImageColorAllocateAlpha($imR, $lineRGB[0], $lineRGB[1], $lineRGB[2], 0);
        $start = $maxheight - $heightsRGB[0];
        $end = $maxheight;
        imageline($imR, ($a + 1), $start, ($a + 1), $end, $lineColor);

        $lineRGB[$lineOrder[0]] = 0;
        $lineColor = ImageColorAllocateAlpha($imR, $lineRGB[0], $lineRGB[1], $lineRGB[2], 0);
        $start = $maxheight - $heightsRGB[1];
        $end = $maxheight - $heightsRGB[0];
        imageline($imR, ($a + 1), $start, ($a + 1), $end, $lineColor);

        $lineRGB[$lineOrder[1]] = 0;
        $lineColor = ImageColorAllocateAlpha($imR, $lineRGB[0], $lineRGB[1], $lineRGB[2], 0);
        $start = $maxheight - $heightsRGB[2];
        $end = $maxheight - $heightsRGB[1];
        imageline($imR, ($a + 1), $start, ($a + 1), $end, $lineColor);
      }
    }
    else { // SEPARATE R G B HISTOGRAMS
      imagefilledrectangle($imR, 0, 0, 256, 300, $back);

      // CREATE GRAPH
      for ($a = 0; $a < 256; $a++) {
        $lineColor = ImageColorAllocateAlpha($imR, 255, 0, 0, 0);
        $start = $maxheight - min($histoR[$a] / $histoClipR, 1.0) * $maxheight;
        $end = $maxheight;
        imageline($imR, ($a + 1), $start, ($a + 1), $end, $lineColor);
        $histoRcompiled[] = floor(min($histoR[$a] / $histoClipR, 1.0) * $maxheight);

        $lineColor = ImageColorAllocateAlpha($imR, 0, 255, 0, 0);
        $start = $maxheight - min($histoG[$a] / $histoClipG, 1.0) * $maxheight;
        $end = $maxheight;
        imageline($imR, ($a + 1), $start + 100, ($a + 1), $end + 100, $lineColor);
        $histoGcompiled[] = floor($start);

        $lineColor = ImageColorAllocateAlpha($imR, 0, 0, 255, 0);
        $start = $maxheight - min($histoB[$a] / $histoClipB, 1.0) * $maxheight;
        $end = $maxheight;
        imageline($imR, ($a + 1), $start + 200, ($a + 1), $end + 200, $lineColor);
        $histoBcompiled[] = floor($start);
      }
    }
  }
  else { // GRAYSCALE

    // COMPUTE MAX VALUES
    $max = max($histo);

    // compute bounds of vertical axis

    // sort the histogram to find tallest bins
    $sortedHisto = $histo;
    sort($sortedHisto);

    // we allow clipping of at most the 5 tallest histogram bins, but clipping
    // also needs to be useful. i.e. if clipping does not change the vertical
    // range much, then don't do it. The following heuristic code enforces this.
    $lerpFactor = min(max(($sortedHisto[255] / $sortedHisto[250] - 1.15) / 2.0, 0.0), 1.0);
    $histoClip = (1.0 - $lerpFactor) * $sortedHisto[255] + $lerpFactor * $sortedHisto[250];

    // CREATE HISTOGRAM BACKGROUND
    $back = imagecolorallocate($imR, $rgbcolor[0], $rgbcolor[1], $rgbcolor[2]);
    imagefilledrectangle($imR, 0, 0, 256, 100, $back);

    // MAKE HISTOGRAM COLOR NOT MATCH BACKGROUND LIGHTNESS
    if ((($rgbcolor[0] + $rgbcolor[0] + $rgbcolor[0]) / 3) < 127) {
      $graphcolor = 255;
    }
    else {
      $graphcolor = 0;
    }
    $text_color = ImageColorAllocateAlpha($imR, $graphcolor, $graphcolor, $graphcolor, 0);

    // CREATE HISTOGRAM
    for ($a = 0; $a < 256; $a++) {
      $h = min($histo[$a] / $histoClip, 1.0) * $maxheight;
      $start = ($maxheight - $h);
      imageline($imR, ($a + 1), $start, ($a + 1), $maxheight, $text_color);
    }

    // ENG GRAY HISTOGRAM
  }


// SAVE HISTOGRAM AND DESTROY RESOURCE
  if (is_writable($directory)) {
    touch($directory . "/hist_" . $file_basename);


    if ($histW != 256) {
      $newW = $histW;
      $scale = $newW / 256;

      $width = imagesx($imR);
      $height = imagesy($imR);

      $new_width = floor($scale * $width);
      $new_height = floor($scale * $height);
      $tmp_img = imagecreatetruecolor($new_width, $new_height);
      // gd 2.0.1 or later: imagecopyresampled
      // gd less than 2.0: imagecopyresized
      if (function_exists(imagecopyresampled)) {
        imagecopyresampled($tmp_img, $imR, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
      }
      else {
        imagecopyresized($tmp_img, $imR, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
      }
      imagedestroy($imR);
      $imR = $tmp_img;

    }

    imagejpeg($imR, $directory . "/hist_" . $file_basename, 100);
    $histogram = $directory . "/hist_" . $file_basename;
    chmod($histogram, 0644);
    imagedestroy($imR);
    $perms = fileperms($directory);
    drupal_set_message(t('Histogram Created.'));

    return $histogram;

  }
  else {
    drupal_set_message(t('The folder is not writable.'), 'error');
  }


}


function histogram_html2rgb($color) {
  if ($color[0] == '#') {
    $color = substr($color, 1);
  }
  if (strlen($color) == 6) {
    list($r, $g, $b) = array(
      $color[0] . $color[1],
      $color[2] . $color[3],
      $color[4] . $color[5]
    );
  }
  elseif (strlen($color) == 3) {
    list($r, $g, $b) = array($color[0], $color[1], $color[2]);
  }
  else {
    return FALSE;
  }
  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);
  return array($r, $g, $b);
}
