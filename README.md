# What is this?
This is a package for the Concrete5 CMS ( http://concrete5.org ) which replaces the built-in functionality of the "Edit" menu item with a lightweight javascript image editor which allows you to crop and resize the image without having to load the external "Piknik" service.

#Why?
I don't like how choosing "Edit" from the popup menu when you click on an image in the file manager tries to connect you to the Piknik online service (and I say "tries" because I've never actually gotten it to work for me, although plenty of other people swear they have so it must just be me). Even if it did work, I still don't need such heavyweight functionality on my website. But being able to quickly and easily crop images after uploading them would be *very* useful to me -- both to myself and to non-technical users who sometimes don't understand that uploading an image directly from your camera without resizing it first results in HUGE file sizes and slow page loads. With this image editor, it becomes feasible to instruct my clients (the people managing the sites I build) to "click Edit, type 600 into the Width box, crop if you want, then click save".

# How To Install
1. Click the "Downloads" button up above (way up near the top of this page, over to the right).
2. Click "Download .zip".
3. Unzip the downloaded file.
4. Rename the unzipped folder to "image_cropper"
5. Copy that `image_cropper` folder to your site's `packages` directory (NOT `concrete/packages`).
6. Log into your site, go to Dashboard -> Add Functionality, then click the "Install" button next to "Image Cropper".

# Usage Instructions (for site editors)
[NOTE: THIS PACKAGE IS CURRENTLY IN DEVELOPMENT -- SAVING FUNCTIONALITY HAS NOT YET BEEN IMPLEMENTED! YOU CAN TEST THIS OUT AND SEE HOW THE INTERFACE WORKS BUT YOU WILL NOT BE ABLE TO SAVE YOUR CROPPED OR RESIZED IMAGES YET!]

To bring up the image editor, click on an image thumbnail from the file manager (or from add/edit dialog of any block which allows for image selection), then choose "Edit" from the popup menu.

I've tried to keep the interface as simple as possible. Towards that end, I have combined a lot of functionality into a few controls -- hopefully in a way that becomes clear once you start using it.

When the image editor is first opened, the image will be shown at its full size. If the image is larger than the size of the window, you can use the Zoom dropdown to "zoom out" and view the image at a smaller size. Note that this smaller size is strictly for viewing purposes and has no effect on the eventual size of the saved image.

To crop the image, simply click and drag on it. If you change your mind and don't want to crop the image, click anywhere outside selected area to undo it.

The Width and Height controls show the size that the image will be resized to. They can either be "locked" or "unlocked" (as indicated by a lock icon or an expanding arrows icon, respectively). "Locked" means that the value is locked at whatever number you enter into the box -- it will not be changed as the crop selection changes. "Unlocked" means that the value is *not* locked to an entry -- it will be changed automatically as the crop selection changes, or as the other dimension is set.

The crop selection, width and height values, and lock icons can be combined in various ways to provide different styles of resizing/cropping:

* If you only wish to resize the image (without any cropping), simply don't make a crop selection, then "lock" the width and/or the height and enter the desired value(s).
* If you only wish to crop the image (without any resizing), "unlock" both width and height, then drag the crop selection around.
* To resize the image to a specific width and have the height be automatically adjusted based on that (and the crop), "lock" the width and "unlock" the height, then enter the desired width into the "Width" box.
* To resize the image to a specific height and have the width be automatically adjusted based on that (and the crop), "lock" the height and "unlock" the width, then enter the desired height into the "Height" box.
* To resize the image to a specific width *and* height, "lock" both width and height and enter the desired values into the boxes.

Note that due to the way that width, height and cropping are set up, it is impossible to "skew" your image! It is, however, possible to set a new image size that is wider and/or taller than the original, which will result in a loss of image quality (the resized image may appear "blocky"). To help you avoid this situation, a warning icon (exclamation point in upside-down triangle) will appear next to the Save button. If you see this icon appear, you will want to reduce the width and/or height, or increase the crop area until the icon disappears.

When you are finished setting your width, height and crop, click the "Save" button to save the resized/cropped image. By default, saving an image always creates a new copy (so that the original remains untouched). If you do not wish to save a copy, and instead want to replace the original image with the resized/cropped one, check the "overwrite" box before saving. NOTE that if you overwrite the original image, it will be permanently deleted from the server! It is recommended that you do not do this -- it's always safer to create a copy because if you mess something up or want to change the resize/crop in the future, you'll want to have the full-size original image still available.

#Developer Notes
There are three different things that the code needs to do:

1. Hijack the "Edit" menu item so that our own image editor window is loaded instead of the Piknik service.
2. Present the image editor to the user and respond to user interaction.
3. Saving the resized/cropped image back to the file system [NOT YET IMPLEMENTED!]

The three files `controller.php`, `js/ccm.filemanager.js`, and `tools/files/edit.php` are responsible for the first task. They all involve fairly simple modifications to core concrete5 behavior (see comments at the top of those files for a little more detail), and require no further mention.

The image editor utilizes the Jcrop jquery plugin ( http://deepliquid.com/content/Jcrop.html ) for the crop selection interface. Two versions of the Jcrop plugin are included (both in the `js` directory) -- one minified and one not. This package currently loads the un-minified version to make debugging easier, but in production you may want to load the minified version instead (although it probably won't make much of a difference).

The `elements/files/edit/image.php` file contains the actual html that is loaded into the editor popup window. The `js/ui.js` file contains all of the UI event handlers for that editor window. The `js/image_editor.js` file is a wrapper around the Jcrop plugin AND handles all of the resizing calculations (because the Jcrop plugin only deals with crop area). It also provides a nice clean API to the UI event handlers.

So the basic chain of command is that the editor window opens, loads the `elements/files/edit/image.php` file which displays the image editor UI and instantiates the ImageEditor object from `js/image_editor.js` (which subsequentally instantiates the Jcrop plugin from `js/jquery.Jcrop.js`), then loads the `ui.js` file which contains the front-end code connecting all of the UI elements to the ImageEditor object (which in turn handles all of our resizing calculations and talks to the Jcrop plugin for us).

The ImageEditor object is directly responsible for the display of the image in the window (including zooming and crop area selection), while the front-end code is directly responsible for the display and input of all other controls in the window. The front-end code never interacts with the displayed image directly -- rather, it asks the ImageEditor object to do things which in turn may or may not affect the displayed image or crop selection area. Similarly, the ImageEditor object never interacts with the other controls in the window directly -- rather, it responds to function calls made by the front-end code (or it calls an event handler in the front-end code) which in turn may or may not affect the value of the controls.

#TODO
* Implement saving! (Both as a copy and overwrite)
* NOTE: When you do save a copy, append the new size and whether it's cropped (e.g. img0323423.jpg -> img0323423_542x287_cropped.jpg)
* In `$('#image_cropper_width_input').change(function() { ... });` handler (in `js/ui.js`), change the alert message so it uses new `ImageEditor.max_allowable_locked_dimension('width')` function to offer useful details to user. Also figure out what suggestions to give (increate/decrease crop area? width or height? increase/decrease other number?). When you figure it out, copy the change down to height handler as well.
* Fix the thing where clicking on the icon immediately shows the other hover (so wait until first OUT event after switching to put the :hover state on it)
* Add yellow fade hilite to display number when it's changed by a locked dimension change
* Always display original image width+height (above or below resized width/height inputs) so degradation warning makes a little more sense
* Refactor lock/auto icons event handlers in `js/ui.js` so it's one element with swapping image (and swapping hover imgs). Hafta merge icon click events as well and look to width_locked() or height_locked() to see which one is "current" based on visibility of textfield, etc.
* Add a help icon that pops up instructions in a lightbox (or something like that)
* Maybe move editing controls to BELOW the image -- the save button makes more sense down there, and the whole interace might make more sense that way if users are familiar with iPhoto.
* Browser testing! (Developed in Firefox/Mac, so that's the only one I know works for now)

Not as important...

* show spinner while loading
* Try to get the image centered in its div. (You have to set up margins on `'#image_cropper_image_container > div.jcrop-holder'`). Don't use jcrop.center.js plugin because if image is too large for the container div, it gets cut off and you can't scroll to it entirely! Instead, manually set the margins for centering ONLY IF the current image's dims are less than the container div (individually for length and width).
* Maybe set the zoom automatically when window is first loaded? (But maybe not because it's a good indication to people when their images are too freaking huge!)
* Maybe have an "Auto-Fit" zoom option? (only applicable if image is larger than container div, not smaller -- Jcrop doesn't allow us to zoom larger than original).
* Maybe add a feature where a dashboard setting indicates the default locked width or height when editor is first opened (so designer could set this to column width of their templates before handing off to client?). !NOTE: only apply the default if it's SMALLER than the original image dimension.
