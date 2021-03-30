<?php

namespace Monty;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;


class Cropper extends BaseControl {

	const CROPPER_TEMP_FOLDER = __DIR__ . "/temp/";

	public $tempFolder;
	public $destinationPath;
	public $prefix;
	public $ratio = null;
	public $imageMaxWidth = 500;
	public $imageMaxHeight = null;
	public $onCropp = [];




	public function render() {
		parent::render();
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/cropper.latte");

		$template->cropperTempFolder = self::CROPPER_TEMP_FOLDER;
		if (!isset($template->image)) $template->image = null;
		$template->ratio = $this->ratio;
		// if (!$this->destinationPath) {
		// 	throw new CropperDestinationPathException("You have to set destination path for saved images");
		// }
		

		$template->render();
	}

	public function setTempFolder($tempPath) {
		$this->tempFolder = $tempFolder;

		return $this;
	}

	public function setDestinationPath($path) {
		$this->destinationPath = $path;

		return $this;
	}

	public function setPrefix($prefix) {
		$this->prefix = $prefix;

		return $this;
	}

	public function setRatio($x, $y) {
		$this->ratio = [$x, $y];

		return $this;
	}

	public function setMaxSizes($width, $height) {
		$this->imageMaxWidth = $width;
		$this->imageMaxHeight = $height;

		return $this;
	}

	public function getRatio() {
		if (!$this->ratio) {
			return null;
		} else {
			return $this->ratio[0] / $this->ratio[1];
		}
	}


	public function createComponentImageUploadForm() {
		$form = new Form;

		$form->setMethod("post");
		$form->addUpload("image")->setRequired();
		$form->addSubmit("upload");

		$form->onSuccess[] = function($form, $vals) {
			\Tracy\Debugger::barDump("image loaded...");
			$fileName = $this->uploadTempImage($vals);

			//$this["bubbleForm"]["image"]->setValue($fileName);

			$folder = self::CROPPER_TEMP_FOLDER;

			$content = file_get_contents($folder . $fileName);
			$image = base64_encode($content);

			$this->template->image = $image;
			$this->template->imageName = $fileName;
			$this->redrawControl("cropper");
		};

		return $form;
	}

	public function createComponentCropperForm() {
		$form = new Form;

		$form->addHidden("image");
		$form->addHidden("image_data");

		$form->addSubmit("save");

		$form->onSuccess[] = function($form, $vals) {
			#\Tracy\Debugger::barDump($vals, "vals");

			$imageData = json_decode($vals->image_data);
			\Tracy\Debugger::barDump($imageData, "imageData");

			$path = $this->tempFolder ? $this->tempFolder : self::CROPPER_TEMP_FOLDER;
			$path = $path . "/" . $vals->image;
			$image = Image::fromFile($path);
			$image->crop((int)$imageData->x, (int)$imageData->y, (int)$imageData->width, (int)$imageData->height);
			$image->resize($this->imageMaxWidth, $this->imageMaxHeight);
			$destPath = $this->destinationPath ? $this->destinationPath : self::CROPPER_TEMP_FOLDER;
			$destPath .= $vals->image;
			$imgName = $image->save($destPath);
			#\Tracy\Debugger::barDump($imgName, "imgName");

			
			$vals["path"] = $destPath;

			foreach ($this->onCropp as $callback) {
				$callback($vals);
			}
		};

		return $form;
	}

	public function uploadTempImage($vals) {
		\Tracy\Debugger::barDump($vals, "vals");
		$folder = self::CROPPER_TEMP_FOLDER;
		FileSystem::createDir($folder);

		$file = $vals->image;
		$prefix = $this->prefix ? $this->prefix . "_" : null;
		\Tracy\Debugger::barDump($file, "image");

		//$imageName = $parent . "_" . $file->getName() . "_" . uniqid() . ".jpg";
		$imageName = \Monty\Helper::generateUniqueFileName($file, $prefix);
		$image = Image::fromFile($file);
		$image->resize(1920, null);
		$image->save($folder . $imageName);

		return $imageName;
	}

}

class CropperTempFolderException extends \Exception {

}

class CropperDestinationPathException extends \Exception {

}