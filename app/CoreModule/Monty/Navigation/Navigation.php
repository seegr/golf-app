<?php

namespace Monty;

use Nette;
use Nette\Utils\Html;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;
use Exception;

use Monty\Navigation\Item;


class Navigation extends BaseControl {

	public
		$id,
		$navId,
		$class = ["navbar-light", "navbar-expand-lg"],
		$items = [],
		$itemsArr = [],
		$itemsSorted = [],
		$itemsClass = [],
		$sticky,
		$linksGenerator = true,
		$type = "navbar",
		$itemsCounter = 0,
		$activeItem,
		$depth = 10000,
		$debug,
		$breadcrumbIcon = "fas fa-angle-right";
		


	public function __construct() {
		return $this;
	}


	public function render() {
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/" . $this->type . ".latte");

		$this->id = "navigation-control-" . $this->lookupPath();
		$template->id = $this->id;

		$this->class[] = "navigation-" . $this->type;

		$brandItems = $template->brandItems = $this->getItems("brand");
		$centerItems = $template->centerItems = $this->getItems("center");
		$leftItems = $template->leftItems = $this->getItems("left");
		$rightItems = $template->rightItems = $this->getItems("right");
		$alwaysVisibleItems = $template->alwaysVisibleItems = $this->getItems("alwaysVisible");

		bdump($this->itemsSorted, "itemsSorted");

		$menuCols = 0;
		if ($centerItems) $menuCols++;
		if ($leftItems) $menuCols++;
		if ($rightItems) $menuCols++;
		$template->itemsCol = $menuCols ? 12 / $menuCols : 12;

		$template->positions = ["left", "center", "right"];

		#bdump($this->items, "items");

		$this->setParentActive();

		return $this->renderIt($template);
		// $template->render();
	}

	public function renderBreadcrumbs()
	{
		parent::render();
		$template = $this->template;

		$template->setFile(__DIR__ . "/templates/breadcrumbs.latte");
		$template->breadcrumbs = $this->getBreadcrumbs();
		$template->icon = $this->breadcrumbIcon;

		$template->render();
	}

	public function setType($type) {
		$this->type = $type;

		return $this;
	}

	public function setLayout($l) {
		$this->setType($l);

		return $this;
	}

	public function setSticky($state = true) {
		$this->sticky = $state;
	}

	public function setClass($class) {
		$this->class = [];
		$this->class[] = $class;

		return $this;
	}

	public function setId($id) {
		$this->navId = $id;

		return $this;
	}

	public function addClass($class) {
		$this->class[] = $class;

		return $this;
	}

	public function getType() {
		return $this->type;
	}

	public function setLinksGenerator($state = true) {
		$this->linksGenerator = $state;

		return $this;
	}

	public function setLinkGenerator($state = true) {
		$this->linksGenerator = $state;

		return $this;
	}

	public function setDepth($depth) {
		$this->depth = $depth;

		return $depth;
	}

	public function addItem($title = null, $link = null, $id = null, $level = 1) {
		// bdump($title, "title");
		// bdump($id, "id");
		// bdump($link, "link");
		// bdump($title, "addItem");
		// bdump($id, "addItem - id");
		// bdump($parent, "addItem - parent");

		if (!$id) {
			$genId = null;
			while ($genId == null || isset($this->items[$genId])) {
				$genId = Random::generate(4, "a-z");
			}
			$id = $genId;
		}

		// $id = $id ? $id : $maxId++;
		// bdump($id, "id");

		$data = $this->getItemData($title, $link);
		// bdump($data, "item data");

		$item = new Item($this, $id, $data->link, $data->title, $data->class);

		if (!empty($data->route)) {
			#bdump($data->route, "data route");
			if (is_array($data->route)) {
				$item->setRoute($data->route[0], $data->route[1]);
			} else {
				$item->setRoute($data->route);
			}
		} 
		$item->setPosition("center");

		#bdump($title, "item title");
		#bdump($item, "item");

		//$level = $item->parent ? $this->itemsArr[$item->parent]->level + 1 : 1;
		$item->setLevel($level);

		$this->itemsArr[$id] = $item;

		if (!empty($data->childs)) {
			// bdump($data->childs, "childs");
			foreach ($data->childs as $childId => $child) {
				// bdump($child->title, "child");
				$childData = $this->getItemData($childId, $child);

				// bdump($this->itemsArr[$item->id], "parent");
				$level = $level + 1;
				// bdump($this->itemsArr[$item->parent]->level, "parent level");
				#bdump($level, "level");

				$childItem = $this->addItem($childData->title, $child, $child->id, $level);
				$childItem->setLevel($level);
				// bdump($childItem, "childItem");
				$childItem->setParent($item->id);
				#bdump($childItem, "childItem");
				#bdump($child->id, "childId");
				if (!empty($childItem->route)) $childItem->setRoute($childItem->route[0], $childItem->route[1]);

				$this->itemsArr[$child->id] = $childItem;
				$item->childs[$child->id] = $childItem;
			}
		}
		// bdump($this->itemsArr, "itemsArr");

		if ($item->level == 1) {
			$this->items[$id] = $this->itemsArr[$id];
		}

		#bdump($item, "item");
		if (!empty($item->route)) {
			// bdump(1);
			// bdump($item->route, "route");
			$routeArgs = [];
			if (is_array($item->route[1]) && count($item->route[1])) {
				foreach ($item->route[1] as $arg => $val) {
					$routeArgs[$arg] = $val;
				}
			}
			// bdump($item->route[0], "route");
			// bdump($routeArgs, "routeArgs");
			$presenter = $this->getPresenter();
			// bdump($presenter->getParameters(), "pars");

			if ($presenter->isLinkCurrent($item->route[0], $routeArgs)) {
				// bdump($item->text, "is current");
				$this->activeItem = $item->id;
				$this->itemsArr[$item->id]->setActive();
			} else {
				// bdump("not current");
			}
		}

		return $item;
	}

	public function addItems($items) {
		#\Tracy\Debugger::$maxDepth = 10;
		// bdump($items, "adding items");
		foreach ($items as $item => $data) {
			// $data = ArrayHash::from($data);
			// bdump($data, "item data");
			$id = !empty($data->id) ? $data->id : null;
			$this->addItem($data->title, $data, $id);
		}

		#bdump($this->items, "items");

		return $this;
	}

	public function getItemLink($item, $onlyFullRoute = false) {
		// bdump($item, "getitemlink item");
		$link = null;

		$presenter = $this->getPresenter();

		if (!empty($item->route) && $this->linksGenerator) {
			// bdump(1);
			// $presenter = $this->getPresenter();

			// bdump($item->parameters, "item pars");
			if (!empty($item->params)) {
				$parameters = is_string($item) ? json_decode($item->params, true) : $item->params;
			} else {
				$parameters = [];
			}

			if (empty($item->home)) {
				$parameters["nav_item_id"] = $item->id;
			}

			// bdump($parameters, "pars");

			/*foreach ($parameters as &$par) {
				$par = json_encode($par, true);
			}*/
			
			// bdump($parameters, "parameters");

			$link = $presenter->link($item->route, $parameters);

			if (!$onlyFullRoute) {
				$link = $link;
			} else {
				$link = [$item->route, $parameters];
			}
		} else if (!empty($item->home)) {
			// bdump(2);
			// bdump($item, "item");
			$link = $presenter->link($item->route);
		} else {
			// bdump(3);
			// bdump($item->url, "url");
			// bdump($item, "item");
			if (!empty($item->link) && !$onlyFullRoute) {
				// bdump($item->title, "has link (getItemLink)");
				$link = $item->link;
			} else {
				$link = null;
			}
		}

		return $link;
	}

	public function getItemData($title, $attr) {
		$origData = $attr;

		if (is_array($attr) || is_object($attr)) {
			#bdump($attr, "item attr");
			$data = [
				"title" => $attr["title"],
				"link" => $this->getItemLink($attr),
				"class" => isset($attr["class"]) ? $attr["class"] : null,
				"childs" => isset($attr["childs"]) ? $attr["childs"] : null
			];

			$data = ArrayHash::from($data);
			$data["route"] = $this->getItemLink($origData, true);
			#bdump($data, "data");
		} else {
			// bdump($attr, "attr");
			if (strpos($attr, ":") !== false && strpos($attr, "http") === false) {
				$route = $attr;
				// bdump($attr, "attr");
				$presenter = $this->getPresenter();
				$link = $presenter->link($attr);
			} else {
				$link = $attr;
			}

			$data = ArrayHash::from([
				"link" => $link,
				"title" => $title,
				"class" => null
			]);

			if (!empty($route)) $data["route"] = $attr;
		}

		#bdump($data, "data");

		return $data;
	}

	public function addBrand($link = null, $title = null) {
		$item = $this->addItem($title, $link, "brand");
		$item->setPosition("brand");

		return $item;
	}

	public function getItemsCount() {
		return count($this->items);
	}

	public function hasItems() {
		return $this->getItemsCount() ? true : false;
	}

	public function getItems($position = "center") {
		if (!isset($this->itemsSorted[$position])) {
			#bdump("items filter loop", $position);
			bdump($this->items, "items before sorting");
			$items = array_filter($this->items, function($item) use ($position) {
				return $item->position == $position;
			});

			$this->itemsSorted[$position] = $items;
		}

		return $this->itemsSorted[$position];
	}

	public function getItemTrace($id = null, $trace = [], $justIds = false) {
		#bdump($this->itemsArr, "itemsArr");
		#bdump($trace, "trace");

		#bdump($id, "getItemTrace id");

		// if (!isset($itemsArr[$id])) return null;
		// bdump($this->activeItem, "activeItem");
		$id = $id ? $id : $this->activeItem;
		// bdump($id, "active item id");
		// bdump($this->itemsArr, "itemsArr");

		if (!$id) return;

		$item = $this->itemsArr[$id];

		if ($justIds) {
			$trace[] = $id;
		} else {
			$trace[$id] = $item;
		}

		if ($item->parent) {
			// bdump($item->parent, "item parent");
			$trace = $this->getItemTrace($item->parent, $trace, $justIds);
		}

		return $trace;
	}

	public function getBreadcrumbs($currentItemId = null) {
		if ($currentItemId) {
			$trace = $this->getItemTrace($currentItemId);
			// bdump($trace, "trace");

			if (!$trace) return [];

			$trace = array_reverse($trace, true);

			$breadcrumbs = [];
			foreach ($trace as $item) {
				// bdump($item->link, "link");
				$breadcrumbs[$item->text] = $item->link;
			}
		} else {
			$presenter = $this->getPresenter();
			$pars = $presenter->getParameters();
			// bdump($pars, "pars");

			$breadcrumbs = [
				"home" => $presenter->link($presenter->getHomeRoute())
			];

			if (!empty($pars["nav_item_id"])) {
				$breadcrumbs = $breadcrumbs + $this->getBreadcrumbs($pars["nav_item_id"]);
			} else if (!empty($pars["parent_nav_item_id"])) {
				$breadcrumbs = $breadcrumbs + $this->getBreadcrumbs($pars["parent_nav_item_id"]);
			}

			if (!empty($pars["action"]) && $pars["action"] == "contentDetail") {
				$content = $presenter->template->content;
				$breadcrumbs[$content->title] = $presenter->link("this");
			}
		}
		// bdump($breadcrumbs, "breadcrumbs");

		return $breadcrumbs;
	}

	protected function setParentActive() {
		$activeItem = $this->activeItem;
		#bdump($this->activeItem, "activeItem");

		if (!$activeItem) return;

		$trace = $this->getItemTrace($activeItem);

		unset($trace[$activeItem]);

		foreach ($trace as $id => $item) {
			$this->itemsArr[$id]->setChildActive();
		}
	}

	public function setDebug($state = true) {
		$this->debug = $state;

		return $this;
	}

	public function getActiveItem() {
		return $this->activeItem;
	}

	public function getTrace($justIds = false) {
		return $this->getItemTrace(null, null, $justIds);
	}

	public function getActiveItemChilds() {
		$activeItem = $this->activeItem;

		$item = $this->itemsArr[$activeItem];

		return $item->childs;
	}

	public function setHomepageRoute($route)
	{
		// bdump("setHomepageRoute...");
		$this->homepageRoute = $route;

		return $this;
	}

	public function getItem($id) {
		return $this->itemsArr[$id];
	}

	public function getBrand() {
		return isset($this->itemsArr["brand"]) ? $this->itemsArr["brand"] : null;
	}

	public function setItemsClass($class) {
		// bdump($class, "class");
		$this->itemsClass[] = $class;

		return $this;
	}

	public function setBreadcrumbIcon($iconClass) {
		// bdump("setBreadcrumbIcon");
		$this->breadcrumbIcon = $iconClass;

		return $this;
	}

}
