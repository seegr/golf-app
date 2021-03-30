<?php

namespace App\CoreModule\Model;

use Nette;
use Nette\Utils\Strings;


class CategoriesManager extends BaseManager {

	const
		TABLE_CATEGORIES = "categories";


	public function getCategories() {
		return $this->db->table(self::TABLE_CATEGORIES)->order("title");
	}

	public function getCategory($id) {
		if (is_numeric($id)) {
			return $this->getCategories()->get($id);
		} else {
			return $this->getCategories()->where("short", $id)->fetch();
		}
	}

	public function saveCategory($vals) {
		$data = [
			"title" => $vals->title,
			"short" => $vals->short ? Strings::webalize($vals->short) : Strings::webalize($vals->title)
		];

		if ($vals->id) {
			$id = $vals->id;

			$this->getCategories()->get($id)->update($data);
		} else {
			$id = $this->getCategories()->insert($data);
		}

		return $id;
	}

}