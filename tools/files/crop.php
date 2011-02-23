<?php 

defined('C5_EXECUTE') or die("Access Denied.");
$u = new User();
$js = Loader::helper('json');
$valt = Loader::helper('validation/token');
$ich = Loader::helper('image_cropper', 'image_cropper');
Loader::library("file/importer");


$fp = FilePermissions::getGlobal();
if (!$fp->canAddFiles()) {
	die(_("Unable to add files."));
}

$error = '';

if (isset($_POST['fID'])) {
	$f = File::getByID($_REQUEST['fID']);
	$fi = new FileImporter();
	
	$is_overwrite = isset($_POST['overwrite']) && (bool)$_POST['overwrite'];
	
	//Validate
	if (!$valt->validate('upload')) {
		$error = $valt->getErrorMessage();
	} else if (!validate_dimensions()) {
		$error = t('Invalid width, height, or crop selection');
	} else if (!is_object($f) || $f->isError()) {
		$error = t('Invalid file.');
	} else if (!$fp->canAddFileType($f->getExtension())) {
		$error = t('You do not have permission to perform this action.');
	} else if ($is_overwrite) {
		$new_fv = $fi->import($f->getPath(), false, $f); //Pass the original file object to create a new version of the same file
	} else {
		$new_fv = $fi->import($f->getPath(), false); //Don't pass original file object so a brand new file will be created
	}
	
	if (empty($error) && !($new_fv instanceof FileVersion)) {
		$error = FileImporter::getErrorMessage($new_fv);
	} else {
		set_ocid($resp, $is_overwrite);
		$ich->edit($new_fv->getPath(), $_POST['crop_x'], $_POST['crop_y'], $_POST['crop_w'], $_POST['crop_h'], $_POST['target_w'], $_POST['target_h']);
	}
	
	//Send json response
	$obj = new stdClass;
	$obj->message = $error;
	$obj->error = empty($error) ? 0 : 1;
	$obj->fID = empty($error) ? $new_fv->getFileID() : '';
	print $js->encode($obj);
	exit;
}

function validate_dimensions() {
	$val = Loader::helper('validation/form');
	$val->setData($_POST);
	$fields = array('crop_x', 'crop_y', 'crop_w', 'crop_h', 'target_w', 'target_h');
	foreach ($fields as $field) {
		$val->addRequired($field, null, ValidationFormHelper::VALID_INTEGER_REQUIRED);
	}
	return $val->test();
}

function set_ocid(&$fileVersion, $is_overwrite) {
	if (!$is_overwrite && isset($_POST['ocID'])) {
		//ocID is the collection ID of the page that the image was first added to.
		//We only want to set this if the image was copied (not overwritten).
		// (If it's not available in $_POST, that's okay too -- it will just be empty [saved to db as 0], no big whoop)
		$f = $fileVersion->getFile();
		$f->setOriginalPage($_POST['ocID']);
	}
}