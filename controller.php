<?php   
defined('C5_EXECUTE') or die(_("Access Denied."));

class ImageCropperPackage extends Package {
	
	protected $pkgHandle = 'image_cropper';
	protected $appVersionRequired = '5.4.1';
	protected $pkgVersion = '1.0';
	
	public function getPackageName() {
		return t("Image Cropper"); 
	}	
	
	public function getPackageDescription() {
		return t('Adds image cropping functionality to the file manager (click on image thumbnail, choose "Edit")');
	}



	public function on_start() {
		Events::extend('on_page_view', 'ImageCropperPackage', 'on_page_view', 'packages/image_cropper/controller.php');
	}

	public function on_page_view() {
		//Include js and css for image cropper IF user is logged in and has editing capabilities
		// (don't just check for edit mode because user could be in the file manager
		//  via the dashboard OR the front-end sans edit mode [if they have file manager toolbar button enabled]).
		$u = new User();
		$cp = new Permissions(Page::getCurrentPage());
		if ($u->isLoggedIn() && $cp->canWrite()) {
			$html = Loader::helper('html');				
			$view = View::getInstance();
			$view->addHeaderItem($html->css(BASE_URL.DIR_REL.'/packages/image_cropper/css/jquery.Jcrop.css'), 'CONTROLLER');
			$view->addHeaderItem($html->css(BASE_URL.DIR_REL.'/packages/image_cropper/css/image_cropper.css'), 'CONTROLLER');
			$view->addHeaderItem($html->javascript(BASE_URL.DIR_REL.'/packages/image_cropper/js/ccm.filemanager.js'), 'CONTROLLER');
			//Note that we passed the 'CONTROLLER' namespace to addHeaderItem() so that it adds our items AFTER the core items
		}
	}


	public function install() {
		$pkg = parent::install();
	}

}
