<?php

namespace App\CoreModule\Traits;


trait FilesTrait {

    /** @var \App\Managers\FilesManager @inject */
    public $FilesManager;
    

	public function getThumb($fileId) {
		if ($src = $this->FilesManager->getImageThumbSrc($fileId)) {
			return $src;
		} else if ($src = $this->SettingsManager->getSetting("item_no_image")) {
			return $src;
		} else {
			return "dist/images/item-no-image.jpg";
		}
	}

	public function isImage($id) {
		// \Tracy\Debugger::barDump("is image");
		return $this->FilesManager->isImage($id);
	}

}