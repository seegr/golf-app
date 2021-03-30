<?php

namespace App\CoreModule\Model;

use Nette;
use Nette\Utils\Random;
use Monty\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Nette\Utils\Json;


class NavigationsManager extends BaseManager {

	const
		TABLE_NAVIGATIONS = "navigations",
		TABLE_NAVIGATIONS_ITEMS = "navigations_items",
		TABLE_ITEMS_CONTENT_BOXES = "navigations_items_content_boxes";

	protected $LinkGenerator;


	public function getNavigations() {
		return $this->db->table(self::TABLE_NAVIGATIONS);
	}

	public function getNavigation($id) {
		return $this->getNavigations()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();
	}

	public function getNavigationsItems() {
		return $this->db->table(self::TABLE_NAVIGATIONS_ITEMS);
	}

	public function getNavigationItem($id) {
		return $this->getNavigationsItems()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();
	}

	public function getItem($id) {
		return $this->getNavigationItem($id);
	}

	public function getNavigationItems($id, $onlyActive = false) {
		$sel = $this->getNavigationsItems()->whereOr([
			"navigation.id" => $id,
			"navigation.short" => $id
		]);

		if ($onlyActive) $sel->where("active", true);

		return $sel;
	}

	public function getNavigationTree($id, $parent = null, $onlyActive = false) {
		//\Tracy\Debugger::barDump($parent, "parent");
		$selection = $this->getNavigationItems($id, $onlyActive);

		$defaultSelection = clone $selection;

		if (!$parent) {
			$selection->where("parent IS NULL");
		} else {
			$selection->where("parent", $parent);
		}

		$selection->order("order");

		$tree = [];

		// \Tracy\Debugger::barDump($selection->fetchAll(), "nav selection");
		foreach ($selection as $item) {
			$data = ArrayHash::from([]);

			$data["id"] = $item->id;
			$data["title"] = $item->title;
			// $data["parameters"] = ["nav_item" => $data["id"]];

			// if ($item->item_alias) \Tracy\Debugger::barDump($item->title, "is item alias");
			$item = $item->item_alias ? $item->ref("item_alias") : $item;

			if ($item->url) {
				\Tracy\Debugger::barDump($item->title, "has url");
				$data["link"] = $item->url;
			} elseif ($item->route) {
				// \Tracy\Debugger::barDump($item->title, "has route");
				$data["route"] = $item->route;
				$data["params"] = json_decode($item->params, true);
			} else {
				$data["link"] = null;
			}

			if (!empty($item->home)) {
				$data["home"] = true;
			}

			// $sel = clone $defaultSelection;

			$childs = $this->getNavigationTree($id, $item->id, $onlyActive);
			$data["childs"]	= $childs ? $childs : null;

			//$data = ArrayHash::from($data);
			// \Tracy\Debugger::barDump($data, "data");
			
			$tree[$item->id] = $data;
		}

		// \Tracy\Debugger::barDump($tree, "tree");
		return $tree;
	}

	/*public function getItemLink($item) {
		$item = is_object($item) ? $item : $this->getNavigationItem($item);

		if (($item->route || $item->content) && $this->LinkGenerator) {
			if ($item->content) {

			} else {

			}
		} else {
			$link = $item->url ? $item->url : null;
		}

		return $link;
	}*/

	public function saveNavigationItem($vals) {
		\Tracy\Debugger::barDump($vals, "vals");
		$data = [
			"title" => $vals->title,
			"navigation" => $vals->navigation,
			"parent" => !empty($vals->parent) ? $vals->parent : null,
			"item_alias" => $vals->item_alias,
			// "content" => $vals->content,
			"route" => $vals->route,
			"url" => $vals->url,
			"params" => $vals->params,
			"class" => $vals->class,
			"template" => $vals->template,
			"params" => !empty($vals->params) ? $vals->params : null,
			"active" => $vals->active
		];

		if ($vals->id) {
			$id = $vals->id;
			// $data["short"] = Strings::webalize($vals->title);
			$item = $this->getNavigationItem($id);
			$item->update($data);
			$item->update(["short" => $this->generateUniqueShort($item, $this->getNavigationsItems()->where("parent", $item->parent), $vals->short)]);
		} else {
			$item = $this->getNavigationsItems()->insert($data);
			$id = $item->id;

			$maxOrder = $this->getNavigationItems($vals->navigation);
			if ($vals->parent) $maxOrder->where("parent", $vals->parent);
			$maxOrder = $maxOrder->max("order");
			#\Tracy\Debugger::barDump($maxOrder, "maxOrder");

			$item->update([
				"order" => $maxOrder + 1,
				"short" => $this->generateUniqueShort($item, $this->getNavigationsItems()->where("parent", $item->parent))
			]);
		}

		$this->saveNavItemAlias($id);

		return $id;
	}

	public function saveNavItemAlias($id, $forced = false) {
		$item = $this->getNavigationItem($id);

		if ($item->alias && !$forced) return;

		\Tracy\Debugger::barDump($id, "id");
		$itemTrace = $this->getItemTrace($id);
		$itemTrace = array_keys($itemTrace);

		// \Tracy\Debugger::barDump($itemTrace, "itemTrace");
		$titles = $this->getNavigationsItems()->where("id", $itemTrace)->fetchPairs(null, "title");
		// \Tracy\Debugger::barDump($items, "items");

		$aliasStr = "";
		foreach ($titles as &$title) {
			$title= Strings::webalize($title);
		}
		$aliasStr = implode("-", $titles);
		\Tracy\Debugger::barDump($aliasStr, "aliasStr");

		// $alias = $this->saveAlias("navigation", $id, $aliasStr);
		// if ($alias) $item->update(["alias" => $alias]);
	}

	public function getItemIdByRequest($request, $multiple = false) {
		#\Tracy\Debugger::barDump($request, "request");

		$pars = $request->getParameters();
		#\Tracy\Debugger::barDump($pars, "pars");

		$route = ":" . $request->getPresenterName() . ":" . $pars["action"];
		#\Tracy\Debugger::barDump($route, "route");
		unset($pars["action"]);
		$jsonPars = json_encode($pars);
		#\Tracy\Debugger::barDump($jsonPars, "jsonPars");

		$items = $this->getNavigationsItems()->where("route", $route);

		$result = $multiple ? [] : null;

		foreach ($items as $item) {
			$itemPars = !empty($item->params) ? json_decode($item->params, true) : [];
			#\Tracy\Debugger::barDump($itemPars, "itemPars");

			$finded = !count(array_diff($itemPars, $pars)) ? $item->id : null;

			if ($finded) {
				if ($multiple) {
					$result[] = $item->id;
				} else {
					$result = $item->id;

					break;
				}
			}
		}

		#\Tracy\Debugger::barDump($result, "items");

		return $result;
	}

	public function getItemTrace($id, $trace = []) {
		#\Tracy\Debugger::barDump($trace, "trace");
		$item = $this->getNavigationItem($id);

		if (!$item) return;

		$trace[$item->id] = $item;

		if ($item->parent) {
			$trace = $this->getItemTrace($item->parent, $trace);
		}

		return $trace;
	}

	public function getBreadcrumbs($trace) {
		// \Tracy\Debugger::barDump($trace, "trace");
		$trace = array_reverse($trace, true);

		$breadcrumbs = [];
		foreach ($trace as $item) {
			$breadcrumbs[] = $item->title;
		}

		// \Tracy\Debugger::barDump($trace, "trace");
		return $breadcrumbs;
	}

	public function setLinkGenerator(Nette\Application\LinkGenerator $linkGenerator) {
		$this->LinkGenerator = $linkGenerator;

		return $this;
	}

	public function getItemSlugTrace($id) {
		$trace = $this->getItemTrace($id);
		// \Tracy\Debugger::barDump($trace, "getItemTraceSlug trace");

		if (!$trace) return;

		$trace = array_reverse($trace, true);

		$slug = "";
		$len = count($trace);
		$i = 0;
		foreach ($trace as $item) {
			$i++;
			$slug .= $item->short;
			if ($i < $len) {
				$slug .= "/";
			}
		}

		return $slug;
	}

	public function getItemBySlugTrace($trace) {
		$trace = trim($trace, "/");
		$slugs = explode("/", $trace);
		// \Tracy\Debugger::barDump($slugs, "slugs");

		$len = $slugs;
		$i = 0;
		$parent = null;
		foreach ($slugs as $slug) {
			$i++;
			$item = $this->getNavigationsItems()->where([
				"short" => $slug,
				"parent" => $parent
			])->fetch();
			// \Tracy\Debugger::barDump($item->title, "item");
			if ($item) $parent = $item->id;
		}

		return !empty($item) ? $item : null;
	}

	public function getParentItemByRoute($route, $params = null) {
		$items = $this->getNavigationsItems()->order("parent")->order("order")->where("route", $route);

		foreach ($items as $item) {
			// array_intersect(array1, array2)
			if ($route == $item->route) {
				if ($params) {
					$pars = $item->params ? Json::decode($item->params, Json::FORCE_ARRAY) : [];

					if (!array_diff($params, $pars)) {
						return $item;
					}
				} else {
					return $item;
				}
			}
		}
	}

	public function getNavigationItemPars($id) {
		$item = $this->getNavigationItem($id);

		if ($item && $item->params) {
			return Json::decode($item->params);
		} else {
			return null;
		}
	}

}