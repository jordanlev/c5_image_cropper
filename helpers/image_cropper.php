<?
defined('C5_EXECUTE') or die("Access Denied.");

class ImageCropperHelper {

	public function edit($path, $crop_x, $crop_y, $crop_w, $crop_h, $target_w, $target_h) {
		$imageSize = @getimagesize($originalPath);
		$img_type = $imageSize[2];
		
		//create "canvas" to put new resized and/or cropped image into
		$image = @imageCreateTrueColor($target_w, $target_h);
		
		switch($img_type) {
			case IMAGETYPE_GIF:
				$im = @imageCreateFromGIF($path);
				break;
			case IMAGETYPE_JPEG:
				$im = @imageCreateFromJPEG($path);
				break;
			case IMAGETYPE_PNG:
				$im = @imageCreateFromPNG($path);
				break;
		}
		
		if ($im) {
			// Better transparency - thanks for the ideas and some code from mediumexposure.com
			if (($img_type == IMAGETYPE_GIF) || ($img_type == IMAGETYPE_PNG)) {
				$trnprt_indx = imagecolortransparent($im);
				
				// If we have a specific transparent color
				if ($trnprt_indx >= 0) {
			
					// Get the original image's transparent color's RGB values
					$trnprt_color = imagecolorsforindex($im, $trnprt_indx);
					
					// Allocate the same color in the new image resource
					$trnprt_indx = imagecolorallocate($image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
					
					// Completely fill the background of the new image with allocated color.
					imagefill($image, 0, 0, $trnprt_indx);
					
					// Set the background color for new image to transparent
					imagecolortransparent($image, $trnprt_indx);
					
				
				} else if ($img_type == IMAGETYPE_PNG) {
				
					// Turn off transparency blending (temporarily)
					imagealphablending($image, false);
					
					// Create a new transparent color for image
					$color = imagecolorallocatealpha($image, 0, 0, 0, 127);
					
					// Completely fill the background of the new image with allocated color.
					imagefill($image, 0, 0, $color);
					
					// Restore transparency blending
					imagesavealpha($image, true);
			
				}
			}
			
			$res = @imageCopyResampled($image, $im, 0, 0, $crop_x, $crop_y, $target_w, $target_h, $crop_w, $crop_h);
			if ($res) {
				switch($img_type) {
					case IMAGETYPE_GIF:
						$res2 = imageGIF($image, $path);
						break;
					case IMAGETYPE_JPEG:
						$compression = defined('AL_THUMBNAIL_JPEG_COMPRESSION') ? AL_THUMBNAIL_JPEG_COMPRESSION : 80;
						$res2 = imageJPEG($image, $path, $compression);
						break;
					case IMAGETYPE_PNG:
						$res2 = imagePNG($image, $path);
						break;
				}
			}
		}
	}

}