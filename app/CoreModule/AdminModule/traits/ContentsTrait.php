<?php

namespace App\CoreModule\AdminModule\Traits;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Monty\Gallery;
use Monty\DataGrid;
use Monty\Dropzone;
use Nette\Database\Table\Selection;


trait ContentsTrait
{
	protected $tempId;
	protected $lastId;
	protected $id;



	public function saveContent($vals) {
		\Tracy\Debugger::barDump($vals, "saveContent - vals");
		$id = $this->ContentsManager->contentSave($vals);
		$content = $this->ContentsManager->getContent($id);

		if (!empty($vals->image) && $vals->image->hasFile()) {
			// \Tracy\Debugger::barDump("saving image");
			$fileId = $this->FilesManager->uploadImage($vals->image, $this->getUser()->id);
			$content->update(["image" => $fileId]);
		}
		if (!empty($vals->header_image) && $vals->header_image->hasFile()) {
			$fileId = $this->FilesManager->uploadImage($vals->header_image, $this->getUser()->id);
			$content->update(["header_image" => $fileId]);
		}
		if (!empty($vals->file) && $vals->file->hasFile()) {
			$fileId = $this->FilesManager->uploadFile($vals->file, $this->getUser()->id);
			$content->update(["file" => $fileId]);
		}
		if (!empty($vals->attachments)) {
			foreach ($vals->attachments as $att) {
				$file = $this->FilesManager->uploadFile($att, true);
				$this->ContentsManager->savecontentAttachment($id, $file);
			}
		}

		$tempFiles = $this->FilesManager->getTempFilesById($this->tempId);
		foreach ($tempFiles as $tempFile) {
			$this->ContentsManager->contentImageSave($id, $tempFile->file);
			$this->FilesManager->getTempFile($tempFile)->delete();
		}

		$alias = $this->AliasesManager->saveAlias("contents", $content);
		\Tracy\Debugger::barDump($alias, "alias");
		$content->update(["alias" => $alias]);

		$this->lastId = $id;

		return $id;
	}

	public function iamContentEditor($id, \Nette\Security\User $user = null) {
		$user = $user ? $user : $this->getUser();

		if (!$user->isLoggedIn()) return false;

		$content = $this->getContent($id);
		$type = $content->ref("type")->short;
		$role = "admin";

		// \Tracy\Debugger::barDump(count($this->getContentsEditors()->where("content", $id)->where("user", $user->id)), "count");

		if (!$user->isInRole("superadmin") && ((
			$content->user != $user->id &&
			!count($this->getContentsEditors()->where("content", $id)->where("user", $user->id))))) {
			return false;
		} else {
			return true;
		}
	}
	
	public function getContent($id) {
		return $this->ContentsManager->getContent($id);
	}

	public function createComponentGalleryImagesDropzone() {
		$dropzone = new Dropzone;

		$dropzone->setAcceptedFiles(["jpg", "png", "jpeg"]);

		$inputs = $dropzone["inputs"];
		// $inputs->addText("author", "Autor");
		// $inputs->addText("watermark", "Vodotisk");
		// $inputs->addMultiSelect("tags", "Tagy", $tags);

		$dropzone->onUpload[] = function($file, $vals) {
			$fileId = $this->FilesManager->uploadImage($file, $this->getUser()->id);
			if ($this->getParameter("id")) {
				$this->ContentsManager->contentImageSave($this->getParameter("id"), $fileId);
			} else {
				$this->FilesManager->saveTempFile($fileId, $this->tempId);
			}
			// if (!empty($vals->tags)) $this->ContentsMa->galleryImageTagsSave($imageId, $vals->tags);
		};

		$dropzone->onUploadComplete[] = function() {
			$this->redrawControl("images");
		};

		return $dropzone;
	}

	public function createComponentImagesGallery() {
		$gal = new Gallery;

		$gal->setLayout("grid");
		$gal->setEditable();
		$gal->addImageAction("edit", null, "contentImageForm!")->setIcon("fas fa-pencil");

		$gal->onSelectionDelete[] = function($files) {
			\Tracy\Debugger::barDump($files, "files");
			foreach ($files as $file) {
				if ($this->getParameter("id")) {
					$this->ContentsManager->getContentImage($file)->delete();
				} else {
					$this->FilesManager->getTempFile($file)->delete();
				}
			}

			$this->redrawControl("images");
		};

		$gal->onOrderChange[] = function($item, $itemPrev, $itemNext) {
			\Tracy\Debugger::barDump("change order");
			\Tracy\Debugger::barDump($item, "item");

			$image = $this->ContentsManager->getContentImage($item);
			$images = $this->ContentsManager->getContentImages($image->content);
			\Tracy\Debugger::barDump($images->fetchAll(), "images");

			$this->ContentsManager->changeItemOrder($images, $item, $itemNext, $itemPrev);
			$this->ContentsManager->itemsReorder((clone $images));

			// $item = $this->ContentsManager->getContentImage($item);
			// $itemPrev = $this->ContentsManager->getContentImage($itemPrev);
			// $itemNext = $this->ContentsManager->getContentImage($itemNext);

			// $items = $this->ContentsManager->getContentsImages($item->content);
			// $this->ContentsManager->itemOrderChange($item, $itemPrev, $itemNext, $items);

			$this->redrawControl("images");
		};

		return $gal;
	}

	public function createComponentContentImageForm() {
		$form = new Form;

		$form->addHidden("id");
		$form->addText("title");
		$form->addTextArea("desc");
		$form->addSubmit("submit");

		$form->onSuccess[] = function($form, $vals) {
			$this->ContentsManager->saveContentImage($vals);
			// $this->redrawControl("modal");
			// $this->payload->modal = "hide";
			$this->modal("hide");
		};

		return $form;
	}

	public function createComponentHeaderImageCropper() {
		$cropper = new \Monty\Cropper;

		$cropper->setMaxSizes(1920, null);
		// $cropper->setRatio(1, 0.156);

		$cropper->onCropp[] = function($vals) {
			\Tracy\Debugger::barDump($vals, "headerImage cropper vals");
			$fileId = $this->FilesManager->uploadImage($vals->path, $this->getUser()->id);
			// $this->UsersManager->getUser($user->id)->update(["image" => $vals->image]);
			$this->ContentsManager->getContent($this->id)->update(["header_image" => $fileId]);
			$this->redrawControl("headerImageCropper");
		};

		return $cropper;
	}

	public function getContentCustomData($id) {
		return $this->ContentsManager->getContentCustomData($id);
	}

}