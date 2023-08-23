<?php

namespace Monty\FileBrowser;

use Nette;

use Nette\Utils\ArrayHash;
use Nette\Utils\Html;


class File {

	use Nette\SmartObject;

	public
		$fileBrowser,
		$id,
		$label,
		$path,
		$src,
		$fileInfo;


	public function __construct($fileBrowser, $id, $path, $src, $label = null) {
		$this->fileBrowser = $fileBrowser;
		$this->id = $id;
		$this->path = $path;
		$this->src = $src;
		$this->label = $label;

		$this->fileInfo = ArrayHash::from(pathinfo($path));

		return $this;
	}

	public function addSrc($src) {
		$this->src = $src;

		return $this;
	}

	public function addPath($path) {
		$this->path = $path;

		return $this;
	}

	public function addLabel($label) {
		$this->label = $label;

		return $this;
	}

	public function getBaseUrl() {
		$url = "";
		$url .= $this->getBase() . "/";

		//bdump("getBaseUrl - url", $url);

		return $url;
	}

	public function getUrl() {
		// bdump($this->fileInfo, "fileInfo");
		// bdump(new \SplFileInfo($this->path), "splFileInfo");
		$url = $this->getBaseUrl();
		$url .= $this->src;

		// bdump($url, "url");

		return $url;
	}

	public function getThumbUrl() {
		return $this->getThumbFolder() ? $this->getBaseUrl() . $this->getThumbSrc() : $this->getUrl();
	}

	public function getBase() {
		return $this->fileBrowser->base;
	}

	public function getThumbFolder() {
		return $this->fileBrowser->thumbsSubfolder;
	}

	public function getFilename() {
		return $this->getBasename();
	}

	public function getThumbSrc() {
		#bdump($this->src, "src");
		$srcArr = explode("/", $this->src);
		array_pop($srcArr);
		$src = implode("/", $srcArr) . "/";
		$src .= $this->getThumbFolder() ? $this->getThumbFolder() . "/" : null;
		$src .= $this->getFilename();

		return $src;
	}

	public function getSrc() {
		return $this->getBase() . "/" . $this->src;
	}

	public function isImage() {
		$imageSize = getimagesize($this->path);
		#bdump($imageSize, "imageSize");

		if (isset($imageSize[0])) {
			return true;
		} else {
			return false;
		}
	}

	public function getExt() {
		return $this->fileInfo->extension;
	}

	public function getBasename() {
		#bdump($this->fileInfo, "fileInfo");
		return $this->fileInfo->basename;
	}

	public function getThumb() {
		if ($this->isImage()) {
			$div = Html::el("div");

			$style = [];
			$style[] = "background-image:url('" . $this->getThumbUrl() . "')";
			$div->style(implode(";", $style));

			$div->class[] = "img-fluid file-thumb-bg";

			return $div;
		} else {
			return $this->getExt();
		}
	}
}