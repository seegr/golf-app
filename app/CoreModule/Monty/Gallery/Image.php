<?php

namespace Monty\Gallery;

use Nette\Utils\ArrayHash;
use SplFileInfo as FileInfo;
use Nette\Utils\Image as NetteImage;

use Monty\Gallery\Action;


class Image {

	use \Nette\SmartObject;

	protected $gallery;

	public
		$id,
		$imageId,
		$name,
		$title,
		$alt,
		$desc,
		$path,
		$fileInfo,
		$attributes = [],
		$orientation,
		$order,
		$src,
		$thumb,
		$form,
		$parent,
		$class = [],
		$formDefaults = [],
		$actioins = [];


	public function __construct($gallery, $src, $thumb, $id) {
		$this->gallery = $gallery;
		$this->id = $id;
		$this->src = $src;
		$this->thumb = $thumb;

		$this->fileInfo = $this->getFileInfo();
		$this->getImageData();

		return $this;
	}

	public function setAttribute($attr, $val) {
		$this->attributes[$attr] = $val;
	}

	public function setAttributes($attrs) {
		foreach ($attrs as $attr => $val) {
			$this->setAttribute($attr, $val);
		}
	}

	public function setSrc($src) {
		$this->src = $src;

		return $this;
	}

	public function setThumb($thumb) {
		$this->thumb = $thumb;

		return $this;
	}

	public function setOrder($order) {
		$this->order = $order;

		return $this;
	}

	public function setId($id) {
		$this->imageId = $id;

		$this->attributes["data-id"] = $id;

		$this->gallery->actionButtonsVisible = $this->gallery->actionButtonsVisible !== false ? true : false;

		return $this;
	}

	public function setAlt($alt) {
		$this->alt = $alt;

		return $this;
	}

	public function setLabel($label) {
		$this->setTitle($label);

		return $this;
	}

	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	public function setDesc($desc) {
		$this->desc = $desc;

		return $this;
	}

	public function setFormDefaults($defaults) {
		$this->formDefaults = $defaults;

		return $this;
	} 

	public function setParent($parent) {
		$this->parent = $parent;

		return $this;
	}

	public function addForm($form) {
		$this->form = $form;

		return $this;
	}

	public function addClass($class) {
		$this->class[] = $class;

		return $this;
	}

	public function getFileInfo() {
		return ArrayHash::from(pathinfo($this->src));
	}

	public function getImageData() {
		if (!file_exists($this->src)) return;

		list($width, $height) = getimagesize($this->src);

		if ($width > $height) {
		    $this->orientation = "landscape";
		} else {
		    $this->orientation = "portrait";
		}
	}

	public function getClass() {
		if ($this->class) {
			return implode(" ", $this->class);
		} elseif ($this->gallery->imageColClass) {
			return $this->gallery->imageColClass;
		} else {
			if ($this->gallery->getLayout() == "grid") {
				return "col-12 col-sm-6 col-md-4 col-lg-3";
			} else {
				return "";
			}
		}
	}

	public function getSrc($type = "src") {
		#\Tracy\Debugger::barDump("getSrc");

		#if (!$this->gallery->srcGenerator) return $this->$type;

		if (strpos($this->$type, "http") !== false) {
			return $this->$type;
		} else {
			#\Tracy\Debugger::barDump($this->src, "src");
			$base = $this->gallery->getBase();
			#\Tracy\Debugger::barDump($base, "base");

			return $base . "/" . $this->$type;
		}
	}

	public function getAlt() {
		if ($this->alt) {
			return $this->alt;
		} else {
			return "Obrázek - " . $this->id;
		}
	}

	public function getThumb() {
		return $this->thumb ? $this->thumb : $this->src;
	}

	public function getForm() {
		return $this->form;

		if ($this->form) {
			return $this->form;
		} else {
			return $this->gallery->imagesForm;
		}
	}

	public function getFormDefaults() {
		return $this->formDefaults;
	}

	public function getAttrs() {
		// n:attr="title => $image->title, alt => $image->getAlt(), data-desc => $image->desc"
		// n:attr="data-id => $image->imageId"
		// $attrs = [
		// 	"title" => $this->title,
		// 	"alt" => $this->alt,
		// 	"desc" => $this->desc
		// ];

		// $attrs = $attrs + $this->attributes;

		\Tracy\Debugger::barDump($this->attributes, "image attributes");
		return $this->attributes;
	}

	public function getActions() {
		$actions = $this->parent->getImageActions();

		// foreach ($actions->setParent)

		return $actions;
	}

	public function addAction($name, $label, $link) {
		\Tracy\Debugger::barDump("addAction");
		$action = new Action($name, $label, $link);

		return $this;
	}

	public function addAttributes(array $attrs) {
		$this->attributes = $this->attributes + $attrs;

		return $this;
	}

}