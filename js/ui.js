//Javascript (mostly Jquery event handlers) for editor front-end controls

var last_good_width;
var last_good_height;
	
function init_ui(img_dom_id, save_post_url) {
    ImageEditor.init($('#'+img_dom_id), jcropOnChangeHandler);
    last_good_width = width_val();
    last_good_height = height_val();

/* Width/Height Input Change Events */
    $('#image_cropper_width_input').change(function() {
        if (height_locked()) {
    	    var valid = ImageEditor.set_crop_dimension('width', width_val(), height_val());
        	if (!valid) {
        		width_val(last_good_width);
                alert('Invalid width (it would result in the cropped height being taller than the original image)');
        	}
        } else {
            var target_height = ImageEditor.other_dimension_for_locked_dimension('width', width_val());
            if (target_height == 0) {
                width_val(last_good_width); //No alert -- this only happens if non-numeric is entered, so it'll be obvious
            } else {
                height_val(target_height);
            }
        }
    
        last_good_width = width_val();
    });

    $('#image_cropper_height_input').change(function() {
        if (width_locked()) {
            var valid = ImageEditor.set_crop_dimension('height', width_val(), height_val());
            if (!valid) {
                height_val(last_good_height);
                alert('Invalid height (it would result in the cropped width being wider than the original image)');
            }
        } else {
            var target_width = ImageEditor.other_dimension_for_locked_dimension('height', height_val());
            if (target_width == 0) {
                height_val(last_good_height); //No alert -- this only happens if non-numeric is entered, so it'll be obvious
            } else {
                width_val(target_width);
            }
        }
    
        last_good_height = height_val();
    });

    
/** Lock Icon Crop Events **/
    $('#image_cropper_width_lock').click(function() {
        update_for_lock_change();
    });

    $('#image_cropper_width_auto').click(function() {
        update_for_lock_change();
    });

    $('#image_cropper_height_lock').click(function() {
        update_for_lock_change();
    });

    $('#image_cropper_height_auto').click(function() {
        update_for_lock_change();
    });


/** Lock Icon UI Events **/
    $('#image_cropper_width_lock').hover(function() { //hoverIn:
    	$(this).attr('hover-out-src', $(this).attr('src')).attr('src', $('#image_cropper_width_auto').attr('src'));
    }, function() { //hoverOut:
    	$(this).attr('src', $(this).attr('hover-out-src')).removeAttr('hover-out-src');
    });
    $('#image_cropper_width_lock').click(function() {
    	$(this).hide();
    	$('#image_cropper_width_auto').show();
    	$('#image_cropper_width_input').hide();
    	$('#image_cropper_width_units').hide();
    	$('#image_cropper_width_display').show();
    });

    $('#image_cropper_width_auto').hover(function() { //hoverIn:
    	$(this).attr('hover-out-src', $(this).attr('src')).attr('src', $('#image_cropper_width_lock').attr('src'));
    }, function() { //hoverOut:
    	$(this).attr('src', $(this).attr('hover-out-src')).removeAttr('hover-out-src');
    });
    $('#image_cropper_width_auto').click(function() {
    	$(this).hide();
    	$('#image_cropper_width_lock').show();
    	$('#image_cropper_width_display').hide();
    	$('#image_cropper_width_units').show();
    	$('#image_cropper_width_input').show();
    });

    $('#image_cropper_height_lock').hover(function() { //hoverIn:
    	$(this).attr('hover-out-src', $(this).attr('src')).attr('src', $('#image_cropper_height_auto').attr('src'));
    }, function() { //hoverOut:
    	$(this).attr('src', $(this).attr('hover-out-src')).removeAttr('hover-out-src');
    });
    $('#image_cropper_height_lock').click(function() {
    	$(this).hide();
    	$('#image_cropper_height_auto').show();
    	$('#image_cropper_height_input').hide();
    	$('#image_cropper_height_units').hide();
    	$('#image_cropper_height_display').show();
    });

    $('#image_cropper_height_auto').hover(function() { //hoverIn:
    	$(this).attr('hover-out-src', $(this).attr('src')).attr('src', $('#image_cropper_height_lock').attr('src'));
    }, function() { //hoverOut:
    	$(this).attr('src', $(this).attr('hover-out-src')).removeAttr('hover-out-src');
    });
    $('#image_cropper_height_auto').click(function() {
    	$(this).hide();
    	$('#image_cropper_height_lock').show();
    	$('#image_cropper_height_display').hide();
    	$('#image_cropper_height_units').show();
    	$('#image_cropper_height_input').show();
    });


/** Other Events **/
    $('#image_cropper_save').click(function() {
        save(save_post_url);
    });

    $('#image_cropper_zoom').change(function() {
        ImageEditor.zoom($(this).val());
    });

	//When the window is closed, destroy the jcrop object (if it's still around)
//TODO: TEST THAT THIS ACTUALLY WORKS! (That it ever gets triggered, AND that the memory is actually cleared when the function is called)
	$(".ccm-dialog-close").click(function() {
		ImageEditor.destroy();
	});

}


/** Event Handler Functions **/

function jcropOnChangeHandler(crop) {
    //Watch out for no crop area!
    if (crop.w == 0 && crop.h == 0) {
        crop.w = ImageEditor.crop_width();
        crop.h = ImageEditor.crop_height();
    }

    if (!width_locked() && !height_locked()) {
        width_val(ImageEditor.crop_width());
        height_val(ImageEditor.crop_height());
    } else if (width_locked()) {
        height_val(ImageEditor.other_dimension_for_locked_dimension('width', width_val()));
    } else if (height_locked()) {
        width_val(ImageEditor.other_dimension_for_locked_dimension('height', height_val()));
    }
}

function update_for_lock_change() {
    //When width+height are both in "locked" mode, the crop area gets locked to an aspect ratio (and when they're not, it doesn't):
    ImageEditor.toggle_aspect_ratio(width_locked() && height_locked());

    //When width+height are both in "auto" mode, the crop area always equals them (i.e. no resizing):
    if (!width_locked() && !height_locked()) {
        width_val(ImageEditor.crop_width());
        height_val(ImageEditor.crop_height());
    }
}

function toggle_save_warning() {
	$('#image_cropper_save_warning').toggle(ImageEditor.degraded(width_val(), height_val()));
}

function save(post_url) {
    var crop = ImageEditor.get_crop();
    var data = {
        'fID': $('#image_cropper_fID').val(),
        'ocID': $('#image_cropper_odID').val(),
        'ccm_token': $('#image_cropper_ccm_token').val(),
        'override': $('#image_cropper_overwrite').is(':checked'),
        'crop_x': crop.x,
        'crop_y': crop.y,
        'crop_w': crop.w,
        'crop_h': crop.h,
        'target_w': width_val(),
        'target_h': height_val()
    };
    
    $.post(post_url, data, function(response) {
        alert("Data Loaded: " + response);
    });
}


/** Utility Functions **/

function width_val(val) {
    if (typeof val == "undefined") {
	    return width_locked() ? $('#image_cropper_width_input').val() : $('#image_cropper_width_display').html().slice(0, -3);
    } else {
        val = (val < 1) ? 1 : val;
	    $('#image_cropper_width_input').val(val);
    	$('#image_cropper_width_display').html(val + ' px');
    	last_good_width = val;
    	toggle_save_warning();
    	return val;
    }
}
function height_val(val) {
    if (typeof val == "undefined") {
	    return height_locked() ? $('#image_cropper_height_input').val() : $('#image_cropper_height_display').html().slice(0, -3);
    } else {
        val = (val < 1) ? 1 : val;
	    $('#image_cropper_height_input').val(val);
    	$('#image_cropper_height_display').html(val + ' px');
    	last_good_height = val;
    	toggle_save_warning();
    	return val;
    }
}

function width_locked() {
    return $('#image_cropper_width_input').is(':visible');
}
function height_locked() {
    return $('#image_cropper_height_input').is(':visible');
}
