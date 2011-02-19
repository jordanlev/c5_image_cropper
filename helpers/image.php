<?
//If you're on concrete version 5.4.1.1 or earlier, use this:
// $ih = Loader::helper('image_cropper', 'image_cropper');
//instead of this:
// $ih = Loader::helper('image');

//If you're on a concrete version AFTER 5.4.1.1, you do not need to use this helper.

//(This file copied from http://svn.concrete5.org/svn/concrete5/trunk/web/concrete/helpers/image.php, revision 2144, on 2011-02-18)

/**
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * Functions useful for working with images.
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 * Now includes
 * Modification of concrete/helpers/image.php to include cropping functionality.
 * 
 * When you call this helper's "create()", "getThumbnail()", or "outputThumbnail()" functions,
 * you can add a new argument of "true" to the end of the parameters list to enable cropping.
 * For example:
 * $ih = Loader::helper('image_cropper', 'image_cropper');
 * $ih->getThumbnail($fileObj, 150, 150, true); //<-- will crop the image to 150x150 pixels

 */

defined('C5_EXECUTE') or die("Access Denied.");
class ImageCropperHelper {

		
	/**
	 * Creates a new image given an original path, a new path, a target width and height.
	 * Optionally crops image to exactly match given width and height.
	 * @params string $originalPath, string $newpath, int $width, int $height, bool $crop
	 * @return void
	 */		
	public function create($originalPath, $newPath, $width, $height, $crop = false) {
		// first, we grab the original image. We shouldn't ever get to this function unless the image is valid
		$imageSize = @getimagesize($originalPath);
		$oWidth = $imageSize[0];
		$oHeight = $imageSize[1];
		$finalWidth = 0; //For cropping, this is really "scale to width before chopping extra height"
		$finalHeight = 0; //For cropping, this is really "scale to height before chopping extra width"
		$do_crop_x = false;
		$do_crop_y = false;
		$crop_src_x = 0;
		$crop_src_y = 0;

		// first, if what we're uploading is actually smaller than width and height, we do nothing
		if ($oWidth < $width && $oHeight < $height) {
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
			$width = $oWidth;
			$height = $oHeight;
		} else if ($crop && ($height >= $oHeight && $width <= $oWidth)) {
			//crop to width only -- don't scale anything
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
			$height = $oHeight;
			$do_crop_x = true;
		} else if ($crop && ($width >= $oWidth && $height <= $oHeight)) {
			//crop to height only -- don't scale anything
			$finalHeight = $oHeight;
			$finalWidth = $oWidth;
			$width = $oWidth;
			$do_crop_y = true;
		} else {
			// otherwise, we do some complicated stuff
			// first, we divide original width and height by new width and height, and find which difference is greater
			$wDiff = $oWidth / $width;
			$hDiff = $oHeight / $height;
			
			if (!$crop && ($wDiff > $hDiff)) {
				//no cropping, just resize down based on target width
				$finalWidth = $width;
				$finalHeight = $oHeight / $wDiff;
			} else if (!$crop) {
				//no cropping, just resize down based on target height
				$finalWidth = $oWidth / $hDiff;
				$finalHeight = $height;
			} else if ($crop && ($wDiff > $hDiff)) {
				//resize down to target height, THEN crop off extra width
				$finalWidth = $oWidth / $hDiff;
				$finalHeight = $height;
				$do_crop_x = true;
			} else if ($crop) {
				//resize down to target width, THEN crop off extra height
				$finalWidth = $width;
				$finalHeight = $oHeight / $wDiff;
				$do_crop_y = true;
			}
		}
		
		//Calculate cropping to center image
		if ($do_crop_x) {
			$crop_src_x = round(($oWidth - ($width * $oHeight / $height)) * 0.5);
		}
		if ($do_crop_y) {
			$crop_src_y = round(($oHeight - ($height * $oWidth / $width)) * 0.5);
		}
		
		//create "canvas" to put new resized and/or cropped image into
		if ($crop) {
			$image = @imageCreateTrueColor($width, $height);
		} else {
			$image = @imageCreateTrueColor($finalWidth, $finalHeight);
		}
		
		switch($imageSize[2]) {
			case IMAGETYPE_GIF:
				$im = @imageCreateFromGIF($originalPath);
				break;
			case IMAGETYPE_JPEG:
				$im = @imageCreateFromJPEG($originalPath);
				break;
			case IMAGETYPE_PNG:
				$im = @imageCreateFromPNG($originalPath);
				break;
		}
		
		if ($im) {
			// Better transparency - thanks for the ideas and some code from mediumexposure.com
			if (($imageSize[2] == IMAGETYPE_GIF) || ($imageSize[2] == IMAGETYPE_PNG)) {
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
					
				
				} else if ($imageSize[2] == IMAGETYPE_PNG) {
				
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
			
			$res = @imageCopyResampled($image, $im, 0, 0, $crop_src_x, $crop_src_y, $finalWidth, $finalHeight, $oWidth, $oHeight);
			if ($res) {
				switch($imageSize[2]) {
					case IMAGETYPE_GIF:
						$res2 = imageGIF($image, $newPath);
						break;
					case IMAGETYPE_JPEG:
						$compression = defined('AL_THUMBNAIL_JPEG_COMPRESSION') ? AL_THUMBNAIL_JPEG_COMPRESSION : 80;
						$res2 = imageJPEG($image, $newPath, $compression);
						break;
					case IMAGETYPE_PNG:
						$res2 = imagePNG($image, $newPath);
						break;
				}
			}
		}
	}
	
	/** 
	 * Returns a path to the specified item, resized and/or cropped to meet max width and height. $obj can either be
	 * a string (path) or a file object. 
	 * Returns an object with the following properties: src, width, height, alt
	 * @param mixed $obj
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @param bool $crop
	 */
	public function getThumbnail($obj, $maxWidth, $maxHeight, $crop = false) {
		if ($obj instanceof File) {
			$path = $obj->getPath();
		} else {
			$path = $obj;
		}		
		
		$fh = Loader::helper('file');
		$prefix = ($crop ? 'cropped:' : ''); //Name cropped images different from resized images so they don't get mixed up in the cache
		if (file_exists($path)) {
			$filename = md5($prefix . $path . ':' . $maxWidth . ':' . $maxHeight . ':' . filemtime($path)) . '.' . $fh->getExtension($path);
		} else {
			$filename = md5($prefix . $path . ':' . $maxWidth . ':' . $maxHeight . ':') . $fh->getExtension($path);
		}

		if (!file_exists(DIR_FILES_CACHE . '/' . $filename)) {
			// create image there
			$this->create($path, DIR_FILES_CACHE . '/' . $filename, $maxWidth, $maxHeight, $crop);
		}
		
		$src = REL_DIR_FILES_CACHE . '/' . $filename;
		$abspath = DIR_FILES_CACHE . '/' . $filename;
		$thumb = new stdClass;
		if (isset($abspath) && file_exists($abspath)) {			
			$thumb->src = $src;
			$dimensions = getimagesize($abspath);
			$thumb->width = $dimensions[0];
			$thumb->height = $dimensions[1];
			return $thumb;
		}					
	}
	
	/** 
	 * Runs getThumbnail on the path, and then prints it out as an XHTML image
	 */
	public function outputThumbnail($obj, $maxWidth, $maxHeight, $alt = null, $return = false, $crop = false) {
		$thumb = $this->getThumbnail($obj, $maxWidth, $maxHeight, $crop);
		$html = '<img class="ccm-output-thumbnail" alt="' . $alt . '" src="' . $thumb->src . '" width="' . $thumb->width . '" height="' . $thumb->height . '" />';
		if ($return) {
			return $html;
		} else {
			print $html;
		}
	}
	
	public function output($obj, $alt = null, $return = false) {
		$s = @getimagesize($obj->getPath());
		$html = '<img class="ccm-output-image" alt="' . $alt . '" src="' . $obj->getRelativePath() . '" width="' . $s[0] . '" height="' . $s[1] . '" />';
		if ($return) {
			return $html;
		} else {
			print $html;
		}
	}


}