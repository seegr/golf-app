<?php

namespace Monty;

use Nette;
use Nette\Utils\Image;
use Nette\Utils\ArrayHash;


class Watermark {

	use \Nette\SmartObject;

	protected
		$type,
		$image,
		$parent,
		$text,
		$color,
		$font,
		$size,
		$position,
		$opacity,
		$canvas;

	public
		$width,
		$height;


	public function __construct($watermark) { // string || image file
		if (!file_exists($watermark)) {
			$this->type = "text";
			$this->text = $watermark;	
		}

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

	public function position($position) {
		$this->position = $position;

		return $this;
	}

	public function canvas($canvas) {
		$this->canvas = $canvas;

		return $this;
	}

	public function getFont() {
		if (!$this->font) {
			throw new \Exception("You have to set font path");
		} else {
			return $this->font;
		}
	}

	public function getOpacity() {
		return $this->opacity ? (127 / 100) * $this->opacity : 60;
	}

	public function getPosition() {
		return $this->position;
	}

	public function getTextBox($scale) {
		$font = $this->getFont();
		$text = $this->text;
		$size = $this->size ? $this->size : 1;

		if ($this->canvas) {
			\Tracy\Debugger::barDump("canvas - 1");
			$fontSize = $this->canvas->width * 0.025 * $size;
		} else {
			\Tracy\Debugger::barDump("canvas - 2");
			$fontSize = $size * 10;
		}

		$fontSize = $fontSize * $scale;

		$box = imagettfbbox($fontSize, 0, $font, $text);
		\Tracy\Debugger::barDump($box, "dimensions");
		$width = $box[2] - $box[0];
		$height = $box[1] + abs($box[7]);

		return ArrayHash::from([
			"box" => $box,
			"fontSize" => $fontSize,
			"width" => $width,
			"height" => $height
		]);
	}

	public function getImage() {
		if ($this->type == "text") {
			$text = $this->text;
			$color = $this->color ? $this->color : "white";
			$opacity = $this->getOpacity();

			$font = $this->getFont();
			\Tracy\Debugger::barDump($font, "font");
			\Tracy\Debugger::barDump(is_file($font), "font is file?");

			$scale = 1;
			$textBox = $this->getTextBox($scale);
			while($textBox->width >= $this->canvas->width) {
				$scale = $scale - 0.1;
				$textBox = $this->getTextBox($scale);
			}

			$box = $textBox->box;

			$this->width = $width = $textBox->width;
			$this->height = $height = $textBox->height;
			
			$image = Image::fromBlank($width, $height, Image::rgb(255, 255, 255, 127));

			/*if ($this->canvas) {
				$image = Image::fromBlank($this->canvas->width, $this->canvas->height, Image::rgb(255, 255, 255, 127));
			} else {
			}*/


			switch ($color) {
				case "white":
					$color = imagecolorallocatealpha($image->getImageResource(), 255, 255, 255, $opacity);
				break;

				case "black":
					$color = imagecolorallocatealpha($image->getImageResource(), 0, 0, 0, $opacity);
				break;
			}

			$posX = 0 - $box[0];
			$posY = $height - $box[1];

			$image->ttfText($textBox->fontSize, 0, $posX, $posY, $color, $font, $text);

			#$this->image = $image;

			#$image->save(__DIR__ . "/../../../www/files/test.png", 100);

			return $image;
		}
	}


}