<?php

namespace Monty;

use Nette;
use Nette\Utils\FileSystem;


class ImageText extends \Nette\Utils\Image {

	use \Nette\SmartObject;

	protected
		$image,
		$parent,
		$text,
		$color,
		$font,
		$size,
		$position,
		$opacity;


	public function __construct($text, $opacity = 50) {
		$this->text = $text;

		$this->opacity = (127 / 100) * $opacity;

		return $this;
	}

	public function getImage($canvas) {
		$text = $this->text;
		$size = $this->size ? $this->size : 1;
		$color = $this->color ? $this->color : "white";
		$opacity = $this->opacity;

		/*if ($this->width) {
			if ($image->width > $this->width) $image->resize($this->width, null);
		}*/

		#FileSystem::createDir($publicImages);

		//$font = $this->folders->fonts . "OpenSans-ExtraBold.ttf";
		#$fontSize = $image->width * 0.08;

		$font = $this->getFont();
		$fontSize = $size * 4;
		
		$dimensions = imagettfbbox($fontSize, 0, $font, $text);
		\Tracy\Debugger::barDump($dimensions, "dimensions");
		$width = $dimensions[4];
		$height = abs($dimensions[5]);

		$image = $this->image;
		/*$this->image = $image;
		\Tracy\Debugger::barDump($image, "image");*/

		switch ($color) {
			case "white":
				$color = imagecolorallocatealpha((\Nette\Utils\Image::fromBlank(100,100))->getImageResource(), 255, 255, 255, $this->opacity);
			break;

			case "black":
				$color = imagecolorallocatealpha($image->getImageResource(), 0, 0, 0, $this->opacity);
			break;
		}


		#\Tracy\Debugger::barDump($brandDimensions, "brandDimensions");

		/*switch ($position) {
			case "bottomRight";
			break;
			default:
				$posX = ($image->width / 2) - ($dimensions[4]/2);
				$posY = ($image->height / 2) + (abs($dimensions[5]) / 2);
			break;
		}*/

		$posX = 0;
		$posY = $height;

		$image = self::fromBlank($width, $height);
		$image->ttfText($fontSize, 0, $posX, $posY, $color, $font, $text);

		/*if ($vals->author_name != "" || $vals->author_email) {
			$authorText = "autor: ";
			if ($vals->author_name != "" && $vals->author_email != "") {
				$authorText .= "$vals->author_name ($vals->author_email)";
			} else if ($vals->author_name != "") {
				$authorText .= $vals->author_name;
			} else {
				$authorText .= $vals->author_email;
			}
			$authorFont = $this->folders->fonts . "OpenSans-Regular.ttf";
			$authorFontSize = $image->width * 0.015;
			$authorColor = imagecolorallocatealpha($image->getImageResource(), 255, 255, 255, 40);
			$authorDimensions = imagettfbbox($authorFontSize, 0, $authorFont, $authorText);
			\Tracy\Debugger::barDump($authorDimensions, "dimensions");
			$authorX = $image->width - $authorDimensions[4] - 10;
			$authorY = $image->height - 10;
			$image->ttfText($authorFontSize, 0, $authorX, $authorY, $authorColor, $authorFont, $authorText);
		}*/

		#\Tracy\Debugger::barDump($image, "image");

		//$this->image = $image;
		#$image->sharpen();

		$this->image = $image;

		$image->save(__DIR__ . "/../../../www/files/test.jpg", 100);

		return $this;
	}

	public function font($fontPath) {
		$this->font = $fontPath;

		return $this;
	}

	public function text($text) {
		$this->text = $text;

		return $this;
	}

	public function size($size) {
		$this->size = $size;

		return $this;
	}

	public function color($color) {
		$this->color = $color;

		return $this;
	}

	public function getFont() {
		if (!$this->font) {
			throw new \Exception("You have to set font path");
		} else {
			return $this->font;
		}
	}

	public function getPosition() {
		return $this->position ? $this->position : "center";;
	}

	/*public function getDimensions() {
		return [$this->width, $this->height];
	}*/

	/*public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}*/
}