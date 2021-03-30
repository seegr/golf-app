<?php

namespace App\Components\Dropzone;


class Input {

	use \Nette\SmartObject;

	public $type, $name, $label, $placeholder;


	public function __construct($type, $name, $label = null) {
		$this->type = $type;
		$this->name = $name;
		$this->label = $label;

		return $this;
	}

	public function setPlaceholder($placeholder) {
		$this->placeholder = $placeholder;
	}

}