<?php

namespace App\CoreModule\Model;

use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;


class PersonsManager extends BaseManager {

	public function getPersons($type = null) {
		$sel = $this->db->table("persons");

		if ($type) $sel->where("type", $type);

		$sel->select("*, CONCAT(firstname, ' ', lastname) AS fullname");
		$sel->select("CONCAT(street, ' ', city, ' ', zipcode) AS address");

		return $sel;
	}

	public function getPerson($id) {
		return $this->getPersons()->get($id);
	}

	public function savePerson($vals, $forceInsert = false) {
		$vals = ArrayHash::from($vals);
		bdump($vals, "vals");

		$data = [
			"id" => !empty($vals->id) ? $vals->id : null,
			"firstname" => $vals->firstname,
			"lastname" => $vals->lastname,
			"email" => $vals->email,
			"tel" => !empty($vals->tel) ? $vals->tel : null,
			"city" => !empty($vals->city) ? $vals->city : null,
			"street" => !empty($vals->street) ? $vals->street : null,
			"zipcode" => !empty($vals->zipcode) ? $vals->zipcode : null,
			"country" => !empty($vals->country) ? $vals->country : null,
			"note" => !empty($vals->note) ? $vals->note : null,
			"type" => !empty($vals->type) ? $vals->type : null,
			"name" => !empty($vals->name) ? $vals->name : null,
			"ico" => !empty($vals->ico) ? $vals->ico : null,
			"dic" => !empty($vals->dic) ? $vals->dic : null
		];

		if (!empty($vals->type) && $vals->type == "hostel") {
			$data["id_card"] = $vals->id_card;
			$data["birth"] = !empty($vals->birth) ? new DateTime($vals->birth) : null;
		}

		if (!empty($vals->id) && !$forceInsert) {
			$id = $vals->id;

			$this->getPerson($id)->update($data);
		} else {
			$id = $this->getPersons()->insert($data);
		}

		return $id;
	}

}