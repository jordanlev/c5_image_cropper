var ImageEditor = {
    
    img:null, //jquery object of the img we're cropping/zooming
    jcrop:null,
    
    original_width:null,
    original_height:null,
    current_zoom:1,
    
    //Store these so when we zoom we can set it back to what it was
    aspectRatio:0,
    onChange:null,
    
	init:function(img, jcropOnChangeHandler){
	    this.img = img;
	    this.original_width = img.attr('width');
	    this.original_height = img.attr('height');
        this.onChange = jcropOnChangeHandler;
        
        this.start_crop({'onChange': jcropOnChangeHandler, 'onSelect': jcropOnChangeHandler});
	},
	
    start_crop:function(options) {
        options = (typeof options == "undefined") ? {} : options;
	    this.jcrop = $.Jcrop(this.img, options);
    },
    
    zoom:function(zoom) { //zoom: 1=100%, 0.5=50%, etc. (due to Jcrop limitation, we cannot go larger than original image -- only smaller)
        //Validate zoom level (avoid divide-by-zero errors and other craziness)
        if (typeof zoom == "undefined" || isNaN(zoom) || zoom <= 0 || zoom > 1) {
            zoom = 1;
        }
        
        //NOTE: For some reason we can't just pass in new options to setOption() -- it doesn't resize the image!
        
        //Temporarily remove the Jcrop selection before resizing the image
        var crop = this.jcrop.tellSelect();
        this.jcrop.destroy();
        
        //Recreate the Jcrop, telling it our new zoom size, and putting back the old options and crop selection if needed.
        // (Jcrop will handle resizing the image for us)
        var options = {
            'aspectRatio': this.aspectRatio,
			'onChange': this.onChange,
			'onSelect': this.onChange
        };
        if (zoom != 1) {
            options['boxWidth'] = zoom * this.original_width;
            options['boxHeight'] = zoom * this.original_height;
        }
        if (crop.h > 0 && crop.w > 0) {
            options['setSelect'] = [crop.x, crop.y, crop.x2, crop.y2]; //Note that Jcrop scales old coords to new ones for us if we passed in boxWidth+boxHeight!
        }

        this.start_crop(options);
        this.current_zoom = zoom;
    },
    
    toggle_aspect_ratio:function(enable) {
        var aspect_ratio = 0;
        if (enable) {
            var crop = this.get_crop();
            var aspect_ratio = (crop.w / crop.h);
        }
        var options = { 'aspectRatio': aspect_ratio };
        this.jcrop.setOptions(options);
        this.aspectRatio = aspect_ratio;
    },
    
    //Change one crop dimension so that it makes sense given the current other ("locked") crop dimension compared to image target dimensions
    //Returns false if the given target dimensions would cause new crop width/height to be more than the image width/height,
    // or if given target width or height is not a valid number (between 1 and 9999).
    set_crop_dimension:function(locked_dimension, target_width, target_height) {
        if (locked_dimension != 'width' && locked_dimension != 'height') {
            throw new Error("Invalid 'locked_dimension' argument passed to set_crop_dimension()!");
        }
        
        if (!this.validate_numeric(target_width) || !this.validate_numeric(target_height)) {
            return false;
        }
        
        var crop = this.get_crop();
        
        // formula: crop width compared to target width == crop height compared to target height
        if (locked_dimension == 'width') {
            var new_crop = Math.round( (crop.w * target_height) / target_width );
        } else {
            var new_crop = Math.round( (crop.h * target_width) / target_height );
        }
        
        //Validate the new crop
        if ( (locked_dimension == 'width' && new_crop > this.original_height) || (locked_dimension == 'height' && new_crop > this.original_width) ) {
            return false;
        } else {
            new_crop = (new_crop < 1) ? 1 : new_crop; //Not possible to be 0 or less, but Math.round might have rounded 0.something down to 0 [not actually sure if that's possible]?
        }
            
        //Calculate new left/top and right/bottom of crop area ("centered" on the old crop area, so it expands/contracts evenly on both sides)
        if (locked_dimension == 'width') {
            var midpoint = Math.round(crop.y + (crop.h / 2));
        } else {
            var midpoint = Math.round(crop.x + (crop.w / 2));
        }
        var new_crop_1 = Math.round(midpoint - (new_crop / 2));
        var new_crop_2 = new_crop_1 + new_crop;
        
        //Now check if our balanced expansion gave us a left/top that starts above 0 or a right/bottom that ends past the edge of the image
        // (and if so, "nudge" the selection area to be inside the bounds of the image).
        if (new_crop_1 < 0) {
            new_crop_2 = new_crop_2 + (0 - new_crop_1);
            new_crop_1 = 0;
        } else if (locked_dimension == 'width' && new_crop_2 > this.original_height) {
            new_crop_1 = new_crop_1 - (new_crop_2 - this.original_height);
            new_crop_2 = this.original_height;
        } else if (locked_dimension == 'height' && new_crop_2 > this.original_width) {
            new_crop_1 = new_crop_1 - (new_crop_2 - this.original_width);
            new_crop_2 = this.original_width;
        }
        
        //Apply newly-calculated coords to the jcrop (and reset aspect ratio if it was set before)
        var had_aspect_ratio = (this.aspect_ratio != 0);
        this.toggle_aspect_ratio(false);
        if (locked_dimension == 'width') {
            if (new_crop == this.original_height && crop.w == this.original_width) {
                this.jcrop.release(); //New crop area is the full size of the image, so just de-select it
            } else {
                this.jcrop.setSelect([crop.x, new_crop_1, crop.x2, new_crop_2]);
            }
        } else {
            if (new_crop == this.original_width && crop.h == this.original_height) {
                this.jcrop.release(); //New crop area is the full size of the image, so just de-select it
            } else {
                this.jcrop.setSelect([new_crop_1, crop.y, new_crop_2, crop.y2]);
            }
        }
        this.toggle_aspect_ratio(had_aspect_ratio);
        
        return true;
    },
    
    //A counterpart to set_crop_dimension() -- call this to find out what the actual limit of one "locked" dimension
    // can be before causing the other dimension to have a crop width or height larger than the original image width or height.
    max_allowable_locked_dimension:function(locked_dimension) {
        var crop = this.get_crop();
        if (locked_dimension == 'width') {
            return Math.round((crop.w * this.original_height) / crop.h);
        } else if (locked_dimension == 'height') {
            return Math.round((crop.h * this.original_width) / crop.w);
        } else {
            throw new Error("Invalid 'locked_dimension' argument passed to max_allowable_locked_dimension()!");
        }
    },
    
    //Given the current crop area and the passed-in width (or height), what is the height (or width)?
    // (Note that this returns the image's target width or height -- NOT the crop width or height).
    //Returns 0 if given dimension value is invalid (not a number between 1 and 9999)
    other_dimension_for_locked_dimension:function(locked_dimension, locked_value) {
        if (locked_dimension == 'width') {
            var locked_dim = 'w';
            var other_dim = 'h';
        } else if (locked_dimension == 'height') {
            var locked_dim = 'h';
            var other_dim = 'w';
        } else {
            throw new Error("Invalid 'locked_dimension' argument passed to other_dimension_for_locked_dimension()!");
        }

        if (!this.validate_numeric(locked_value)) {
            return 0;
        }

        
        var crop = this.get_crop();
        
        // formula: crop width compared to target width == crop height compared to target height
        var other_value = Math.round( (crop[other_dim] * locked_value) / crop[locked_dim] );
        
        return other_value;
    },
    
    //Returns true if the given number is a number between 1 and 9999, otherwise returns false;
    validate_numeric:function(val) {
        return !(val == '' || isNaN(val) || val < 1 || val > 9999);
    },
    
    crop_width:function() {
        var crop = this.get_crop();
        return crop.w;
    },
    crop_height:function() {
        var crop = this.get_crop();
        return crop.h;
    },
    
    //return true/false depending on if new image dims will cause degradation (that is, if new width/height is greater than original (or greater than current crop selection against the original)
    degraded:function(target_width, target_height) {
        var crop = this.get_crop();
        return (target_width > crop.w || target_height > crop.h);
    },
    
    //wrapper around jcrop.tellSelect() -- if no crop is selected we return full image width+height instead of 0
    get_crop:function() {
        var crop = this.jcrop.tellSelect();

        //Watch out for crop.h=0 and crop.w=0 -- treat that as if entire image was selected
        if (crop.w == 0 && crop.h == 0) {
            crop.w = crop.x2 = this.original_width;
            crop.h = crop.y2 = this.original_height;
        }
        
        return crop;
    },

    destroy:function() {
		if (typeof this.jcrop !== "undefined" && this.jcrop !== null) {
			this.jcrop.destroy();
		}
	}
}