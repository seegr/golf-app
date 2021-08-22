<?php

namespace App\CoreModule\Model;

use Nette\Utils\ArrayHash;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

use Monty\Helper;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class ContentsManager extends BaseManager {

	const
		TABLE_CONTENTS = "contents",
		TABLE_CONTENTS_ATTACHMENTS = "contents_attachments",
		TABLE_CONTENTS_CONTACTS = "contents_contacts",
		TABLE_CONTENTS_IMAGES = "contents_images",
		TABLE_CONTENTS_FILES = "contents_files",
		TABLE_CONTENTS_EDITORS = "contents_editors",
		TABLE_CONTENTS_SOURCES = "contents_sources",
		TABLE_CONTENTS_TAGS = "contents_tags",
		TABLE_CONTENT_TYPES = "content_types",
		TABLE_CONTENTS_TYPES_CATEGORIES = "contents_types_categories",
		TABLE_CONTENTS_TYPES_TAGS = "contents_types_tags";


	protected $contentCustomFields = [];
	protected $contentExcludeFields = [];


	public function getContents($type = null) {
		$sel = $this->db->table(self::TABLE_CONTENTS);

		if ($type) $sel->where("contents.type", $this->getContentType($type)->id);

		return $sel;
	}

	public function getPublishedContents($type = null, $active = true) {
		// $contents = $this->getContents();

		$today = new \DateTime;
		// $contents->where([
		// 	"start >= ? OR start IS NULL" => $today,
		// 	"end <= ? OR end IS NULL" => $today
		// ]);
		// if ($active) $contents->where("active", true);

		$contents = $this->getContentsInPeriod($today, null, $type, $active);

		// if ($type) {
		// 	$type = $this->getContentType($type);
		// 	$contents->where("type", $type->id);
		// }

		return $contents;
	}

	public function getActiveContents($type = null) {
        return $this->getContents($type)->where("archived IS NULL OR archived = 0");
	}

	public function getContent($id, $formData = false) {
		// \Tracy\Debugger::barDump($id, "id");		
		$itemId = is_numeric($id) ? null : $this->getItemIdByAlias("contents", $id);
		// \Tracy\Debugger::barDump($itemId, "alias itemId");

		$id = $itemId ? $itemId : $id;

		$contents = $this->getContents();

		if (is_numeric($id)) {
			$content = $contents->get($id);
		} else {
			$content = $contents->whereOr([
				// "id" => $itemId ? $itemId : $id,
				"hash" => $id,
				"alias" => $id
			])->fetch();
		}

		if ($content && $formData) {
			// \Tracy\Debugger::barDump($content, "content");
			$data = [];

			foreach ($content as $col => $val) {
				if (!in_array($col, ["custom_fields"])) {
					$data[$col] = $val;
				} else if (!empty($val)) {
					$data[$col] = Json::decode($val);
				}
			}

			// \Tracy\Debugger::barDump($data, "data");
			$content = ArrayHash::from($data);
		}

		return $content;
	}

	public function contentSave($vals) {
		\Tracy\Debugger::barDump($vals, "vals");

		if (!is_object($vals)) $vals = ArrayHash::from($vals);

		$type = $this->getContentType($vals->type);

		$data = [
			"user" => $vals->user,
			"title" => $vals->title,
			// "short" => Strings::webalize($vals->title),
			"heading" => !empty($vals->heading) ? $vals->heading : null,
			"type" => $type->id,
			"category" => !empty($vals->category) ? $vals->category : null,
			"start" => !empty($vals->start) ? new \DateTime($vals->start) : null,
			"end" => !empty($vals->end) ? new \DateTime($vals->end) : null,
			"active" => !empty($vals->active) ? $vals->active : true,
			"short_text" => !empty($vals->short_text) ? $vals->short_text : null,
			"text" => !empty($vals->text) ? $vals->text : null,
			"registration" => !empty($vals->registration) ? $vals->registration : null,
			"reg_part" => !empty($vals->reg_part) ? $vals->reg_part : null,
			"reg_sub" => !empty($vals->reg_sub) ? $vals->reg_sub : null,
			"reg_form" => !empty($vals->reg_form) ? $vals->reg_form : null,
			"meta_keys" => !empty($vals->meta_keys) ? $vals->meta_keys : null,
			"meta_desc" => !empty($vals->meta_desc) ? $vals->meta_desc : null,
			"custom_fields" => !empty($vals->custom_fields) ? Json::encode($vals->custom_fields) : null			
		];

		if (!empty($vals->created)) {
			$data["created"] = is_object($vals->created) ? $vals->created : new \DateTime($vals->created);
		}

		if (!empty($vals->id)) {
			$id = $vals->id;
			$content = $this->getContent($vals->id);
			$data["edited"] = new \DateTime;
			$content->update($data);
		} else {
			$data["hash"] = $this->generateUniqueHash(self::TABLE_CONTENTS);

			$content = $this->getContents()->insert($data);

			$id = $content->id;
			// $this->itemSave("contents", $id);
		}

		$type = $this->getContentType($vals->type);

		if (!empty($vals->editors))
		$this->saveReferences(self::TABLE_CONTENTS_EDITORS, "content", $id, "user", $vals->editors);
		if (!empty($vals->tags))
		$this->saveReferences(self::TABLE_CONTENTS_TAGS, "content", $id, "tag", $vals->tags);
		if (!empty($vals->categories))
		$this->saveReferences(self::TABLE_CONTENTS_CATEGORIES, "content", $id, "category", $vals->categories);

		if (!empty($vals->contacts)) {
			$this->saveContentContacts($id, $vals->contacts);
			foreach ($vals as $name => $val) {
				if ($contactType = $this->isFieldContact($name)) {
					$this->saveContentContacts($id, $val, $contactType);
				}
			}
		}

		if (!empty($vals->sources))

		$this->saveContentSources($id, $vals->sources);



		return $id;
	}

	public function saveContent($vals, $f = null) {
		return $this->contentSave($vals, $f);
	}

	public function isFieldContact($name) {
		if (strrpos($name, "contacts") !== false) {
			\Tracy\Debugger::barDump($name, "je contact field");
			if (strrpos($name, "_") !== false) {
				$type = explode("_", $name);
				$type = $type[1];

				\Tracy\Debugger::barDump($type, "type");

				return $type;
			}
		} else {
			return;
		}
	}

	public function getContentsTypesFields() {
		return $this->db->table(self::TABLE_CONTENTS_TYPES_FIELDS);
	}

	public function getContentTypeFields($type) {
		if (is_numeric($type) || is_object($type)) {
			return $this->getContentsTypesFields()->where("type", $type);
		} else {
			return $this->getContentsTypesFields()->where("type.short", $type);
		}
	}

	public function getContentsTypesFieldsVals() {
		return $this->db->table(self::TABLE_CONTENTS_TYPES_FIELDS_VALS);
	}

	public function getContentTypeFieldVal($content, $field) {
		return $this->getContentsTypesFieldsVals()->where("content", $content)->where("field", $field)->fetchField("val");
	}

	public function saveContentTypeFieldVal($content, $field, $val) {
		$this->getContentsTypesFieldsVals()->insert([
			"content" => $content,
			"field" => $field,
			"val" => $val
		]);
	}

	public function saveContentTypeFieldsVals($content, $vals, $type) {
		$fields = $this->getContentTypeFields($type)->fetchPairs(null, "name");
		\Tracy\Debugger::barDump($fields, "fields");

		if (!$fields) return;

		foreach ($vals as $field => $val) {
			if (in_array($field, $fields) && strpos($field, "contacts_") === false) {
				// \Tracy\Debugger::barDump($content, "content");
				// \Tracy\Debugger::barDump($field, "field");
				// \Tracy\Debugger::barDump($val, "val");
				$this->getContentsTypesFieldsVals()->where("content", $content)->where("field", $field)->delete();
				$this->saveContentTypeFieldVal($content, $field, $val);
			}
		}
	}

	public function getContentsAttachments() {
		return $this->db->table(self::TABLE_CONTENTS_ATTACHMENTS);
	}

	public function getContentAttachments($id) {
		return $this->getContentsAttachments()->where("content", $id);
	}

	public function saveContentAttachment($content, $file, $title = null) {
		$maxOrder = $this->getContentAttachments($content)->max("order");

		$data = [
			"content" => $content,
			"file" => $file,
			"title" => $title,
			"order" => $maxOrder + 1
		];

		return $this->getContentsAttachments()->insert($data);
	}

	public function getContentAttachment($id) {
		return $this->getContentsAttachments()->get($id);
	}

	public function getContentsContacts($type = null) {
		$sel = $this->db->table(self::TABLE_CONTENTS_CONTACTS);

		if ($type) $sel->where("type", $type);

		return $sel;
	}

	public function getContentContacts($id, $type = null) {
		$sel = $this->getContentsContacts()->where("content", $id)->where("type", $type);

		return $sel->select("emp.*");
	}

	public function getContentContactsArray($id) {
		$content = $this->getContent($id);
		$typeFields = $this->getContentTypeFields($content->type)->fetchPairs(null, "name");

		$types = [];
		foreach ($typeFields as $name) {
			\Tracy\Debugger::barDump($name, "name");
			if ($cName = $this->isFieldContact($name)) {
				$types[] = $cName;
			}
		}

		$arr = [];
		$arr["contacts"] = $this->getContentContacts($id)->fetchPairs(null, "id");
		$arr["contacts"] = count($arr["contacts"]) ? $arr["contacts"] : null;
		foreach ($types as $type) {
			\Tracy\Debugger::barDump($type, "contact type");
			$arr["contacts_" . $type] = $this->getContentContacts($id, $type)->fetchPairs(null, "id");
			$arr["contacts_" . $type] = count($arr["contacts_" . $type]) ? $arr["contacts_" . $type] : null;
		}

		return $arr;
	}

	/*public function getContentTypeFormContactFields($id) {
		$fields = $this->getContentTypeFields($id);

		$contFields = [];
		foreach ($fields as $field) {
			$contFields[] = strpos($field->name, "contacts_"
		}
	}*/

	public function saveContentContacts($id, $contacts = [], $type = null) {
		\Tracy\Debugger::barDump($contacts, "contacts");
		$this->getContentContacts($id, $type)->delete();

		if ($contacts) 
		foreach ($contacts as $contact) {
			$this->getContentsContacts()->insert([
				"content" => $id,
				"emp" => $contact,
				"type" => $type
			]);
		}
	}

	public function getContentsBoxes() {
		return $this->db->table(self::TABLE_CONTENTS_BOXES);
	}

	public function getContentBoxes($typeCol = null, $id = null, $toArray = false) {
		if (!$typeCol) {
			return $this->db->table(self::TABLE_CONTENTS_BOXES);
		} else {
			$sel = $this->db->table(self::TABLE_CONTENTS_BOXES);

			if ($id) {
				$sel->where($typeCol, $id);
			} else {
				$sel = [];
			}

			if ($toArray) {
				return $this->boxesToPosArray($sel);
			} else {
				return $sel;
			}
		}
	}

	public function getBoxes($id, $toArray = false) {
		return $this->getContentBoxes($id, $toArray);
	}

	public function getContentsImages() {
		return $this->db->table(self::TABLE_CONTENTS_IMAGES);
	}

	public function getContentImages($id) {
		return $this->getContentsImages()->where("content", $id);
	}

	public function getContentImage($id) {
		return $this->getContentsImages()->get($id);
	}

	public function contentImageSave($content, $fileId) {
		$maxOrder = $this->getContentsImages()->where("content", $content)->max("order");

		$this->getContentsImages()->insert([
			"content" => $content,
			"file" => $fileId,
			"order" => $maxOrder + 1
		]);
	}

	public function saveContentImage($vals) {
		\Tracy\Debugger::barDump($vals, "save content image");
		$vals = is_array($vals) ? ArrayHash::from($vals) : $vals;

		$data = [
			"title" => $vals->title,
			"desc" => $vals->desc
		];
		
		if (empty($vals->id)) {
			$contentId = $this->getContent($vals->content)->id;
			\Tracy\Debugger::barDump($contentId, "contentId");

			$data["content"] = $contentId;
		}

		if (!empty($vals->file)) {
			$data["file"] = $vals->file;
		}
		if (!empty($vals->order)) {
			$data["order"] = $vals->order;
		}

		if (!empty($vals->id)) {
			$id = $vals->id;
			$this->getContentImage($id)->update($data);
		} else {
			$maxOrder = $this->getContentsImages()->where("content", $contentId)->max("order");

			$data["order"] = $maxOrder + 1;

			$id = $this->getContentsImages()->insert($data);
		}

		return $id;
	}

	public function getContentsFiles() {
		return $this->db->table(self::TABLE_CONTENTS_FILES);
	}

	public function getContentFiles($id) {
		return $this->getContentsFiles()->where("content", $id);
	}

	public function saveContentFile($content, $file) {
		\Tracy\Debugger::barDump($content, "content");
		\Tracy\Debugger::barDump($file, "file");
		return $this->getContentsFiles()->insert(["content" => $content, "file" => $file]);
	}

	public function deleteContent($id) {
		$this->getContent($id)->delete();
	}

	public function getContentsEditors() {
		return $this->db->table(self::TABLE_CONTENTS_EDITORS)->select("user.*");
	}

	public function getContentEditors($id) {
		return $this->getContentsEditors()->where("content", $id)->select("user.*");
	}

	public function getContentsTags() {
		return $this->db->table(self::TABLE_CONTENTS_TAGS);
	}

	public function getContentTags($id) {
		return $this->getContentsTags()->where("content", $id)->select("tag.*");
	}

	public function boxesToPosArray($selection) {
		$arr = [
			"left" => [],
			"right" => []
		];

		foreach ($selection as $box) {
			$pos = $box->position;
			$box = $box->ref("box");

			$arr[$pos][] = $box->toArray();
		}

		return ArrayHash::from($arr);
	}

	public function getContentTypes($onlyActive = true) {
		$sel = $this->db->table(self::TABLE_CONTENT_TYPES);

		if ($onlyActive) $sel->where("active", true);

		return $sel;
	}

	public function getContentType($id) {
		$sel = $this->getContentTypes()->whereOr([
			"id" => $id,
			"short" => $id
		])->fetch();

		return $sel;
	}

	public function getContentTypeCategories($type = null) {
		$sel = $this->db->table(self::TABLE_CONTENTS_TYPES_CATEGORIES)->whereOr([
			"type" => $type,
			"type.short" => $type
		]);

		$sel->select("category.*");

		return $sel;
	}

	public function getContentTypeTags($type = null) {
		$sel = $this->db->table(self::TABLE_CONTENTS_TYPES_TAGS)->whereOr([
			"type" => $type,
			"type.short" => $type
		]);

		$sel->select("tag.*");

		return $sel;
	}

	public function contentTypeSave($vals) {
		$data = [
			"title" => $vals->title,
			"short" => $vals->short ? Strings::webalize($vals->short) : Strings::webalize($vals->title),
			"text" => $vals->text
		];

		if ($vals->id) {
			$id = $vals->id;

			$this->getContentTypes()->get($id)->update($data);
		} else {
			$id = $this->getContentTypes()->insert($data);
		}

		$this->saveReferences(self::TABLE_CONTENTS_TYPES_CATEGORIES, "type", $id, "category", $vals->categories);
		$this->saveReferences(self::TABLE_CONTENTS_TYPES_TAGS, "type", $id, "tag", $vals->tags);
	}

	public function getContentsSources() {
		return $this->db->table(self::TABLE_CONTENTS_SOURCES);
	}

	public function getContentSources($id) {
		return $this->getContentsSources()->where("content", $id);
	}

	public function saveContentSources($id, $sources) {
		$this->getContentSources($id)->delete();

		foreach ($sources as $source) {
			if (!empty($source->url)) $this->getContentsSources()->insert([
				"content" => $id,
				"title" => $source["title"],
				"url" => $source["url"]
			]);
		}
	}

	public function getContentData($id, $formData = false) {
		$type = $this->getContent($id)->type;
		$fields = $this->getContentTypeFields($type)->fetchPairs(null, "name");

		$data = [];
		foreach ($fields as $field) {
			if (!$type = $this->isFieldContact($field)) {
				$data[$field] = $this->getContentTypeFieldVal($id, $field);
			} else {
				$emps = $this->getContentContacts($id, $type);

				if ($formData) {
					$contacts = Helper::arrayHashToArray($emps->fetchPairs(null, "id"));
				} else {
					$contacts = $emps;
				}

				\Tracy\Debugger::barDump($contacts, "contacts");
				$data[$field] = $contacts;
			}
		}

		return $formData ? $data : ArrayHash::from($data);
	}

	public function getContentsCategories() {
		return $this->db->table(self::TABLE_CONTENTS_CATEGORIES);
	}

	public function getContentCategories($id) {
		return $this->getContentsCategories()->where("content", $id);
	}

	public function getLatesGalleriesPhotos($limit = 10) {
		return $this->getContentsImages()->where("content.type.short", "photos")->limit($limit);
	}

	public function getContentsInPeriod($start, $end = null, $type = null, $onlyActive = false) {
		$sel = $this->getContents();

		if ($type) {
			$type = $this->getContentType($type);
			$sel->whereOr([
				"type" => $type->id,
				"type.short" => $type->id
			]);
		}

		if ($onlyActive) {
            $sel->where("contents.active", true);
            $sel->where("archived IS NULL OR archived = 0");
        }

		// if ($start && $end) {
		// 	$sel = $sel->where("
		// 			(start >= ? AND start <= ?) OR
		// 			(end >= ? AND end <= ?) OR 
		// 			(start <= ? AND end >= ?) OR 
		// 			(start >= ? AND end <= ?) OR
		// 		", $start, $end, $start, $end, $start, $end, $start, $end);
		// } else {
		// }

		$conds = [
			"start <= ? AND end IS NULL" => $start,
			"end >= ? AND start IS NULL" => $start
		];

		if ($start && !$end)  {
			$conds["created <= ? AND start IS NULL AND end IS NULL"] = $start;
		} else if ($start && $end) {
			$conds["created <= ? AND start IS NULL AND end IS NULL"] = $end;
		}

		if ($start && $end) {
			$conds["start >= ? AND start <= ?"] = [$start, $end];
			$conds["end >= ? AND end <= ?"] = [$start, $end];
			$conds["start <= ? AND end >= ?"] = [$start, $end];
			$conds["start >= ? AND end <= ?"] = [$start, $end];
		} else {
			$conds["start <= ? AND end >= ?"] = [$start, $start];
		}

		// \Tracy\Debugger::barDump($conds, "conds");

		$sel = $sel->whereOr($conds);

		$sel->select("contents.*");

		return $sel;
	}

	public function getContentFromToday($type = null, $active = null) {
		$today = new DateTime;

		return $this->getPublishedContents($type, $active);
	}

	public function getGalleries() {
		return $this->getContents("photos");
	}

	public function getGalleriesImages() {
		return $this->getContentsImages()->where("content.type.short", "photos");
	}

	public function getGalleryImages($id) {
		return $this->getGalleriesImages()->where("content", $id);
	}

	public function getContentCustomFields($type) {
		$type = $this->getContentType($type);

		if (!$type) return;

		$type = $type->short;
		// \Tracy\Debugger::barDump($type, "type");
		return isset($this->contentCustomFields[$type]) ? $this->contentCustomFields[$type] : [];
	}

	public function addContentCustomFields($type, $fields) {
		$this->contentCustomFields[$type] = $fields;

		return $this;
	}

	/*public function getContentCustomData($id) {
		$content = $this->getContent($id);

		if (!$content) return null;

		return Json::decode($content->data);
	}*/

	public function eventRepeatSave($vals) {
		\Tracy\Debugger::barDump($vals, "event date form");
		\Tracy\Debugger::barDump($vals->type, "type");
		$event = $this->getEvent($vals->parent);

		return;

		$eventType = $this->getEventType($eventType);

		if (!$event->start) {
			$firstChild = $this->getEventFirstChild($event->id);
			$event = $firstChild;
		}
		// \Tracy\Debugger::barDump($event, "event");

		$event_time_start = $event->start->format("H:i");
		$event_time_end = $event->end->format("H:i");

		$start = new \DateTime($vals->start);
		if ($vals->end) {
			$end = new \DateTime($vals->end);
		} else {
			$end = new \DateTime($vals->start);
		}
		// \Tracy\Debugger::barDump($start, "start");
		// \Tracy\Debugger::barDump($end, "end");

		$diff = $start->diff($end);
		// \Tracy\Debugger::barDump($diff, "diff");

		switch ($vals->type) {
			case "days":
				$interval = Helper::getDateRangeInterval($start, $end->modify("23:59:59"));
			break;

			case "weekly":
				$interval = Helper::getDateRangeInterval($start, $end->modify("23:59:59"), "1 week");
			break;

			case "monthly":
				$interval = Helper::getDateRangeInterval($start, $end->modify("23:59:59"), "1 month");
			break;
		}

		if ($vals->type == "dates") {
			// \Tracy\Debugger::barDump($vals->dates, "dates");
			$dates = explode(",", trim($vals->dates));
		} else {
			foreach ($interval as $dt) {
				// \Tracy\Debugger::barDump($dt, "dt");
				$dayNum = $dt->format("N");
				// \Tracy\Debugger::barDump($dayNum, "loop day num");

				if ($vals->type == "days") {
					if (in_array($dayNum, $vals->days)) {
						// \Tracy\Debugger::barDump("den sedi - ukladam");
						$dates[] = $dt->format("j.n.Y");
					}
				} else {
					$eDayNum = $event->start->format("N");
					if ($eDayNum == $dayNum) {
						$dt = $dt;
					} else {
						$dt->modify($eDayNum - $dayNum . " days");
						if ($dt > $end) continue;
					}
					$dates[] = $dt->format("j.n.Y");
				}
			}
		}

		$data = ArrayHash::from([]);
		$data->parent = $vals->parent;

		\Tracy\Debugger::barDump($dates, "dates");
		// return;

		$ids = [];
		foreach ($dates as $date) {
			$diff = $event->start->diff($event->end);
			// \Tracy\Debugger::barDump($diff, "diff");

			$data->start = $event->start->modify($date);
			$endDateModify = "+" . $diff->days . " days";
			$endDate = (clone $data->start)->modify($endDateModify);
			$data->end = $event->end->modify($endDate->format("j.n.Y"));

			$ids[] = $this->eventDuplicateSave($event->id, $data);
		}

		return $ids;
	}

	public function getContentCustomData($id) {
		$content = $this->getContent($id);

		if ($customFields = $content->custom_fields) {
			$customFields = ArrayHash::from(Json::decode($customFields, true));
			$fields = $this->getContentCustomFields($content->ref("type")->short);
			\Tracy\Debugger::barDump($fields, "fields");
			\Tracy\Debugger::barDump($customFields, "customFields");

			$data = [];
			foreach ($fields as $fieldId => $fData) {
				if (isset($customFields->$fieldId)) {
					$fData["value"] = $customFields[$fieldId];
				} else {
					$fData["value"] = null;
				}
				$data[$fieldId] = $fData;
			}

			return ArrayHash::from($data);
		} else {
			return null;
		}
	}

	public function addContentExcludeFields(string $type, array $exclude = []): self
	{
		if ($exclude) {
			$this->contentExcludeFields[$type] = $exclude;
		}

		return $this;
	}

	public function getContentExcludeFields(string $type): ?array
	{
		return !empty($this->contentExcludeFields[$type]) ? $this->contentExcludeFields[$type] : [];
	}

}