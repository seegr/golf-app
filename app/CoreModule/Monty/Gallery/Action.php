<?php

namespace Monty\Gallery;

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
		$linkAttr;


	public function __construct($name, $label, $link, $linkAttr = null) {
		$this->name = $name;
		$this->label = $label;
		$this->link = $link;
		$this->linkAttr = $linkAttr;

		return $this;
	}

	public function setParent($parent) {
		$this->parent = $parent;
	}

	public function getPresenter() {
		if (!$this->presenter) {
			$this->presenter = $this->parent->getPresenter();
		}

		return $this->presenter;
	}

	public function addClass($class) {
		$this->class[] = $class;

		return $this;
	}

	public function setConfirm($text) {
		$this->confirm = $text;

		return $this;
	}

	public function setIcon($icon) {
		$this->icon = $icon;

		return $this;
	}

	public function getLink($itemId = null) {
		$presenter = $this->getPresenter();

		if ($itemId) {
			if (!$this->linkAttr) {
				$attrs = $itemId;
			} else {
				$attrs = [$this->linkAttr => $itemId];
			}
		} else {
			$attrs = [];
		}

		return $presenter->link($this->link, $attrs);
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
		$button->class[] = "action btn btn-sm btn-blue ajax";

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