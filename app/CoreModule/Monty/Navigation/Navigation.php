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

		\Tracy\Debugger::barDump($this->itemsSorted, "itemsSorted");

		$menuCols = 0;
		if ($centerItems) $menuCols++;
		if ($leftItems) $menuCols++;
		if ($rightItems) $menuCols++;
		$template->itemsCol = $menuCols ? 12 / $menuCols : 12;

		$template->positions = ["left", "center", "right"];

		#\Tracy\Debugger::barDump($this->items, "items");

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
		// \Tracy\Debugger::barDump($title, "title");
		// \Tracy\Debugger::barDump($id, "id");
		// \Tracy\Debugger::barDump($link, "link");
		// \Tracy\Debugger::barDump($title, "addItem");
		// \Tracy\Debugger::barDump($id, "addItem - id");
		// \Tracy\Debugger::barDump($parent, "addItem - parent");

		if (!$id) {
			$genId = null;
			while ($genId == null || isset($this->items[$genId])) {
				$genId = Random::generate(4, "a-z");
			}
			$id = $genId;
		}

		// $id = $id ? $id : $maxId++;
		// \Tracy\Debugger::barDump($id, "id");

		$data = $this->getItemData($title, $link);
		// \Tracy\Debugger::barDump($data, "item data");

		$item = new Item($this, $id, $data->link, $data->title, $data->class);

		if (!empty($data->route)) {
			#\Tracy\Debugger::barDump($data->route, "data route");
			if (is_array($data->route)) {
				$item->setRoute($data->route[0], $data->route[1]);
			} else {
				$item->setRoute($data->route);
			}
		} 
		$item->setPosition("center");

		#\Tracy\Debugger::barDump($title, "item title");
		#\Tracy\Debugger::barDump($item, "item");

		//$level = $item->parent ? $this->itemsArr[$item->parent]->level + 1 : 1;
		$item->setLevel($level);

		$this->itemsArr[$id] = $item;

		if (!empty($data->childs)) {
			// \Tracy\Debugger::barDump($data->childs, "childs");
			foreach ($data->childs as $childId => $child) {
				// \Tracy\Debugger::barDump($child->title, "child");
				$childData = $this->getItemData($childId, $child);

				// \Tracy\Debugger::barDump($this->itemsArr[$item->id], "parent");
				$level = $level + 1;
				// \Tracy\Debugger::barDump($this->itemsArr[$item->parent]->level, "parent level");
				#\Tracy\Debugger::barDump($level, "level");

				$childItem = $this->addItem($childData->title, $child, $child->id, $level);
				$childItem->setLevel($level);
				// \Tracy\Debugger::barDump($childItem, "childItem");
				$childItem->setParent($item->id);
				#\Tracy\Debugger::barDump($childItem, "childItem");
				#\Tracy\Debugger::barDump($child->id, "childId");
				if (!empty($childItem->route)) $childItem->setRoute($childItem->route[0], $childItem->route[1]);

				$this->itemsArr[$child->id] = $childItem;
				$item->childs[$child->id] = $childItem;
			}
		}
		// \Tracy\Debugger::barDump($this->itemsArr, "itemsArr");

		if ($item->level == 1) {
			$this->items[$id] = $this->itemsArr[$id];
		}

		#\Tracy\Debugger::barDump($item, "item");
		if (!empty($item->route)) {
			// \Tracy\Debugger::barDump(1);
			// \Tracy\Debugger::barDump($item->route, "route");
			$routeArgs = [];
			if (is_array($item->route[1]) && count($item->route[1])) {
				foreach ($item->route[1] as $arg => $val) {
					$routeArgs[$arg] = $val;
				}
			}
			// \Tracy\Debugger::barDump($item->route[0], "route");
			// \Tracy\Debugger::barDump($routeArgs, "routeArgs");
			$presenter = $this->getPresenter();
			// \Tracy\Debugger::barDump($presenter->getParameters(), "pars");

			if ($presenter->isLinkCurrent($item->route[0], $routeArgs)) {
				// bdump($item->text, "is current");
				$this->activeItem = $item->id;
				$this->itemsArr[$item->id]->setActive();
			} else {
				// \Tracy\Debugger::barDump("not current");
			}
		}

		return $item;
	}

	public function addItems($items) {
		#\Tracy\Debugger::$maxDepth = 10;
		// \Tracy\Debugger::barDump($items, "adding items");
		foreach ($items as $item => $data) {
			// $data = ArrayHash::from($data);
			// \Tracy\Debugger::barDump($data, "item data");
			$id = !empty($data->id) ? $data->id : null;
			$this->addItem($data->title, $data, $id);
		}

		#\Tracy\Debugger::barDump($this->items, "items");

		return $this;
	}

	public function getItemLink($item, $onlyFullRoute = false) {
		// \Tracy\Debugger::barDump($item, "getitemlink item");
		$link = null;

		$presenter = $this->getPresenter();

		if (!empty($item->route) && $this->linksGenerator) {
			// \Tracy\Debugger::barDump(1);
			// $presenter = $this->getPresenter();

			// \Tracy\Debugger::barDump($item->parameters, "item pars");
			if (!empty($item->params)) {
				$parameters = is_string($item) ? json_decode($item->params, true) : $item->params;
			} else {
				$parameters = [];
			}

			if (empty($item->home)) {
				$parameters["nav_item_id"] = $item->id;
			}

			// \Tracy\Debugger::barDump($parameters, "pars");

			/*foreach ($parameters as &$par) {
				$par = json_encode($par, true);
			}*/
			
			// \Tracy\Debugger::barDump($parameters, "parameters");

			$link = $presenter->link($item->route, $parameters);

			if (!$onlyFullRoute) {
				$link = $link;
			} else {
				$link = [$item->route, $parameters];
			}
		} else if (!empty($item->home)) {
			// \Tracy\Debugger::barDump(2);
			// \Tracy\Debugger::barDump($item, "item");
			$link = $presenter->link($item->route);
		} else {
			// \Tracy\Debugger::barDump(3);
			// \Tracy\Debugger::barDump($item->url, "url");
			// \Tracy\Debugger::barDump($item, "item");
			if (!empty($item->link) && !$onlyFullRoute) {
				// \Tracy\Debugger::barDump($item->title, "has link (getItemLink)");
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
			#\Tracy\Debugger::barDump($attr, "item attr");
			$data = [
				"title" => $attr["title"],
				"link" => $this->getItemLink($attr),
				"class" => isset($attr["class"]) ? $attr["class"] : null,
				"childs" => isset($attr["childs"]) ? $attr["childs"] : null
			];

			$data = ArrayHash::from($data);
			$data["route"] = $this->getItemLink($origData, true);
			#\Tracy\Debugger::barDump($data, "data");
		} else {
			// \Tracy\Debugger::barDump($attr, "attr");
			if (strpos($attr, ":") !== false && strpos($attr, "http") === false) {
				$route = $attr;
				// \Tracy\Debugger::barDump($attr, "attr");
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

		#\Tracy\Debugger::barDump($data, "data");

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
			#\Tracy\Debugger::barDump("items filter loop", $position);
			\Tracy\Debugger::barDump($this->items, "items before sorting");
			$items = array_filter($this->items, function($item) use ($position) {
				return $item->position == $position;
			});

			$this->itemsSorted[$position] = $items;
		}

		return $this->itemsSorted[$position];
	}

	public function getItemTrace($id = null, $trace = [], $justIds = false) {
		#\Tracy\Debugger::barDump($this->itemsArr, "itemsArr");
		#\Tracy\Debugger::barDump($trace, "trace");

		#\Tracy\Debugger::barDump($id, "getItemTrace id");

		// if (!isset($itemsArr[$id])) return null;
		// \Tracy\Debugger::barDump($this->activeItem, "activeItem");
		$id = $id ? $id : $this->activeItem;
		// \Tracy\Debugger::barDump($id, "active item id");
		// \Tracy\Debugger::barDump($this->itemsArr, "itemsArr");

		if (!$id) return;

		$item = $this->itemsArr[$id];

		if ($justIds) {
			$trace[] = $id;
		} else {
			$trace[$id] = $item;
		}

		if ($item->parent) {
			// \Tracy\Debugger::barDump($item->parent, "item parent");
			$trace = $this->getItemTrace($item->parent, $trace, $justIds);
		}

		return $trace;
	}

	public function getBreadcrumbs($currentItemId = null) {
		if ($currentItemId) {
			$trace = $this->getItemTrace($currentItemId);
			// \Tracy\Debugger::barDump($trace, "trace");

			if (!$trace) return [];

			$trace = array_reverse($trace, true);

			$breadcrumbs = [];
			foreach ($trace as $item) {
				// \Tracy\Debugger::barDump($item->link, "link");
				$breadcrumbs[$item->text] = $item->link;
			}
		} else {
			$presenter = $this->getPresenter();
			$pars = $presenter->getParameters();
			// \Tracy\Debugger::barDump($pars, "pars");

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
		// \Tracy\Debugger::barDump($breadcrumbs, "breadcrumbs");

		return $breadcrumbs;
	}

	protected function setParentActive() {
		$activeItem = $this->activeItem;
		#\Tracy\Debugger::barDump($this->activeItem, "activeItem");

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
		// \Tracy\Debugger::barDump("setHomepageRoute...");
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
		// \Tracy\Debugger::barDump($class, "class");
		$this->itemsClass[] = $class;

		return $this;
	}

	public function setBreadcrumbIcon($iconClass) {
		// \Tracy\Debugger::barDump("setBreadcrumbIcon");
		$this->breadcrumbIcon = $iconClass;

		return $this;
	}

}
