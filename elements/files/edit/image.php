<?php 
defined('C5_EXECUTE') or die("Access Denied.");

$f = $fv->getFile();
$fp = new Permissions($f);
if (!$fp->canWrite()) {
	die(t("Access Denied."));
}
$fID = $f->getFileID();

$img_src = BASE_URL . $fv->getRelativePath();
$img_width = $fv->getAttribute('width');
$img_height = $fv->getAttribute('height');
$img_dom_id = "cropbox{$fID}";
?>

<div id="image_cropper_controls_container">
	<label id="image_cropper_zoom_label" for="image_cropper_zoom">Zoom:</label>
	<select id="image_cropper_zoom">
		<option value="1" selected="selected">100%</option>
		<option value="0.75">75%</option>
		<option value="0.5">50%</option>
		<option value="0.25">25%</option>
		<option value="0.1">10%</option>
	</select>
	
	<div id="image_cropper_width_container">
		<label id="image_cropper_width_label" for="image_cropper_width">Target Width:</label>
		<input type="text" id="image_cropper_width_input" name="image_cropper_width_input" maxlength="4" value="<?php echo $img_width; ?>" />
		<span id="image_cropper_width_display" style="display: none;"><?php echo $img_width; ?></span>
		<span id="image_cropper_width_units">px</span>
		<img id="image_cropper_width_lock" src="<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/images/lock.png" width="16" height="16" alt="Image width locked to a number of your choosing" />
		<img id="image_cropper_width_auto" src="<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/images/arrow_out.png" width="16" height="16" alt="Image width auto-set based on height and crop" style="display: none;" />
	</div>
	
	<div id="image_cropper_height_container">
		<label id="image_cropper_height_label" for="image_cropper_height">Target Height:</label>
		<input type="text" id="image_cropper_height_input" name="image_cropper_height_input" maxlength="4" value="<?php echo $img_height; ?>" style="display: none;" />
		<span id="image_cropper_height_display"><?php echo $img_height; ?> px</span>
		<span id="image_cropper_height_units" style="display: none;">px</span>
		<img id="image_cropper_height_lock" src="<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/images/lock.png" width="16" height="16" alt="Image height locked to a number of your choosing" style="display: none;" />
		<img id="image_cropper_height_auto" src="<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/images/arrow_out.png" width="16" height="16" alt="Image height auto-set based on width and crop" />
	</div>
	
	<div id="image_cropper_save_container">
		<input type="checkbox" id="image_cropper_overwrite" />
		<label id="image_cropper_overwrite_label" for="image_cropper_overwrite">Overwrite</label>
		<input type="submit" id="image_cropper_save" value="Save" />
		<img id="image_cropper_save_warning" src="<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/images/error.png" width="16" height="16" alt="Image quality will be degraded at these settings" style="display: none;" />
	</div>
</div>

<div id="image_cropper_image_container">
	<img src="<?php echo $img_src; ?>" id="<?php echo $img_dom_id; ?>" width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>" alt="" />
</div>

<script type="text/javascript">
	//Bring in the required javascripts (in proper order, one after the other) if they haven't already been loaded
	if (typeof ImageEditor == 'undefined') {
		var js_path = '<?php echo BASE_URL.DIR_REL; ?>/packages/image_cropper/js';
		$.getScript(js_path + '/jquery.Jcrop.js', function() {
			$.getScript(js_path + '/image_editor.js', function() {
				$.getScript(js_path + '/ui.js', function() {
					ImageEditor.init($('#<?php echo $img_dom_id; ?>'), jcropOnChangeHandler);
		 		});
		 	});
		 });
	}

	//When the window is closed, destroy the jcrop object (if it's still around)
//TODO: TEST THAT THIS ACTUALLY WORKS! (That it ever gets triggered, AND that the memory is actually cleared when the function is called)
	$(".ccm-dialog-close").click(function() {
		ImageEditor.destroy();
	});
</script>