<?php

namespace Monty;

use Nette;
use Exception;
use Monty\Gallery\Image;
use Monty\Gallery\Action;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\ArrayHash;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\Url;


class Gallery extends BaseControl {

	protected 
		$renderToString,
		$layout = "justified-grid",
		$base,
		$thumbsFolder,
		$thumbsSubFolder,
		$random,
		$images = [],
		$actions = [],
		$imageActions = [],
		$orientation,
		$interval = "false",
		$title,
		$sortable,
		$editable,
		$imagesForm,
		$imageSrcKey,
		$imageThumbKey,
		$offset,
		$limit,
		$page,
		$loadMore = true,
		$loadAll = true,
		$gridRowHeight = 200,
		$imagePicker,
		$multiselect = true;

	public
		$onOrderChange = [],
		$onImageDelete = [],
		$onEditsSave = [],
		$onSelectionDelete = [],
		$imageColClass,
		$srcGenerator = false,
		$actionButtonsVisible = null;

	/** @persistent */
	public $currentImageId;


	public function render($attrs = []) {
		#bdump($this, "gallery control");
		$template = $this->template;

		$template->id = Strings::webalize($this->getParent()->getName()) . "-" . $this->lookupPath();
		$template->lookupPath = $this->lookupPath();

		$template->layout = $layout = isset($attrs["layout"]) ? $attrs["layout"] : $this->layout;

		$random = isset($attrs["random"]) ? isset($attrs["random"]) : $this->random;

		// bdump($this->images, "images");
		$images = $this->images;
		$template->random = $this->random;
		$template->title = $this->title;
		$template->sortable = $this->sortable ? true : false;
		$template->editable = $this->editable ? true : false;
		if ($this->editable && !$this->multiselect) {
			$this->multiselect = true;
		}
		$template->multiselect = $this->multiselect;
		$template->imagesForm = $this->imagesForm;
		$template->actions = $this->getActions();
		$template->isEditsSaveCallback = count($this->onEditsSave) ? true : false;
		$template->imagePicker = $this->imagePicker ? true : false;

		$template->imagesCount = $this->getImagesCount();
		$template->limit = $this->limit ? $this->limit : $this->getImagesCount();
		$template->offset = $this->offset ? $this->offset : 0;
		$template->page = $this->page ? $this->page : 1;
		$template->loadMore = $this->loadMore;
		$template->loadAll = $this->loadAll;

		#$template->imageColClass = $this->imageColClass ? $this->imageColClass : "col-12 col-sm-6 col-md-4 col-lg-3";

		if ($images) {
			if ($this->random) {
				$currentImage = $this->getRandomImage();
			} else {
				if (!$this->currentImageId) {
					$this->currentImageId = 1;
				}
				$currentImage = $images[$this->currentImageId];
			}
			$template->currentImage = $currentImage;
		}

		$template->images = $images;
		$templateFile = __DIR__ . "/templates/gallery.latte";

		$template->base = $this->getBase();
		$template->thumbsFolder = $this->getThumbsFolder();
		$template->interval = $this->interval;
		$template->gridRowHeight = $this->gridRowHeight;
		$template->actionButtonsVisible = $this->actionButtonsVisible;

		if (!$this->renderToString) {
			$template->setFile($templateFile);
			$template->render();
		} else {
			return $template->getLatte()->renderToString($templateFile);
		}
	}


	public function getThumbsFolder() {
		$this->base = $this->getBase();
		if (file_exists($this->base . "/thumbs")) {
			$thumbsFolder = $this->base . "/thumbs";
		} else {
			if (!$this->thumbsFolder && $this->thumbsSubFolder) {
				$thumbsFolder = $this->base . "/" . $this->thumbsSubFolder;
			} else {
				$thumbsFolder = $this->base;
			}
		}

		return $thumbsFolder;
	}

	public function getBase() {
		if ($this->base) {
			return $this->base;
		} else {
			$presenter = $this->getPresenter();
			return $presenter->template->baseUrl;
		}
	}

	public function generateCamelId() {		
		$id = "";
		$parent = $this->getParent()->getName();
		foreach (explode("-", $parent) as $part) {
			$id .= ucfirst($part);
		}
		$lookup = $this->lookupPath();
		foreach (explode("-", $lookup) as $part) {
			$id .= ucfirst($part);
		}

		$id = str_replace(":", "", $id);

		bdump($id, "id");
		return $id;
	}

	public function renderToString($state) {
		$this->renderToString = $state;
	}

	public function setLayout($layout) {
		$this->layout = $layout;

		return $this;
	}

	public function setRandom($state) {
		$this->random = $state;

		return $this;
	}

	public function setBase($base) {
		$this->base = $base;

		return $this;
	}

	public function setThumbsSubFolder($folder) {
		$this->thumbsSubFolder = $folder;

		return $this;
	}

	public function setThumbsFolder($folder) {
		$this->thumbsFolder = $folder;

		return $this;
	}

	public function setSortable($state = true) {
		$this->sortable = $state;

		return $this;
	}

	public function setEditable($state = true) {
		$this->editable = $state;

		return $this;
	}

	public function setLoadAll($state = true) {
		$this->loadAll = $state;

		return $this;
	}

	public function setLoadMore($state = true) {
		$this->loadMore = $state;

		return $this;
	}

	public function setSrcGenerator($state = true) {
		$this->srcGenerator = $state;

		return $this;
	}


	public function addImage($src, $thumb = null) {
		$count = $this->getImagesCount();
		$id = $count + 1;

		/*if ($this->base) {
			$base = $this->base . "/";
			$src = $base . $src;
			$thumb = $thumb ? $base . $thumb : $src;
		}*/

		$thumb = $thumb ? $thumb : $src;

		$image = new Image($this, $src, $thumb, $id);
		$image->setParent($this);

		$this->images[$id] = $image;

		return $image;
	}

	public function addImages($images) {
		#$this->addImage($this->imgSrcCol, $this->thumb)
	}

	public function fromFolder($folder) {
		foreach (\Monty\Tools\FileSystem::getImagesFromFolder($folder) as $key => $file) {
			#bdump($file, "file");
			$image = $this->addImage($file->fullname, $file->path);
		}
	}

	public function getImages() {
		return $this->images;
	}

	public function getActions() {
		return $this->actions;
	}

	public function getImageActions() {
		return $this->imageActions;
	}

	public function setOrientation($orientation) {
		$this->orientation = $orientation;

		return $this;
	}

	public function setImageColClass($class) {
		$this->imageColClass = $class;

		return $this;
	}

	public function onlyLandscape() {
		$this->orientation = "landscape";	

		return $this;
	}

	public function onlyPortrait() {
		$this->orientation = "portrait";	

		return $this;
	}

	public function getRandomImage($notId = false) {
		bdump($notId, "not id");
		#bdump($this->images, "images");
		#bdump($this->currentImageId, "current image id");
		$random = rand(1, $this->getImagesCount());
		$this->currentImageId = $random;

		#bdump($random, "random");

		$image = $this->images[$random];
		#bdump($image, "image");

		if ($this->orientation) {
			if ($image->orientation == $this->orientation) {
				if ($notId && $image->id == $notId) {
					return $this->getRandomImage($notId);
				} else {
					return $image;
				}
			} else {
				return $this->getRandomImage($notId);
			}
		} else {
			return $image;
		}
	}

	public function getImagesCount() {
		return count($this->images);
	}

	public function setInterval($interval) {
		$this->interval = $interval;

		return $this;
	}

	public function setDefaultImage($id) {
		$this->currentImageId = $id;

		return $this;
	}

	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	public function setLimit($limit) {
		$this->limit = $limit;

		return $this;
	}

	public function setGridRowHeight($height) {
		$this->gridRowHeight = $height;

		return $this;
	}

	public function setRowHeight($h) {
		$this->setGridRowHeight($h);

		return $this;
	}

	public function setImagePicker($state = true) {
		$this->imagePicker = $state;

		return $this;
	}

	public function setMultiselect($state = true) {
		$this->multiselect = $state;

		return $this;
	}

	public function setActionButtonsVisible($state = true) {
		$this->actionButtonsVisible = $state;

		return $this;
	}

	public function createComponentImageForm() {
		return new Multiplier(function($id) {
			$image = $this->getImage($id);

			$form = new Form;
			if ($image) {
				$form->addHidden("id", $image->imageId);
			}
			$addedForm = $this->getImagesForm();
			$this->cloneForm($form, $addedForm);

			$form->setDefaults($image->getFormDefaults());

			return $form;
		});
	}

	protected function cloneForm($multiplierForm, $imageForm) {
		foreach ($imageForm->getComponents() as $inputId => $input) {
			//bdump($input, "input");
			#$id = $inputsType == "fileInputs" ?  "file_" . $input->name : $input->name;

			$clonedInput = clone $input;
			$clonedInput->setParent($multiplierForm->getParent());
			$multiplierForm[$input->name] = $clonedInput;
		}

		$multiplierForm->onSuccess = $imageForm->onSuccess;
	}

	public function addImagesForm($form) {
		$this->imagesForm = $form;

		return $this;
	}

	public function addImageForm($form) {
		$this->imagesForm = $form;

		return $this;
	}

	public function addAction($name, $label, $link) {
		$action = new Action($name, $label, $link);
		$action->setParent($this);

		$this->actions[] = $action;

		return $action;
	}

	public function addImageAction($name, $label, $link, $linkAttr = null) {
		$action = new Action($name, $label, $link, $linkAttr);
		$action->setParent($this);

		$this->imageActions[] = $action;

		return $action;
	}

	public function getImagesForm() {
		return $this->imagesForm;
	}

	public function getImage($id) {
		return isset($this->images[$id]) ? $this->images[$id] : null;
	}

	public function getLayout() {
		return $this->layout;
	}


	public function handleGetRandomImage() {
		bdump($this->currentImageId, "currentImageId");
		$this->template->randomImage = $this->getRandomImage($this->currentImageId);
		$this->redrawControl("gallery");
	}

	public function handleNextImage($currentImageId) {
		bdump($currentImageId, "currentImageId");
		$this->random = false;
		#bdump($this->images, "images");
		$count = $this->getImagesCount();
		$next = $currentImageId + 1;
		if ($next > $count) {
			$next = 1;
		}

		bdump($next, "next");
		$this->currentImageId = $next;
		$this->redrawControl("gallery");
	}

	public function handleLoadMore($page) {
		bdump($page, "page");
		$this->page = $page;
		$this->offset = ($this->page - 1) * $this->limit;

		$this->template->appended = true;

		$this->redrawControl("galleryWrap");
		$this->redrawControl("galleryImages");
		$this->redrawControl("loadMoreButtons");
	}

	public function handleLoadAll($page) {
		$this->page = $page;
		$this->offset = ($this->page - 1) * $this->limit;
		$this->limit = $this->getImagesCount() - $this->offset;

		$this->template->appended = true;

		$this->redrawControl("galleryWrap");
		$this->redrawControl("galleryImages");
		$this->redrawControl("loadMoreButtons");
	}

	public function handleOrderChange($item, $itemPrev, $itemNext) {
		/*bdump($item, "item");
		bdump($itemPrev, "itemPrev");
		bdump($itemNext, "itemNext");*/

		foreach ($this->onOrderChange as $callback) {
			$callback($item, $itemPrev, $itemNext);
		}

		$this->reloadComponent();
	}

	public function handleEditsSave($forms) {
		bdump("handleEditsSave");
		bdump($forms, "forms");

		/*if (strpos($forms, "[") !== false) {
			bdump("je tam");
			$forms = str_replace("[]", "", $forms);
			bdump($forms, "formsReplaced");
		}*/
		$forms = json_decode($forms, true);
		bdump($forms, "forms");

		#bdump($this->imagesForm->onSuccess, "imagesForm  onsucces");

		foreach ($forms as $id => $vals) {
			foreach ($this->imagesForm->onSuccess as $callback) {
				// $vals = ArrayHash::from($vals);

				$data = [];
				foreach ($vals as $par => $val) {
					bdump(strpos($par, "[]"), $par . " key");
					$key = strpos($par, "[]") !== false ? str_replace("[]", "", $par) : $par;
					$data[$key] = $val;
				}

				$data = ArrayHash::from($data);
				$callback(null, $data);
			}
		}


		if ($this->onEditsSave) foreach ($this->onEditsSave as $callback) {
			$callback(ArrayHash::from($forms));
		}

		$this->reloadComponent();

		// bdump($this->getName(), "name");
		// $presenter = $this->getPresenter();
		// $name = $this->getName();
		// $presenter->removeComponent($presenter->getComponent($name));
	}

	public function handleSelectionDelete(array $images) {
		bdump($images, "images - control");
		foreach ($this->onSelectionDelete as $callback) {
			$callback($images);
		}

		#$this->reloadComponent();
	}

	public function reloadComponent() {
		bdump("reloadComponent...");
		$presenter = $this->getPresenter();
		$presenter->removeComponent($presenter[$this->getName()]);
	}

	public function reload() {
		$this->reloadComponent();
	}

	public function hasImages()
	{
		return count($this->images) ? true : false;
	}

}