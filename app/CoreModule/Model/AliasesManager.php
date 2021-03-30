<?php

namespace App\CoreModule\Model;

use Nette\Utils\ArrayHash;

use App;
use Nette;


class AliasesManager extends BaseManager {

	protected $reserved = [];

	const
		TABLE_ALIASES = "aliases",
		TABLE_ALIASES_REDIRECTS = "aliases_redirects";


	public function getAliases() {
		return $this->db->table(self::TABLE_ALIASES);
	}

	public function saveAlias($type, $item, $prefix = null, $append = null) {
		// \Tracy\Debugger::barDump("saveAlias");
		if ($this->hasItemAlias($type, $item)) return;

		$lastAlias = $this->getLastAlias($type, $item);
		$alias = $this->generateUniqueAlias($item, self::TABLE_ALIASES, "alias", $prefix);

		// \Tracy\Debugger::barDump($lastAlias, "lastAlias");
		// \Tracy\Debugger::barDump($alias, "alias");
		
		if ($alias == $lastAlias) return;

		$data = [
			"type" => $type,
			"item" => $item->id,
			"alias" => $alias
		];

		$this->saveTableItem(self::TABLE_ALIASES, $data);

		return $alias;
	}

	public function getItemByAlias($alias) {
		return $this->getAliases()->where("alias", $alias)->fetch();
	}

	public function getAliasByItem($type, $id) {
		return $this->getAliases()->where("type", $type)->where("item", $id)->order("id DESC")->fetch();
	}

	public function getAliasesRedirects() {
		return $this->db->table(self::TABLE_ALIASES_REDIRECTS);
	}

	public function addReserve($alias) {
		$this->reserved[] = $alias;
	}

}