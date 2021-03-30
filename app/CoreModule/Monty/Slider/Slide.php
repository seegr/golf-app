<?php

namespace Monty\Slider;


class Slide {

	public $image, $title, $text, $html, $slider, $height;
	public $style = [];


	public function __construct($title, $image = null, $text = null, $html = null) {
		$this->title = $title;
		$this->image = $image;
		$this->text = $text;
		$this->html = $html;

		return $this;
	}

	public function setSlider($slider) {
		$this->slider = $slider;

		return $this;
	}

	public function setImage($image) {
		$this->image = $image;

		return $this;
	}

	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	public function setText($text) {
		$this->text = $text;

		return $this;
	}

	public function setHtml($html) {
		$this->html = $html;

		return $this;
	}

	public function getHeight() {
		if ($this->height) {
			return $this->height;
		} else {
			$this->addStyle("height", $this->slider->height);
		}
	}

	public function addStyle($attr, $val) {
		$this->style[$attr] = $val;

		return $this;
	}

}