<?php

namespace Monty;

use Monty\Slider\Slide;


class Slider extends BaseControl {

	protected $id;
	public $slides = [];
	public $height = "200px";


	public function render() {
		$template = $this->template;

		$template->setFile(__DIR__ . "/bootstrap-slider.latte");

		$template->id = $this->getId();
		$template->slides = $this->slides;

		$template->render();
	}


	public function addSlide($title, $image = null, $text = null, $html = null) {
		$slide = new Slide($title, $image = null, $text = null, $html = null);

		$slide->setSlider($this);

		$this->slides[] = $slide;

		return $this;
	}

	public function setHeight($height) {
		$this->height = $height;

		return $this;
	}
}