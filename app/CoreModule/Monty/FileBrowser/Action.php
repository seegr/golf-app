<?php

namespace Monty\FileBrowser;

use SplFileInfo as FileInfo;
use Nette\Utils\Image as NetteImage;

use Nette\Utils\Html;


class Action {

	use \Nette\SmartObject;

	public
		$name,
		$label,
		$link,
		$class = [],
		$confirm,
		$parent,
		$presenter,
		$icon,
		$linkAttr,
		$callback;


	public function __construct($name, $label, $callback) {
		$this->name = $name;
		$this->label = $label;
		$this->callback = $callback;

		return $this;
	}

	public function setParent($parent) {
		$this->parent = $parent;

		return $this;
	}

	public function getPresenter() {
		if (!$this->presenter) {
			$this->presenter = $this->parent->getPresenter();
		}

		return $this->presenter;
	}

	public function addClass($class) {
		bdump("browser action add class");
		$this->class[] = $class;

		return $this;
	}

	public function setConfirm($text) {
		$this->confirm = $text;

		return $this;
	}

	public function setIcon($icon) {
		$this->icon = $icon;

		return $icon;
	}

	public function getLink($itemId = null) {
		$presenter = $this->getPresenter();

		if ($itemId) {
			$attrs = [$this->linkAttr => $itemId];
		} else {
			$attrs = [];
		}

		return $presenter->link($this->link, $attrs);
	}

	public function getClass() {
		$this->class[] = "selected-action btn";
		
		return implode(" ", $this->class);
	}

	/*public function getActions() {
		return $this->parent->getImageActions();
	}*/

	/*public function getLabel() {
		$html = Html::el("span");
		$html->class = $this->class;

		if ($this->icon) {
			$i = Html::el("i");
			$i->class = $this->icon;
			$html->addHtml($i);
		} else {
			$html->setText($this->label);
		}

		return $html;
	}*/

	public function getButton($itemId = null) {
		$button = Html::el("a");
		$button->href($this->getLink($itemId));
		$button->class = $this->class;
		$button->class[] = "action";

		if ($this->confirm) {
			$button->addAttributes([
				"data-confirm" => $this->confirm
			]);
		}

		if ($this->icon) {
			$i = Html::el("i");
			$i->class = $this->icon;
			$button->addHtml($i);
		} else {
			$button->setText($this->label);
		}

		return $button;
	}

}