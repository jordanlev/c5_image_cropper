<?php
//Overrides concrete/tools/files/edit.php
//The only change is calling Loader::packageElement() instead of Loader::element() on the last line

defined('C5_EXECUTE') or die("Access Denied.");
$u = new User();
$form = Loader::helper('form');

$ci = Loader::helper('concrete/urls');
$f = File::getByID($_REQUEST['fID']);
$fv = $f->getApprovedVersion();

$fp = new Permissions($f);
if (!$fp->canWrite()) {
	die(_("Access Denied."));
}

$to = $fv->getTypeObject();
if ($to->getPackageHandle() != '') {
	Loader::packageElement('files/edit/' . $to->getEditor(), $to->getPackageHandle(), array('fv' => $fv));
} else {
	Loader::packageElement('files/edit/' . $to->getEditor(), 'image_cropper', array('fv' => $fv));
}