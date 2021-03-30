<?php

namespace App\CoreModule\Model;

use Nette\Utils\DateTime;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\Random;

use Monty;


class TagsManager extends BaseManager {

	const
		TABLE_TAGS = "tags";
		

	public function getTags() {
		return $this->db->table("tags")->order("tags.title");
	}

	public function getTag($id) {
		return $this->getTags()->get($id);
	}

	public function tagSave($form = null, $vals) {
		$vals = ArrayHash::from($vals);

		$data = [
			"title" => $vals->title,
			"user" => $vals->user
		];

		if (isset($vals->id)) {
			$id = $vals->id;
			$this->getTag($id)->update($data);
		} else {
			$id = $this->getTags()->insert($data);
		}

		return $id;
	}

	public function tagDelete($id) {
		$this->getTag($id)->delete();
	}

	public function getSectionTags($id) {
		$select = $this->getTags();

		if (is_numeric($id)) {
			return $select->where(":" . SectionsManager::TABLE_SECTIONS_TAGS .".section", $id);
		} else {
			return $select->where(":" . SectionsManager::TABLE_SECTIONS_TAGS .".section.short", $id);
		}
	}

}