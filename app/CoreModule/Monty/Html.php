<?php

namespace Monty;

use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Finder;


class Html extends \Nette\Utils\Html {
	
	
	public static function link($class, $link = null, $outerClass = null, $ajax = false) {
		$btn = self::el("a");
		$btn->class[] = $ajax ? "ajax" : null;
		$btn->class[] = $outerClass;

		if ($link) {
			$btn->href($link);
		}

		$i = self::el("i");
		$i->class[] = $class;

		$btn->addHtml($i);

		#bdump($btn, "btn");

		return $btn;
	}

	public static function iconButton($btnClass, $iconClass, $link = null, $ajax = false) {
		$btn = self::link($iconClass, $link, $btnClass, $ajax);
		$btn->class[] = "btn";

		return $btn;
	}

	public static function button($text, $link = null, $class = null, $ajax = false) {
		$btn = self::link($class, $link, null, $ajax);
		$btn->class[] = "btn";
		$btn->setText($text);

		return $btn;
	}

	public function setTooltip($tooltip) {
		$this->addAttributes([
			"data-toggle" => "tooltip",
			"title" => $tooltip
		]);

		return $this;
	}

	public function setPopover($title = null, $content) {
		$this->addAttributes([
			"data-toggle" => "popover",
			"data-content" => $content,
			"title" => $title
		]);

		return $this;
	}

	public function setDisabled($state = true) {
		$this->class[] = "disabled";

		return $this;
	}

	public function setConfirm($text) {
		$this->addAttributes([
			"data-confirm" => $text
		]);

		return $this;
	}

	public static function icon($iconClass, $class = null) {
		$i = self::el("i");

		$i->class[] = $iconClass;

		return $i;
	}

}