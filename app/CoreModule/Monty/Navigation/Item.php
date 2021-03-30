<?php

namespace Monty\Navigation;

use Nette;
use Nette\Utils\Html;

use Monty\Navigation;


class Item {

	use Nette\SmartObject;

	public
		$id,
		$nav,
		$parent,
		$link,
		$route,
		$text,
		$icon,
		$image,
		$class = [],
		$iconClass = [],
		$tooltip,
		$popover = [],
		$attrs = [],
		$onClick,
		$position,
		$alias,
		$alwaysVisible,
		$childs = [],
		$active,
		$childActive,
		$level,
		$activeRoutes = [];


	public function __construct($nav, $id, $link = null, $text = null, $class = null) {
		#\Tracy\Debugger::barDump($id, "id");
		$this->nav = $nav;
		$this->link = $link;
		$this->text = $text;
		$this->id = $id;

		if ($class) {
			$this->class[] = $class;
		}

		return $this;
	}


	public function setLink($link) {
		$this->link = $link;

		return $this;
	}

	public function setRoute($route, $args = null) {
		$this->route = [$route, $args];

		return $this;
	}

	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	public function setParent($parent) {
		// \Tracy\Debugger::barDump($this->text . " set parent " . $parent);
		$this->parent = $parent;

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

		return $this;
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

	public function setHtml($html) {
		$this->setText($html);

		return $this;
	}

	public function setIcon($icon) {
		$this->icon = $icon;

		return $this;
	}

	public function setImage($src, $width = null) {
		$presenter = $this->nav->getPresenter();
		$basePath = $presenter->template->basePath;

		$img = Html::el("img");
		$img->src($basePath . "/" . $src);
		if ($width) {
			$img->width($width);
		}
		$img->class[] = "img-fluid";

		$this->image = $img;

		return $this;
	}

	public function setAlign($align) {
		$this->position = $align;

		//unset($this->nav->items[$this->id]);

		/*switch ($align) {
			case "left":
				$this->nav->leftItems[] = $this;
			break;
			
			case "right":
				$this->nav->rightItems[] = $this;
			break;
		}*/

		return $this;
	}

	public function setPosition($position) {
		$this->position = $position;

		return $this;
	}

	public function setAlias($alias) {
		$this->alias = $alias;

		return $this;
	}

	public function setTooltip($tooltip) {
		$this->tooltip = $tooltip;

		return $this;
	}

	public function setPopover($content, $trigger = "hover-stay", $placement = "bottom") {
		$this->popover = [$content, $trigger, $placement];

		return $this;
	}

	public function setAlwaysVisible($state = true) {
		$this->alwaysVisible = true;
		$this->position = "alwaysVisible";

		return $this;
	}

	public function setActive($state = true) {
		$this->active = $state;

		return $this;
	}

	public function setChildActive($state = true) {
		$this->childActive = $state;

		return $this;
	}

	public function setLevel($level) {
		$this->level = $level;

		return $this;
	}

	public function addAttributes(array $attrs) {
		$this->attrs = $this->attrs + $attrs;

		return $this;
	}

	public function getPresenter() {
		return $this->nav->getPresenter();
	}

	public function getHtml() {
		#\Tracy\Debugger::barDump($this->getPresenter(), "presenter by Item");
		#\Tracy\Debugger::barDump("getHtml");
		// \Tracy\Debugger::barDump($this->text, "getHtml");
		// \Tracy\Debugger::barDump($this, "item");
		$btnWrap = Html::el("div");
		$btnWrap->class[] = "item-wrap";
		if ($this->class) {
			// \Tracy\Debugger::barDump($this->class, "class");
			foreach ($this->class as $class) {
				$btnWrap->class[] = $class;
			}
		}

		$btnWrap->addAttributes([
			"data-id" => $this->id
		]);

		$btn = Html::el("a");
		if ($this->link) $btn->href($this->link);
		$btn->class[] = "item";
		
		if ($this->nav->itemsClass) {
			$btn->class = array_merge($btn->class, $this->nav->itemsClass);
		}

		$presenter = $this->nav->getPresenter();

		if ($this->position != "brand") {
			if ($this->active) $btnWrap->class[] = "active";
			if ($this->childActive) $btnWrap->class[] = "child-active";

			// \Tracy\Debugger::barDump($this->activeRoutes, "activeRoutes"); 
			if ($this->activeRoutes) {
				foreach ($this->activeRoutes as $route) {
					// \Tracy\Debugger::barDump($route, "route");
					if ($presenter->isLinkCurrent($route)) {
						$btnWrap->class[] = "active";
						break;
					}
				}
			}
		}

		// \Tracy\Debugger::barDump($this->position, "position");
		if ($this->link) {
			$btn->class[] = "item-link";
		} else {
			$btn->class[] = "item-separtor";
		}

		$btn->class[] = $this->position == "alwaysVisible" ? "ml-auto order-lg-last" : "";

		if ($this->icon) {
			$i = Html::el("i");

			$iconClass = $this->iconClass;

			$i->class = $iconClass;
			$i->class[] = $this->icon;
			$i->class[] = "mr-1";

			$btn->addHtml($i);
		}

		if ($this->tooltip) {
			$btn->addAttributes([
				"data-toggle" => "tooltip",
				"title" => $this->tooltip
			]);
		}

		$btn->addAttributes($this->attrs);

		if ($this->image) {
			$btn->addHtml($this->image);
		}

		if ($this->text) {
			$btn->addHtml($this->text);
		}

		if ($this->nav->debug) {
			$span = Html::el("span");
			$span->style("font-size: 0.8em; opacity: 0.6");
			$span->addHtml(" (id: " . $this->id . " l: " . $this->level . ")");
			$btn->addHtml($span);
		}

		if ($this->popover) {
			$btn->addAttributes([
				"data-toggle" => "popover",
				"data-content" => $this->popover[0],
				"data-trigger" => $this->popover[1],
				"data-placement" => $this->popover[2]
			]);
		}

		$btn->onclick($this->onClick);

		switch ($this->nav->getType()) {
			case "buttons":
				$btn->class[] = "btn btn-sm";
				if ($this->class) {
					$btn->class[] = implode($this->class, " ");
				} else {
					$btn->class[] = $this->class ? $this->class : "btn-light";
				}
			break;

			case "navbar":
				$btn->class = array_merge($btn->class, $this->class);
				if ($this->id === "brand" || $this->position == "brand") {
					$btn->class[] = "navbar-brand";
				} else {
					$btn->class[] = "nav-item nav-link";
				}
			break;

			case "vertical":
				$btn->class[] = "d-block";
			break;
		}

		if ($this->alias) {
			$btn->addAttributes([
				"data-alias" => $this->alias
			]);
		}

		$btnWrap->addHtml($btn);

		// \Tracy\Debugger::barDump($this->text, "getHtml");
		// \Tracy\Debugger::barDump($this->childs, "childs");
		// \Tracy\Debugger::barDump($this->level, "level");
		// \Tracy\Debugger::barDump($this->nav->depth, "depth");
		if ($this->level < $this->nav->depth && count($this->childs)) {
			// \Tracy\Debugger::barDump("has child", $this->text);

			$btnWrap->class[] = "parent";
			if ($this->nav->type != "vertical") {
				$icon = Html::el("i class='fas fa-caret-down ml-2'");
				$btn->addHtml($icon);
			}

			$childsWrap = Html::el("div class='childs'");

			foreach($this->childs as $child) {
				$childHtml = $child->getHtml();
				$childsWrap->addHtml($childHtml);
			}

			$btnWrap->addHtml($childsWrap);
			/*$btnWrap->class[] = "parent";
			$icon = Html::el("i class='fas fa-caret-down ml-2'");
			$btn->addHtml($icon);
			$btnWrap->addHtml($this->getHtml());*/
		}


		// \Tracy\Debugger::barDump($btnWrap, "btnWrap");
		// \Tracy\Debugger::barDump($btn, "btn");
		return $btnWrap;
	}

	public function addItem($text = null, $link = null, $class = null) {
		$Item = new Item($this->nav, null, $link, $text, $class);

		$this->childs[] = $Item;

		return $Item;
	}

	public function addChild($Item) {
		$this->childs[] = $Item;

		return $this;
	}

	public function getChilds() {
		return $this->childs;
	}

	public function addCurrentRoute($route) {
		if (is_array($route)) {
			foreach ($route as $r) {
				$this->addCurrentRoute($r);
			}
		} else {
			$this->activeRoutes[] = $route;
		}

		return $this;
	}

}