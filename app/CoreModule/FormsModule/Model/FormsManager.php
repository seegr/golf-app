<?php

namespace App\CoreModule\FormsModule\Model;

use Nette\Utils\Strings;
use Nette\Utils\Json;


class FormsManager extends \App\CoreModule\Model\BaseManager {

	const
		TABLE_FORMS = "forms",
		TABLE_FORMS_FIELDS = self::TABLE_FORMS . "_fields",
		TABLE_FORMS_FIELDS_OPTIONS = self::TABLE_FORMS . "_fields_options",
		TABLE_FORMS_FIELDS_TYPES = self::TABLE_FORMS . "_fields_types",
		TABLE_FORMS_RECORDS = self::TABLE_FORMS . "_records",
		FAST_REG_ALERT = "Využili jste rychlé registrace, prosím potvrďte přihlášení doplněním osobních údajů o účastníkovi v seznamu Vašich registrací.";

	public function getForms() {
		return $this->db->table(self::TABLE_FORMS);
	}

	public function getForm($id) {
		return $this->getForms()->get($id);
	}

	public function saveForm($vals) {
		$data = [
			"title" => $vals->title,
			"type" => !empty($vals->type) ? $vals->type : null,
			"old_form" => !empty($vals->old_form) ? $vals->old_form : null,
			"intro_text" => $vals->intro_text,
			"end_text" => $vals->end_text
		];

		if (!empty($vals->id)) {
			$id = $vals->id;

			$this->getForm($id)->update($data);
		} else {
			$id = $this->getForms()->insert($data);
		}

		return $id;
	}

	public function getFormsFields() {
		return $this->db->table(self::TABLE_FORMS_FIELDS);
	}

	public function getFormFields($id) {
		// \Tracy\Debugger::barDump($id, "form id");
		return $this->getFormsFields()->where("form", $id);
	}

	public function getFormField($id) {
		return $this->getFormsFields()->get($id);
	}

	public function saveFormField($v) {
		\Tracy\Debugger::barDump($v, "v");
		$type = $this->getFormFieldType($v->type);

		$data = [
			"form" => $v->form,
			"type" => $v->type,
			"label" => $v->label,
			"required" => $v->required,
			"in_summary" => $v->in_summary,
			"summary_label" => $v->summary_label,
			"desc" => $v->desc,
			"admin" => $v->admin,
			"active" => $v->active
		];

		if (!empty($v->id)) {
			$id = $v->id;

			$this->getFormField($id)->update($data);
		} else {
			$data["order"] = $this->getFormFields($v->form)->max("order") + 1;
			$data["hash"] = $this->generateUniqueHash(self::TABLE_FORMS_FIELDS, "hash", 20, "field_");
			if ($type->short == "email" && !$this->getFormEmailField($v->form)) {
				$name = "e_mail";
			} else {
				$name = str_replace("-", "_", Strings::webalize($v->label));
			}
			$data["name"] = $name;

			$id = $this->getFormsFields()->insert($data);
		}

		return $id;
	}

	public function getFormsFieldsOptions() {
		return $this->db->table(self::TABLE_FORMS_FIELDS_OPTIONS);
	}

	public function getFormFieldOptions($id) {
		return $this->getFormsFieldsOptions()->where("field", $id);
	}

	public function getFormFieldOption($id) {
		return $this->getFormsFieldsOptions()->get($id);
	}

	public function saveFormFieldOption($v) {
		\Tracy\Debugger::barDump($v, "vals");
		$data = [
			"field" => $v->field,
			"value" => Strings::webalize($v->label),
			"label" => $v->label,
			"selects_limit" => $v->selects_limit,
			"active" => $v->active
		];

		if (!empty($v->id)) {
			$id = $v->id;

			$this->getFormFieldOption($id)->update($data);
		} else {
			$id = $this->getFormsFieldsOptions()->insert($data);
		}

		return $id;
	}

	public function deleteForm($id) {
		$this->getForm($id)->delete();
	}

	public function getFormsFieldsTypes() {
		return $this->db->table(self::TABLE_FORMS_FIELDS_TYPES);
	}

	public function getFormFieldType($id) {
		return $this->getFormsFieldsTypes()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();
	}

	// public function getFormsData() {
	// 	return $this->db->table(self::TABLE_FORMS_DATA);
	// }

	// public function getFormData($id, $toArray = false) {
	// 	$res = $this->getFormsData()->where([
	// 		"form" => $id
	// 	]);

	// 	$data = [];
	// 	if ($toArray) {
	// 		foreach ($res as $d) {
	// 			$data[$d->id] = [];
	// 			$item = Json::decode($d->data, true);
	// 			$data[$d->id]["id"] = $d->id;
	// 			$data[$d->id] = $data[$d->id] + $item;
	// 		}

	// 		return $data;
	// 	} else {
	// 		return $res;
	// 	}
	// }

	public static function getFormColumnsFromJson($data) {
		$cols = [];

		$data = array_values($data);
		\Tracy\Debugger::barDump($data, "data");
		foreach ($data[0] as $col => $d) {
			$cols[] = $col;
		}

		return $cols;
	}

	// public function getFormColumns($id) {
	// 	$form = $this->getForm($id);

	// 	return $form
	// }

	// public function saveFormData($formId, $vals) {
	// 	\Tracy\Debugger::barDump($vals, "vals");
	// 	$id = !empty($vals->id) ? $vals->id : null;

	// 	unset($vals->buttons, $vals->id);

	// 	$json = Json::encode($vals);
	// 	\Tracy\Debugger::barDump($json, "json");

	// 	$data = [
	// 		"form" => $formId,
	// 		"data" => $json
	// 	];

	// 	if ($id) {
	// 		$this->getFormsData()->get($id)->update($data);
	// 	} else {
	// 		$id = $this->getFormsData()->insert($data);
	// 	}

	// 	return $id;
	// }

	public function getFormsRecords() {
		return $this->db->table(self::TABLE_FORMS_RECORDS);
	}

	public function getFormRecords($id, $toArray = false, $whereArgs = [], $order = null) {
		$res = $this->getFormsRecords()->where(self::TABLE_FORMS_RECORDS . ".form", $id);

		$res->where($whereArgs);

		if ($order) $res->order($order);

		if ($toArray) {
			$arr = $this->fetchFormRecords($res, $id);
			return $arr;
		} else {
			return $res;
		}
	}

	public function saveRecord($formId, $vals) {
		return $this->saveFormRecord($formId, $vals);
	}

	public function saveFormRecord($formId, $vals) {
		$data = is_object($vals) || is_array($vals) ? Json::encode($vals) : $vals;
		// \Tracy\Debugger::barDump($formId, "formId");
		// \Tracy\Debugger::barDump($data, "data");

		$dataReal = [];
		$fields = $this->getFormFields($formId)->fetchPairs("name", "label");
		foreach ($vals as $fieldId => $val) {
			if ($fieldId == "id" || !isset($fields[$fieldId])) continue;
			$valTitle = $fields[$fieldId];
			$dataReal[$valTitle] = $val;
		}
		$dataReal = Json::encode($dataReal);

		$dataArr = [
			"data" => $data,
			"data_real" => $dataReal
		];

		if (!empty($vals->id) || !(empty($vals->hash))) {
			$id = !empty($vals->id) ? $vals->id : $vals->hash;

			$dataArr["edited"] = new \DateTime;

			$this->getFormRecord($id)->update($dataArr);
		} else {
			$rec = $this->getFormsRecords()->insert($dataArr + [
				"form" => $formId,
				"hash" => $this->generateUniqueHash(self::TABLE_FORMS_RECORDS, "hash", $length = 30)
			]);

			$id = $rec->id;
		}

		return $id;
	}

	public function getRecord($id, $toArray) {
		return $this->getFormRecord($id, $toArray);
	}

	public function getFormRecord($id, $toArray = false) {
		$res = $this->getFormsRecords()->whereOr([
			"id" => $id,
			"hash" => $id
		])->fetch();

		if ($toArray) {
			if (!$res) return [];

			$data = $res->toArray();
			$data = $data + Json::decode($data["data"], true);
			unset($data["data"]);

			// \Tracy\Debugger::barDump($data, "data");

			return $data;
		} else {
			return $res;
		}
	}

	// public static function parseFormData($vals) {
	// 	return $json = Json::encode($vals);
	// }

	/*public static function parseJsonToArray($json) {
		\Tracy\Debugger::barDump($json, "json");
		$arr = Json::decode($json, true);

		$data = [];
		foreach ($arr as $d) {
			$data[$d->id] = [];
			$item = Json::decode($d->data, true);
			$data[$d->id]["id"] = $d->id;
			$data[$d->id] = $data[$d->id] + $item;
		}

		return $data;
	}*/

	public static function parseRecordsSelectionToArray($sel, $dataCol = "data") {
		$arr = [];
		foreach ($sel as $r) {
			// \Tracy\Debugger::barDump($r, "r");
			$arr[$r->id] = [];

			foreach ($r as $col => $val) {
				if ($col == $dataCol) continue;
				$arr[$r->id][$col] = $val;
			}

			$data = Json::decode($r->$dataCol, true);
			foreach ($data as &$val) {
				if (is_array($val)) $val = implode("; ", $val);
			}
			// \Tracy\Debugger::barDump($data, "data");
			$arr[$r->id] = $arr[$r->id] + $data;
		}

		// \Tracy\Debugger::barDump($arr, "arr");

		return $arr;
	}

	public function getFormEmailField($id) {
		foreach ($this->getFormFields($id) as $field) {
			if ($field->ref("type")->short == "email")
				return $field->name;
		}

		return null;
	}

	public function getFormNameFields($id) {
		$fields = [];
		foreach ($this->getFormFields($id)->order("order") as $field) {
			if (in_array($field->name, ["firstname", "lastname"])) {
				$fields[] = $field->name;
			}
		}

		\Tracy\Debugger::barDump($fields, "name fields");

		return $fields ? $fields : null;
	}

	public function getSubmitterName($id) {
		$record = $this->getFormRecord($id);
		$nameFields = $this->getFormNameFields($record->form);
		// \Tracy\Debugger::barDump($nameFields, "nameFields");

		if (count($nameFields) > 1) {
			$vals = $this->getFormRecord($id, true);
			// \Tracy\Debugger::barDump($vals, "vals");
			$name = "";
			foreach ($nameFields as $field) {
				$name .= isset($vals[$field]) ? $vals[$field] : "";
				if (next($nameFields)) {
					$name .= " ";
				}
			}

			return !empty(trim($name)) ? $name : null;
		} else {
			return null;
		}
	}

	public function mergeColumnsRecordArray($id, $record) {
		$fields = $this->getFormFields($id);

		$arr = [];
		foreach ($fields as $f) {
			$arr[$f->label] = !empty($record[$f->name]) ? $record[$f->name] : null;
		}

		return $arr;
	}

	public function getFormRecordRealData($id) {
		$record = $this->getFormRecord($id);

		if (!$record) return null;

		return Json::decode($record["data_real"]);
	}

	public function fetchFormRecords($records, $formId = null) {
		// $fields = $this->getFormFields($formId)->fetchPairs(null, "name");
		// \Tracy\Debugger::barDump($fields, "fields arr");
		
		$arr = [];
		foreach ($records as $r) {
			// \Tracy\Debugger::barDump($r, "r");
			$arr[$r->id] = [];

			foreach ($r as $col => $val) {
				if ($col == "data") continue;
				$arr[$r->id][$col] = $val;
				// \Tracy\Debugger::barDump($col, "col");
				// \Tracy\Debugger::barDump($val, "val");
			}

			$data = Json::decode($r->data, true);
			// \Tracy\Debugger::barDump($data, "data");
			foreach ($data as $col => $val) {
				if (is_array($val)) $data[$col] = $val;
			}
			// \Tracy\Debugger::barDump($data, "data");

			// foreach ($fields as $field) {
			// 	if (!isset($data[$field])) $data[$field] = null;
			// }
			$arr[$r->id] = $arr[$r->id] + $data;
		}
		
		return $arr;
	}

}