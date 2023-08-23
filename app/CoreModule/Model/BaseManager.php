<?php

declare(strict_types=1);

namespace App\CoreModule\Model;

use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Utils\Random;


class BaseManager
{
	use Nette\SmartObject;

	const
		FOLDER_WWW = __DIR__ . "/../../www/",
		FOLDER_FONTS = __DIR__ . self::FOLDER_WWW . "fonts/",
		TABLE_ITEMS = "items",
		TABLE_SECTIONS = "sections",
		TABLE_SECTIONS_TAGS = "sections_tags",
		TABLE_ALIASES = "aliases",
		TABLE_CHARGES_TYPES = "charges_types",
		TABLE_DAYS = "days",
		TABLE_STATES = "states",
		TABLE_SETTINGS = "settings",
		TABLE_LANGS = "langs";
	
	const SETTINGS = [
		"appName" => "Název webu/aplikace",
		"og_image" => "Sociální sítě - obrázek",
		"content_header_image" => "Header image cropper",
		"content_header_image_default" => "Výchozí obrázek hlavičky",
		"item_no_image" => "No image IMG"
	];


	protected $db;

	public $appName, $wwwDir, $appDir, $maintenance, $homeRoute;


	public function __construct(Nette\Database\Context $db)
	{
		$this->db = $db;
	}

	// public function __construct(\Monty\Database\Context $db)
	// {
	// 	$this->db = $db;
	// }

	public function table($table)
	{
		return $this->db->table($table);
	}

	public function getTree($selection, $parent = null, $cols = [], $orderCol = null): array
	{
		//bdump($parent, "parent");
		if (is_string($selection)) $selection = $this->db->table($selection);
		if (!$orderCol) $selection->order("order");

		$defaultSelection = clone $selection;

		if (!$parent) {
			$selection->where("parent IS NULL");
		} else {
			$selection->where("parent", $parent);
		}

		$tree = [];

		foreach ($selection as $item) {
			$data["id"] = $item->id;
			$data["title"] = $item->title;

			$sel = clone $defaultSelection;

			$childs = $this->getTree($sel, $item->id, $cols);
			$data["childs"]	= $childs ? $childs : null;

			foreach ($cols as $col) {
				$data[$col] = $item->$col;
			}

			//$data = ArrayHash::from($data);
			//bdump($data, "data");
			
			$tree[] = ArrayHash::from($data);
		}

		return $tree;
	}

	public function getAliases() {
		return $this->db->table(self::TABLE_ALIASES);
	}

	public function getAlias($arg, $arg2 = null) {
		$selection = $this->getAliases();

		if (!$arg2) {
			return $selection->get($arg);
		} else {
			return $selection->where("type", $arg)->where("item", $arg2);
		}
	}

	public function hasItemAlias($type, $item) {
		$alias = Strings::webalize($item->title);

		$sel = $this->getAliases()->where([
			"type" => $type,
			"item" => $item->id,
			"alias LIKE " => "%$alias%"
		]);

		return count($sel) ? true : false;
	}

	public function generateUniqueAlias($item, $table = null, $column = "alias", $prefix = null, $append = null) {
		bdump($item, "item");
		bdump($item->title, "item title");
		$alias = "";
		if ($prefix) {
			if (!is_numeric($prefix)) {
				$alias .= Strings::webalize($prefix);
			} else {
				$alias .= $prefix;
			}
			$alias .= "-";
		}
		
		$alias .= Strings::webalize($item->title);
		$alias .= $append ? "-" . Strings::webalize($append) : null;
		// bdump($alias, "alias");

		$table = $table ? $table : self::TABLE_ALIASES;

		$sel = $this->db->table($table)->where($column, $alias);

		if (!(clone $sel)->count("*")) {
			return $alias;
		} else {
			// $append = $append ? $append + 1 : 1;
			// $last = $this->getAliases()->where("alias LIKE ?", "%$alias%")->order("id DESC")->fetchField("alias");
			// bdump($last, "last");
			// $last = explode("-", $last);
			// $last = end($last);
			// bdump($last, "last");
			// $last = is_numeric($last) ? $last + 1 : 1;
			// bdump($last, "last");

			return $this->generateUniqueAlias($item, $table, $column, $item->id);
		}
	}

	public function getLastAlias($type, $item) {
		$alias = $this->getAlias($type, $item)->fetchPairs(null, "alias");

		return end($alias);
	}

	public function getItemIdByAlias($type, $alias) {
		$selection = $this->getAliases()->where("type", $type)->where("alias", $alias)->fetch();

		if ($selection) {
			return $selection->item;
		}
	}

	public function itemOrderChange($item, $itemPrev, $itemNext, $items, $orderColumn = "order") {
		// $this->itemsReorder($items, $orderColumn);
		// bdump($item_id, "item_id");
		// bdump($prev_id, "prev_id");
		// bdump($next_id, "next_id");

		if ($item && is_string($item)) {
			bdump("item is string");
			$item = (clone $items)->where("id", $item)->fetch();
			bdump($item, "item fetch");
		}
		$items->order($orderColumn);
		$lastOrder = count($items);
		$itemOrder = $item->$orderColumn;

		if ($itemPrev && is_string($itemPrev)) {
			$itemPrev = (clone $items)->where("id", $itemPrev)->fetch();
		}
		if ($itemNext && is_string($itemNext)) {
			$itemNext = (clone $items)->where("id", $itemNext)->fetch();
		}

		$prevOrder = $itemPrev ? $itemPrev->$orderColumn : null;
		$nextOrder = $itemNext ? $itemNext->$orderColumn : null;

		// bdump($itemOrder, "itemOrder");
		// bdump($prevOrder, "prevOrder");
		// bdump($nextOrder, "nextOrder");
		// bdump($items->fetchAll(), "items");
		// bdump($item, "item");
		// bdump($itemPrev, "itemPrev");
		// bdump($itemNext, "itemNext");


		$newOrder = $nextOrder ? $nextOrder : $lastOrder;
		$orderStep = $newOrder + 1;

		$orderStep = 1;
		$afterItems = false;
		foreach ($items as $i) {
			$lItemOrder = $item->$orderColumn;

			if ($i->id == $item->id) {
				$i->update([$orderColumn => $newOrder]);
				continue;
			}

			if ($itemNext && $i->id == $itemNext->id) {
				$afterItems = true;
				$orderStep++;
			}

			$i->update([$orderColumn => $orderStep]);

			$orderStep++;
		}
	}

	public function changeItemOrder($selection, $itemId, $nextItemId, $prevItemId, $orderColumn = "order") {
		// bdump($selection->fetchAll(), "sel");
		$item = (clone $selection)->get($itemId);
		$itemNext = (clone $selection)->get($nextItemId);
		$itemPrev = (clone $selection)->get($prevItemId);
		// bdump($item, "item");

		$itemOrder = $item->$orderColumn;
		$prevOrder = $itemPrev ? $itemPrev->$orderColumn : null;
		$nextOrder = $itemNext ? $itemNext->$orderColumn : null;

		// bdump($itemOrder, "itemOrder");
		// bdump($prevOrder, "prevOrder");
		// bdump($nextOrder, "nextOrder");

		if ($itemOrder < $prevOrder) {
			bdump("nahoru");
			foreach ($selection as $item) {
				if ($item->$orderColumn != $itemOrder) {
					//** update items before
					if ($item->$orderColumn > $itemOrder && ($item->$orderColumn < $nextOrder || $nextOrder == null)) {
						$item->update([$orderColumn => $item->$orderColumn - 1]);
					}
				} else {
					//** update moving item
					$item->update([$orderColumn => $prevOrder]);
				}
			}
		} else {
			bdump("dolu");
			foreach ($selection as $item) {
				if ($item->$orderColumn != $itemOrder) {
					if ($item->$orderColumn < $itemOrder && ($item->$orderColumn > $prevOrder || $prevOrder == null)) {
						$item->update([$orderColumn => $item->$orderColumn + 1]);
					}
				} else {
					$item->update([$orderColumn => $nextOrder]);
				}
			}
		}

		// $this->itemsReorder($selection);
	}

	public function itemsReorder($items, $orderColumn = "order") {
		$items->order($orderColumn);
		bdump($items->fetchAll(), "reoder items");

		$i = 1;
		foreach ($items as $item) {
			// $data["order"] = $i;
			bdump($i, "order loop i");
			$item->update([
				$orderColumn => $i
			]);

			$i++;
		}
	}

	public function saveTableItem($table, $data) {
		bdump("saveTableItem");
		$table = $this->db->table($table);

		if (isset($data["id"]) && $data["id"]) {
			return $table->get($data["id"])->update($data);
		} else {
			return $table->insert($data);
		}
	}

	public function generateUniqueHash($table, $column = "hash", $length = 40, $prepend = null) {
		$hash = "";
		if ($prepend) $hash .= $prepend;
		$hash .= Random::generate($length, "a-z");

		if (!$this->db->table($table)->where($column)->count("*")) {
			return $hash;
		} else {
			$this->generateHashId($table, $column, $length);
		}
	}

	public function saveReferences($table, $item_col, $item_id, $ref_col, $ref_ids) {
		$this->db->table($table)->where($item_col, $item_id)->delete();

		// bdump($ref_ids, "saveReferences - ref_ids");
		if ($ref_ids) {
			foreach ($ref_ids as $id) {
				$this->db->table($table)->insert([
					$item_col => $item_id,
					$ref_col => $id
				]);
			}
		}
	}

	public function getRandomRows($sel, $limit = 1) {
		$sel = clone $sel;
		$rand = array_rand($sel->fetchAll(), $limit);
		// bdump($rand, "rand");

		$sel->where("id", $rand);

		return count($sel) == 1 ? $sel->fetch() : $sel;
	}

	public function generateUniqueShort($item, $sel = null, $short = null, $titleCol = "title", $shortCol = "short") {
		bdump($item, "item");
		$table = $item->getTable()->getName();
		bdump($table, "table");

		$short = Strings::webalize($short ? $short : $item->$titleCol);

		$sel = $sel ? $sel : $this->db->table($table);
		$sel->where("id != ?", $item->id)->where($shortCol, $short);
		bdump($sel->fetchAll(), "sel");

		if (count($sel)) {
			$short .= "-" . $item->id;
		}

		bdump($short, "short");

		// $item->update([$shortCol => $short]);

		return $short;
	}


	public function getSettings()
	{
		return $this->db->table(self::TABLE_SETTINGS);
	}

	public function getSettingRow($id)
	{
		return $this->getSettings()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();
	}

	public function getSetting($id)
	{
		$setting = $this->getSettingRow($id);

		if ($setting) {
			return $setting->val ? $setting->val : null;
		} else {
			return null;
		}
	}

	public function saveSetting($atr, $val)
	{
		bdump($atr, "atr");
		if ($setting = $this->getSettingRow($atr)) {
			bdump($setting, "setting");
			$id = $setting->id;
			$setting->update(["val" => $val]);
		} else {
			$id = $this->getSettings()->insert([
				"short" => $atr,
				"val" => $val
			]);
		}

		return $id;
	}

	public function setSetting($atr, $val) {
		return $this->saveSetting($atr, $val);
	}

	public function getLangs($activeOnly = false)
	{
		$sel = $this->db->table(self::TABLE_LANGS);

		if ($activeOnly) $sel->where("active", true);
		
		return $sel;
	}

	public function getLang($id)
	{
		return $this->getLangs()->whereOr([
			"id" => $id,
			"code" => $id
		])->fetch();
	}

	public function getDefaultLang()
	{
		return $this->getLangs()->where("default", true)->fetch();
	}

	public function getStates()
	{
		return $this->db->table(self::TABLE_STATES);
	}

	public function getState($id)
	{
		$sel = $this->getStates()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();

		return $sel;
	}

}