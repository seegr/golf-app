<?php

namespace App\Components\Tabs;

use Nette;
use Nette\Utils\Html;


class Tab {

	use Nette\SmartObject;

	public
		$id,
		$nav,
		$link,
		$text,
		$icon,
		$class = [],
		$iconClass = [],
		$tooltip,
		$onClick,
		$align,
		$alias,
		$active,
		$container,
		$content,
		$params;


	public function __construct($container, $id, $text = null) {
		$this->container = $container;
		$this->id = $id;
		$this->text = $text;

		return $this;
	}


	public function link($link) {
		$this->link = $link;

		return $this;
	}

	public function onClick($callback) {
		$this->onClick = $callback;

		return $this;
	}

	public function addClass($class) {
		if (is_array($class)) {
			$this->class += $class;
		} else {
			$this->class[] = $class;
		}
	}

	public function addIconClass($class) {
		if (is_array($class)) {
			$this->iconClass += $class;
		} else {
			$this->iconClass[] = $class;
		}
	}

	public function setText($text) {
		$this->text = $text;

		return $this;
	}

	public function setIcon($icon) {
		$this->icon = $icon;

		return $this;
	}

	public function setAlign($align) {
		$this->align = $align;

		unset($this->nav->items[$this->id]);

		switch ($align) {
			case "left":
				$this->nav->leftItems[] = $this;
			break;
			
			case "right":
				$this->nav->rightItems[] = $this;
			break;
		}

		return $this;
	}

	public function setAlias($alias) {
		$this->alias = $alias;

		return $this;
	}

	public function setActive($state = true) {
		$this->active = $state;

		return $this;
	}

	public function setContent($content, $params = []) {
		$this->content = $content;
		$this->params = $params;

		return $this;
	}

	public function getHtmlId() {
		$tabsId = $this->container->getHtmlId();

		return $tabsId . "-" . $this->id;
	}

	public function getButtonHtml() {
		$li = Html::el("li");

		$li->class[] = "nav-item";
		
		$a = Html::el("a");
		$a->class[] = "nav-link";
		$a->class[] = $this->active ? "active" : null;
		
		$tabId = $this->getHtmlId();

		$a->addAttributes([
			"id" => $tabId,
			"data-toggle" => "tab",
			"href" => $this->link ? $this->link : "#" . $this->id,
			"role" => "tab",
			"aria-controls" => $this->id
		]);

		if ($this->icon) {
			$i = Html::el("i");
			$i->class[] = $this->icon;
			$a->addHtml($i);
		}

		$a->addHtml($this->text);

		$li->addHtml($a);

		return $li;
	}

	public function getContentType() {
		if ($this->content) {
			if (!is_object($this->content)) {
				$pathinfo = pathinfo($this->content);

				if (isset($pathinfo["extension"]) && in_array($pathinfo["extension"], ["latte", "html"])) {
					return "template";
				} else {
					return "string";
				}
			} else {
				return "control";
			}
		} else {
			return null;
		}
	}

	public function getContentHtml() {
		$div = Html::el("div");

		$div->id($this->id);
		$div->class[] = "tab-pane fade";
		$div->class[] = $this->active ? "show active" : null;
		$div->addAttributes([
			"role" => "tabpanel",
			"aria-labelledby" => $this->id
		]);

		$div->addHtml($this->content);

		return $div;
	}

}