<?php

namespace Monty;

use Nette;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\Random;
use Nette\Utils\ArrayHash;
use SplFileInfo;

use Monty\Watermakr;


class Image extends \Nette\Utils\Image {	

	use \Nette\SmartObject;

	protected
		$file,
		$folder,
		$format,
		$originalImage,
		$image,
		$imageSize,
		$id,
		$hash_id,
		$name,
		$name_full,
		$label,
		$description,
		$ext,
		$temp,
		$user,
		$session,
		$uploaded,
		$expiration,
		$x, $y,
		$maxWidth = 1920,
		$maxHeight,
		$thumbs = [],
		$watermarks = [];


	/*public function __construct($file) {
		$image = Img::fromFile($file);

		$this->image = $image;

		return $this;
	}*/

	public function format($format) {
		$this->format = $format;

		return $this;
	}

	/*public function resize($x, $y) {
		$this->$x = $x;
		$this->y = $y;

		return $this;
	}*/

	public function thumb($size) {
		if (is_array($size)) {
			$this->thumbs = $size;
		} else {
			$this->thumbs[] = $size;
		}

		return $this;
	}

	public function thumbs($size) {
		$this->thumb($size);
	}

	public function watermark($watermark) {
		$this->watermark = $watermark;

		return $this;
	}

	public function author($author) {
		$this->author = $author;

		return $this;
	}

	public function folder($folder) {
		$this->folder = $folder;
	}

	public function name($name) {
		$this->name = $name;
	}

	public function width($width) {
		$this->maxWidth = $width;
	}

	public function getFolder($thumb = null) {
		if (!$this->folder) {
			throw new \Exception("You have to set folder");
		} else {			
			$folder = $this->folder;
			$folder .= $thumb ? "/thumbs/" . $thumb . "/" : null;

			return $folder;
		}
	}

	public function getName() {
		if (!$this->name) {
			#throw new Exception("you have to set name");
			$name = Random::generate(20, "a-z");
			$filePath = $this->getFilePath($name);

			if (file_exists($filePath)) {
				$this->getName();
			} else {
				return $name;
			}
		} else {			
			return $this->name;
		}
	}

	public function getFormat() {
		if (!$this->format) {
			return "jpg";
		} else {
			return $this->format;
		}
	}

	public function getType() {
		$format = $this->getFormat();

		switch ($format) {
			case "jpg":
			case "jpeg":
				return self::JPEG;
			break;

			case "png":
				return self::PNG;
			break;

			case "gif":
				return self::GIF;
			break;
		}
	}

	public function getFilePath($name = null, int $thumb = null) {
		$name = $name ? $name : $this->getName();
		$folder = $this->getFolder($thumb);
		$format = $this->getFormat();

		$filePath = $folder . "/";
		//$filePath .= $thumb ? "thumbs/" . $thumb . "/" : null;
		$filePath .= $name;
		//$filePath .= $thumb ? "_" . $thumb : null;
		$filePath .= "." . $format;

		#bdump($filePath, "filePath");

		return $filePath;
	}

	public function addText($text) {
		bdump($this->width, "width");
		bdump($this->height, "height");

		$watermark = new Watermark($text);

		$this->watermarks[] = $watermark;

		return $watermark;
	}

	public function save($name = null, $quality = 100, $type = null) {
		/*$name = $this->getName();
		$folder = $this->getFolder();
		$format = $this->getFormat();
		$type = $this->getType();*/

		if (!$name) {
			$name = $this->getName();
		}
		$folder = $this->getFolder();

		$filePath = $this->getFilePath($name);

		FileSystem::createDir($folder);

		#bdump($this, "image");

		// if ($this->thumbs) {
		// 	$originalImage = clone($this);
		// }

		if ($this->maxWidth && $this->width > $this->maxWidth) {
			bdump($this->width, "width");
			bdump($this->height, "height");
			$this->resize($this->maxWidth, null);
		}

		// bdump($filePath, "filePath");
		// bdump($quality, "quality");
		// bdump($type, "type");
		if (!$this->thumbs) {
			$this->placeWatermarks();
			parent::save($filePath, $quality, $type);
			return $this->getFileData($filePath);
		} else {
			if ($this->thumbs) {
				$originalImage = clone $this;
				$this->placeWatermarks();
				parent::save($filePath, $quality, $type);
				
				$thumbs = [];

				foreach ($this->thumbs as $thumb) {
					bdump($thumb, "thumb");
					$thumbImage = $originalImage;
					$thumbImage->resize($thumb, null);
					$thumbImage->thumbs([]);
					#$thumbName = $name . "_" . $thumb;
					$thumbPath = $thumbImage->getFilePath($name, $thumb);

					bdump($thumbPath, "filePath");
					bdump($quality, "quality");
					bdump($type, "type");

					$thumbFolder = $this->getFolder($thumb);
					FileSystem::createDir($thumbFolder);

					$thumbImage->parentSave($thumbPath, $quality * 0.8, $type);

					$thumbs[] = $this->getFileData($thumbPath) + ["width" => $thumb];
				}
			}

			/*return ArrayHash::from([
				"original" => $this->getFileData($filePath),
				"thumbs" => $thumbs
			]);*/

			bdump($filePath, "filePath");
			return ArrayHash::from($this->getFileData($filePath));
		}

	}

	public function placeWatermarks() {
		foreach ($this->watermarks as $watermark) {
			$watermark->canvas($this);
			$img = $watermark->getImage();
			$pos = $this->getWatermarkPosition($watermark);

			$this->place($img, $pos[0], $pos[1]);
		}
	}

	public function getFileData($filePath) {
		$pathinfo = pathinfo($filePath);
		$splInfo = new SplFileInfo($filePath);

		$data = [
			"file" => $splInfo,
			"name" => $pathinfo["basename"],
			"path" => $splInfo->getRealPath(),
			"ext" => $pathinfo["extension"]
		];

		return $data;
	}

	public function parentSave($filePath, $quality = null, $type = null) {
		parent::save($filePath, $quality, $type);
	}

	public function getWatermarkPosition($watermark) {
		#bdump("getWatermarkPosition...");

		$imageOrigin = [$this->width / 2, $this->height / 2];
		$watermarkOrigin = [$watermark->width / 2, $watermark->height / 2];
		$margin = $this->width * 0.02;

		switch ($watermark->getPosition()) {
			case "topLeft":
				$pos = [
					0 + $margin,
					0 + $margin 
				];
			break;

			case "topCenter":
				$pos = [
					($imageOrigin[0]) - ($watermarkOrigin[0]),
					0 + $margin
				];
			break;

			case "topRight":
				$pos = [
					$this->width - $watermark->width - $margin,
					0 + $margin
				];
			break;

			case "left":
				$pos = [
					0 + $margin,
					($imageOrigin[1]) - ($watermarkOrigin[1])
				];
			break;

			default:
			case "center";
				$pos = [
					($imageOrigin[0]) - ($watermarkOrigin[0]),
					($imageOrigin[1]) - ($watermarkOrigin[1])
				];
			break;

			case "right":
				$pos = [
					$this->width - $watermark->width - $margin,
					($imageOrigin[1]) - ($watermarkOrigin[1])
				];
			break;

			case "bottomLeft":
				$pos = [
					0 + $margin,
					$this->height - $watermark->height - $margin
				];
			break;

			case "bottomCenter":
				$pos = [
					($imageOrigin[0]) - ($watermarkOrigin[0]),
					$this->height - $watermark->height - $margin
				];
			break;

			case "bottomRight":
				$pos = [
					$this->width - $watermark->width - $margin,
					$this->height - $watermark->height - $margin
				];
			break;
		}

		return $pos;
	}


}