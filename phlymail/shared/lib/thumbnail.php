<?php
/**
 * Creates thumbnail through various means like ImageMagick or GD
 *
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Basic functionalities
 * @copyright 2010-2011 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.0.2 2011-04-10
 */

class thumbnail {

    /**
     * Creates a thumbnail from a source file and returns it with some info as a string.
     * If the source is not processable or not accessible the function returns false
     *
     * @param string $file Path to the source file
     * @param int $width Maximum width of the bounding box
     * @param int $height Maximum height of the bounding box
     * @return array
     * 'size' => The length of thumbnail string
     * 'mime' => The MIME type of the thumbnail
     * 'width' => The image width of the thumbnail
     * 'height' => The image height of the thumbnail
     * 'stream' => The thumbnail itself as a string
     */
    static public function create($file, $width, $height)
    {
        global $_PM_;

        // File not accessible
        if (!file_exists($file) || !is_readable($file)) return false;

        $generated = false;

        // File supposedly too large
        if (!$generated && filesize($file) > $_PM_['size']['thumb_filesize']) return false;

        // Try ImageMagick on command line
        try {
            $tmp_file = $file.'.'.$width.'.tmp';
            exec('convert '.$file.' -resize '.$width.'x'.$height.' -quality 60 jpg:'.$tmp_file);
            if (file_exists($tmp_file) && is_readable($tmp_file)) {
                $ti = getimagesize($tmp_file);
                $thstream = file_get_contents($tmp_file);
                $thmime = 'image/jpeg';
                unlink($tmp_file);
                if (strlen($thstream) > 0) {
                    $generated = true;
                }
            }
        } catch(Exception $e) {
            // nothing!
        }

        // Try Imagick's PHP API
        // This is really much of a shot in the dark, no checking, nothing!
        if (!$generated && class_exists('Imagick')) {
            // path to the sRGB ICC profile
            $srgbPath = $_PM_['path']['conf'].'/srgb_v4_icc_preference.icc';
            // load the original image
            try {
                $image = new Imagick($file);
            } catch (Exception $e) {
                return false;
            }
            // get the original dimensions
            $owidth = $image->getImageWidth();
            $oheight = $image->getImageHeight();
            if ($owidth*$oheight < $_PM_['size']['thumb_pixelsize']) {

                // set colour profile
                // this step is necessary even though the profiles are stripped out in the next step to reduce file size
                $srgb = file_get_contents($srgbPath);
                $image->profileImage('icc', $srgb);
                // strip colour profiles
                $image->stripImage();
                // set colorspace
                $image->setImageColorspace(Imagick::COLORSPACE_SRGB);
                // determine which dimension to fit to
                $fitWidth = ($width / $owidth) < ($height / $oheight);
                // create thumbnail
                $image->thumbnailImage($fitWidth ? $width : 0, $fitWidth ? 0 : $height);

                $image->setImageFormat('jpeg');
                $thmime = 'image/jpeg';
                $thstream = $image->getImageBlob();
                $ti = array(0 => $image->getImageWidth(), 1 => $image->getImageHeight());
                $image->clear();
                $image->destroy();
                if (!empty($ti[0])) $generated = true;
            }
        }

        // Try GD
        if (!$generated && function_exists('imagecreatetruecolor')) {
            $ii = @getimagesize($file);
            // Only try creating the thumbnail with the correct GD support.
            // GIF got dropped a while ago, then reappeared again; JPEG or PNG might not be compiled in
            if ($ii[2] == 1 && !function_exists('imagecreatefromgif')) $ii[2] = 0;
            if ($ii[2] == 2 && !function_exists('imagecreatefromjpeg')) $ii[2] = 0;
            if ($ii[2] == 3 && !function_exists('imagecreatefrompng')) $ii[2] = 0;
            if ($ii[2] == 15 && !function_exists('imagecreatefromwbmp')) $ii[2] = 0;
            // a supported source image file type, pixel dimensions small enough and source file not too big
            if (!empty($ii[2]) && $ii[0]*$ii[1] < $_PM_['size']['thumb_pixelsize']) {
                $ti = $ii;
                if ($ti[0] > $width || $ti[1] > $height) {
                    $wf = $ti[0] / $width; // Calculate width factor
                    $hf = $ti[1] / $height; // Calculate height factor
                    if ($wf >= $hf && $wf > 1) {
                        $ti[0] /= $wf;
                        $ti[1] /= $wf;
                    } elseif ($hf > 1) {
                        $ti[0] /= $hf;
                        $ti[1] /= $hf;
                    }
                    $ti[0] = round($ti[0], 0);
                    $ti[1] = round($ti[1], 0);
                }
                if ($ii[2] == 1) {
                    $si = imagecreatefromgif($file);
                } elseif ($ii[2] == 2) {
                    $si = imagecreatefromjpeg($file);
                } elseif ($ii[2] == 3) {
                    $si = imagecreatefrompng($file);
                } elseif ($ii[2] == 15) {
                    $si = imagecreatefromwbmp($file);
                }
                if (!empty($si)) {
                    $tn = imagecreatetruecolor($ti[0], $ti[1]);
                    imagecopyresized($tn, $si, 0, 0, 0, 0, $ti[0], $ti[1], $ii[0], $ii[1]);
                    // Get the thumbnail and populate thumbinfo
                    ob_start();
                    if (imagetypes() & IMG_JPG) {
                        $thmime = 'image/jpeg';
                        imagejpeg($tn, null, 75);
                    } elseif (imagetypes() & IMG_PNG) {
                        $thmime = 'image/png';
                        imagepng($tn, null);
                    } elseif (imagetypes() & IMG_GIF) {
                        $thmime = 'image/gif';
                        imagegif($tn, null);
                    }
                    $thstream = ob_get_clean();
                    $generated = true;
                    imagedestroy($tn);
                }
            }
        }
        return array('size' => strlen($thstream), 'mime' => $thmime, 'width' => $ti[0], 'height' => $ti[1], 'stream' => $thstream);
    }
}
