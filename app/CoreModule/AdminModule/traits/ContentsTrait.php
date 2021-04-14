<?php

namespace App\CoreModule\AdminModule\Traits;

use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Monty\Gallery;
use Monty\DataGrid;
use Monty\Dropzone;
use Monty\Modal;
use Nette\Database\Table\Selection;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;


trait ContentsTrait
{
	protected $tempId;
	protected $lastId;
	protected $id;


	public function contentForm($id, $type) {
		// \Tracy\Debugger::barDump("contentForm...");
		$template = $this->template;
		$form = $this->getForm();

		if ($id) {
			$content = $this->getContent($id);
			$type = $content->ref("type");
		} else {
			$type = $this->ContentsManager->getContentType($type);
		}
		$template->type = $type;

		$this->tempId = "contents_" . $type->short . "_images_" . $this->getUser()->id;

		// \Tracy\Debugger::barDump($form->getGroup("custom_fields"), "custom fields group");
		if ($customFieldsGroup = $form["custom_fields"]) {
			// \Tracy\Debugger::barDump($form["custom_fields"], "custom fields container");
			$template->_customFieldsGroup = $customFieldsGroup;
			$template->_customFields = !empty($form["custom_fields"]) ? $form["custom_fields"]->getControls() : [];
		}

		foreach ($this->ContentsManager->getContentExcludeFields($type->short) as $exField) {
			unset($form[$exField]);
		}
		
	}

	public function renderContentForm($id, $type) {
		\Tracy\Debugger::barDump("renderContentForm...");
		$user = $this->getUser();

		$template = $this->template;
		$form = $this->getForm();
		$gal = $this->getComponent("imagesGallery");

		$id = $id ? $id : $this->id;

		if ($id) {
			$content = $this->ContentsManager->getContent($id);
			$type = $content->ref("type");
			\Tracy\Debugger::barDump($content, "content");
			$form->setDefaults($this->ContentsManager->getContent($id, true));
			$form["created"]->setDefaultValue($content->created ? $content->created->format(self::DATETIME_FORMAT) : null);
			$form["start"]->setDefaultValue($content->start ? $content->start->format(self::DATETIME_FORMAT) : null);
			$form["end"]->setDefaultValue($content->end ? $content->end->format(self::DATETIME_FORMAT) : null);
			// $contentData = $this->ContentsManager->getContentData($id, true);
			// $form->setDefaults($contentData);
			// $form["editors"]->setDefaultValue($this->ContentsManager->getContentEditors($id)->fetchPairs(null, "id"));
			if (isset($form["tags"])) $form["tags"]->setDefaultValue($this->ContentsManager->getContentTags($id)->fetchPairs(null, "id"));
			$template->content = $content;
			$template->attachments = $this->ContentsManager->getContentAttachments($id)->order("order");

			$images = $this->ContentsManager->getContentImages($this->getParameter("id"));

			// \Tracy\Debugger::barDump($type->short, "type short");
			switch ($type->short) {
				case "gallery":
					if ($content->images_order) {
						$images->order("order $content->images_order");
					} else {
						$images->order("order ASC");
					}
					break;

				default:
					$images->order("order ASC");
					break;
			}

			$gal->setSortable();

			$this["eventDatesList"]->setDataSource($this->EventsManager->getEventDates($id));
		} else {
			$images = $this->FilesManager->getTempFiles($this->tempId);
		}

		\Tracy\Debugger::barDump("content images loop");
		foreach ($images as $image) {
			\Tracy\Debugger::barDump($image, "image");
			$file = $image->ref("file");
			$gal->addImage($file->url, $this->getThumb($file->id))
				->setId($image->id);
		}

		// \Tracy\Debugger::barDump($form, "form");
	}

	public function createComponentContentForm() {
		$form = $this->FormsFactory->contentForm($this->type);

		$sources = $this->ContentsManager->getContentSources($this->getParameter("id"))->fetchAssoc("id=");
		// bdump($sources, "sources");

		$form["sources"]->setDefaults($sources);

		$form["sources"]->onCreate[] = function() {
			$this->redrawControl("formWrap");
			$this->redrawControl("sources");
		};

		$form["save"]->onClick[] = function($btn, $vals) {
			\Tracy\Debugger::barDump($vals, "save");
			$this->saveContent($vals);
			$type = $this->ContentsManager->getContentType($vals->type);
			$this->redirect("contentsList", ["type" => $type->short]);
			// $this->redrawControl();
		};
		$form["save_stay"]->onClick[] = function($btn, $vals) {
			\Tracy\Debugger::barDump($vals, "save_stay");
			$id = $this->saveContent($vals);
			// \Tracy\Debugger::barDump($id, "id");
			$this->redirect("this", ["id" => $id]);
			$this->id = $id;
		};
		$form->onError[] = function($form) {
			\Tracy\Debugger::barDump($form->getErrors(), "errors");
		};

		return $form;
	}

	public function saveContent($vals) {
		\Tracy\Debugger::barDump($vals, "saveContent - vals");
		$id = $this->ContentsManager->contentSave($vals);
		$content = $this->ContentsManager->getContent($id);

		if (!empty($vals->image) && $vals->image->hasFile()) {
			// \Tracy\Debugger::barDump("saving image");
			$fileId = $this->FilesManager->uploadImage($vals->image, $this->getUser()->id);
			$content->update(["image" => $fileId]);
		}
		if (!empty($vals->header_image) && $vals->header_image->hasFile()) {
			$fileId = $this->FilesManager->uploadImage($vals->header_image, $this->getUser()->id);
			$content->update(["header_image" => $fileId]);
		}
		if (!empty($vals->file) && $vals->file->hasFile()) {
			$fileId = $this->FilesManager->uploadFile($vals->file, $this->getUser()->id);
			$content->update(["file" => $fileId]);
		}
		if (!empty($vals->attachments)) {
			foreach ($vals->attachments as $att) {
				$file = $this->FilesManager->uploadFile($att, true);
				$this->ContentsManager->savecontentAttachment($id, $file);
			}
		}

		$tempFiles = $this->FilesManager->getTempFilesById($this->tempId);
		foreach ($tempFiles as $tempFile) {
			$this->ContentsManager->contentImageSave($id, $tempFile->file);
			$this->FilesManager->getTempFile($tempFile)->delete();
		}

		$alias = $this->AliasesManager->saveAlias("contents", $content);
		\Tracy\Debugger::barDump($alias, "alias");
		$content->update(["alias" => $alias]);

		$this->lastId = $id;

		return $id;
	}

	public function iamContentEditor($id, \Nette\Security\User $user = null) {
		$user = $user ? $user : $this->getUser();

		if (!$user->isLoggedIn()) return false;

		$content = $this->getContent($id);
		$type = $content->ref("type")->short;
		$role = "admin";

		// \Tracy\Debugger::barDump(count($this->getContentsEditors()->where("content", $id)->where("user", $user->id)), "count");

		if (!$user->isInRole("superadmin") && ((
			$content->user != $user->id &&
			!count($this->getContentsEditors()->where("content", $id)->where("user", $user->id))))) {
			return false;
		} else {
			return true;
		}
	}

	public function handleChangeAttTitle($attId, $title) {
		\Tracy\Debugger::barDump($attId, "attId");
		\Tracy\Debugger::barDump($title, "title");
		$this->ContentsManager->getContentAttachment($attId)->update(["title" => $title]);

		$this->redrawControl("formWrap");
		$this->redrawControl("attachments");
	}

	public function handleDeleteContent($id) {
		try {
			$this->ContentsManager->deleteContent($id);

			$this["contentsList"]->reload();
		} catch (\Nette\Database\ForeignKeyConstraintViolationException $e) {
			$this->flashMessage("Obsah nelze smazat kvůli vazbám. Zkus ho alespoň skrýt.", "alert-warning");
			$this->redrawControl("flashes");
		}
	}

	public function handleDeleteFile($fileId) {
		$this->FilesManager->deleteFile($fileId);

		$this->redrawControl("formWrap");
		$this->redrawControl("form");
		$this->redrawControl("files");
		$this->redrawControl("headerImageCropper");
	}

	public function handleContentImageForm($imageId) {
		\Tracy\Debugger::barDump($imageId, "imageId");
		$template = $this->template;
		$this["contentImageForm"]->setDefaults($this->ContentsManager->getContentImage($imageId));

		$this->modal([
			"title" => "Fotka",
			"content" => $template->getLatte()->renderToString(__DIR__ . "/../templates/Contents/contentImageForm.latte")
		]);
	}

	public function handleAttOrderChange($itemId, $prevItemId, $nextItemId) {
		\Tracy\Debugger::barDump($itemId, "id");
		\Tracy\Debugger::barDump($prevItemId, "prevItemId");
		\Tracy\Debugger::barDump($nextItemId, "nextItemId");

		$items = $this->ContentsManager->getContentAttachments($this->getParameter("id"));
		$this->ContentsManager->itemOrderChange($itemId, $prevItemId, $nextItemId, $items);
	}

	public function getContent($id) {
		return $this->ContentsManager->getContent($id);
	}


	public function createComponentContentsList(): DataGrid
	{
		$list = new DataGrid;

		$type = $this->type->short;

		// $list->addColumnText("type", "Typ", "type.title")
		// 	->setSortable()
		// 	->setFilterSelect(["- Všechny -"] + $this->ContentsManager->getContentTypes()->fetchPairs("short", "title"), "type.short");
		$list->addColumnLink("title", "Název", "contentForm")->setFilterText("contents.title");
		if ($type == "event") {
			// $list->addColumnDateTime("start", "Začátek")->setFormat("j.n.Y H:i")->setSortable();
			// $list->addColumnDateTime("end", "Konec")->setFormat("j.n.Y H:i")->setSortable();
			$list->addColumnText("interval", "Konání [počet termínů]")->setRenderer(function($i) {
				$dates = count($this->EventsManager->getEventDates($i));

				if (!$dates) return;

				$el = Html::el("div");
				$range = $this->EventsManager->getEventDatesInterval($i->id);
				bdump($range, "dates range");
				$int = \Monty\Filters::dateTimeInterval($range[0], $range[1], true, true, true);

				$el->addHtml($int);
				$el->addHtml(" <span style='font-weight: 700; color: #464646'>[$dates]</span>");

				return $el;
			})->setSortable()->setSortableCallback(function($data, $sort) {
				\Tracy\Debugger::barDump($data, "data");
				\Tracy\Debugger::barDump($sort, "sort");
				$sort = reset($sort);
				bdump($sort, "reset sort");

				return $data->order(":contents_events_dates.start $sort");
		});
			$list->addColumnText("persons", "Účastníků")->setRenderer(function($i) {
				if ($i->registration == "event") {
					$persons = count($this->EventsManager->getEventPersons($i->id));
					$el = Html::el("a");
					$el->href = $this->link(":Core:Admin:EventsPersons:eventPersonsList", $i->id);
					$el->class[] = "badge";
					$el->class[] = $persons != $i->reg_part ? "badge-success" : "badge-danger";
					$el->addHtml($persons . " / " . $i->reg_part);
					return $el;
				}
			})->setAlign("center");
		}
		$list->addColumnDateTime("created", "Vytvořeno")->setFormat("j.n.Y H:i")->setSortable();
		// $list->addAction("edit", "", "contentForm")->setClass("fas fa-pencil btn btn-warning");
		$list->addAction("delete", "", "deleteContent!")->setClass("fas fa-trash btn btn-danger")
			->setConfirmation(new StringConfirmation("Opravdu chceš smazat %s?", "title"));

		// $list->addGroupAction("Smazat")->onSelect[] = function($ids) use ($list) {
		// 	\Tracy\Debugger::barDump($ids, "ids");

		// 	$this->ContentsManager->getContents()->where("id", $ids)->delete();
		// 	$list->reload();
		// 	// $this->ContentsManager->getEventsDates()->where("id", $ids)->delete();
			
		// 	// $this->redrawControl();
		// };
		// $list->addGroupAction("Delete examples")->onSelect[] = function($ids) {
		// 	$this->ContentsManager->getContents()->where("id", $ids)->delete();
		// 	$list->reload();
		// };
		
		$list->addGroupButtonAction("Smazat")->onClick[] = function($ids) use ($list) {
			$this->ContentsManager->getContents()->where("id", $ids)->delete();
			$list->reload();
		};
		$groupCollection = $list->getGroupActionCollection();
		bdump($groupCollection, "groupCollection");
		$delBtn = $groupCollection->getGroupAction("Smazat");
		bdump($delBtn, "delBtn");
		$delBtn->setAttribute("data-datagrid-confirm", "Opravdu smazat?");
		$delBtn->setClass("btn btn-sm btn-success ajax");
		// foreach ($groupButton->get as $gBtn) {
		// 	bdump($gBtn, "gBtn");
		// 	$gBtn->addAttributes([
		// 		"data-confirm" => "Jo?"
		// 	]);
		// }

		$list->setDefaultSort($type == "event" ? ["interval" => "ASC"] : ["created" => "DESC"]);

		$list->setStrictSessionFilterValues(false);
		$list->setRememberState(true);

		return $list;
	}

	public function createComponentGalleryImagesDropzone() {
		$dropzone = new Dropzone;

		$dropzone->setAcceptedFiles(["jpg", "png", "jpeg"]);

		$inputs = $dropzone["inputs"];
		// $inputs->addText("author", "Autor");
		// $inputs->addText("watermark", "Vodotisk");
		// $inputs->addMultiSelect("tags", "Tagy", $tags);

		$dropzone->onUpload[] = function($file, $vals) {
			$fileId = $this->FilesManager->uploadImage($file, $this->getUser()->id);
			if ($this->getParameter("id")) {
				$this->ContentsManager->contentImageSave($this->getParameter("id"), $fileId);
			} else {
				$this->FilesManager->saveTempFile($fileId, $this->tempId);
			}
			// if (!empty($vals->tags)) $this->ContentsMa->galleryImageTagsSave($imageId, $vals->tags);
		};

		$dropzone->onUploadComplete[] = function() {
			$this->redrawControl("images");
		};

		return $dropzone;
	}

	public function createComponentImagesGallery() {
		$gal = new Gallery;

		$gal->setLayout("grid");
		$gal->setEditable();
		$gal->addImageAction("edit", null, "contentImageForm!")->setIcon("fas fa-pencil");

		$gal->onSelectionDelete[] = function($files) {
			\Tracy\Debugger::barDump($files, "files");
			foreach ($files as $file) {
				if ($this->getParameter("id")) {
					$this->ContentsManager->getContentImage($file)->delete();
				} else {
					$this->FilesManager->getTempFile($file)->delete();
				}
			}

			$this->redrawControl("images");
		};

		$gal->onOrderChange[] = function($item, $itemPrev, $itemNext) {
			\Tracy\Debugger::barDump("change order");
			\Tracy\Debugger::barDump($item, "item");

			$image = $this->ContentsManager->getContentImage($item);
			$images = $this->ContentsManager->getContentImages($image->content);
			\Tracy\Debugger::barDump($images->fetchAll(), "images");

			$this->ContentsManager->changeItemOrder($images, $item, $itemNext, $itemPrev);
			$this->ContentsManager->itemsReorder((clone $images));

			// $item = $this->ContentsManager->getContentImage($item);
			// $itemPrev = $this->ContentsManager->getContentImage($itemPrev);
			// $itemNext = $this->ContentsManager->getContentImage($itemNext);

			// $items = $this->ContentsManager->getContentsImages($item->content);
			// $this->ContentsManager->itemOrderChange($item, $itemPrev, $itemNext, $items);

			$this->redrawControl("images");
		};

		return $gal;
	}

	public function createComponentContentImageForm() {
		$form = new Form;

		$form->addHidden("id");
		$form->addText("title");
		$form->addTextArea("desc");
		$form->addSubmit("submit");

		$form->onSuccess[] = function($form, $vals) {
			$this->ContentsManager->saveContentImage($vals);
			// $this->redrawControl("modal");
			// $this->payload->modal = "hide";
			$this->modal("hide");
		};

		return $form;
	}

	public function createComponentHeaderImageCropper() {
		$cropper = new \Monty\Cropper;

		$cropper->setMaxSizes(1920, null);
		// $cropper->setRatio(1, 0.156);

		$cropper->onCropp[] = function($vals) {
			\Tracy\Debugger::barDump($vals, "headerImage cropper vals");
			$fileId = $this->FilesManager->uploadImage($vals->path, $this->getUser()->id);
			// $this->UsersManager->getUser($user->id)->update(["image" => $vals->image]);
			$this->ContentsManager->getContent($this->id)->update(["header_image" => $fileId]);
			$this->redrawControl("headerImageCropper");
		};

		return $cropper;
	}

	public function createComponentEventDateFormModal()
	{
		$modal = new Modal;
		$modal->setTitle("Přidat termín/y");
		$modal->setContent($this->template->renderToString(self::APP_ROOT . "CoreModule/AdminModule/templates/Contents/addEventDateModal.latte"));

		return $modal;
	}

	public function handleEventDateFormModal($date_id)
	{
		\Tracy\Debugger::barDump($date_id, "date_id");
		$modal = $this["eventDateFormModal"];
		\Tracy\Debugger::barDump($modal, "modal");

		$modal->show();
	}

	public function createComponentEventDateForm()
	{
		$f = $this->FormsFactory->eventDateForm();

		unset($f["save_stay"]);

		\Tracy\Debugger::barDump($this->getParameters(), "createComponentEventDateFormModal pars");

		$content = $this->ContentsManager->getContent($this->id);

		$pars = $this->getParameters();
		$date = isset($pars["date_id"]) ? $this->EventsManager->getEventDate($pars["date_id"]) : null;

		\Tracy\Debugger::barDump($date, "date");
		if ($content) {
			$f->setDefaults([
				"content" => $content->hash
			]);
		}
		if (isset($date)) {
			$f->setDefaults([
				"start" => $date->start->format(self::DATETIME_FORMAT),
				"end" => $date->end->format(self::DATETIME_FORMAT)
			]);
		}

		$f["save"]->onClick[] = function($f, $v) {
			\Tracy\Debugger::barDump($v, "EventDateForm vals");

			$this->EventsManager->saveEventDate($v);

			// $this->redrawControl("event-dates");
			$this["eventDatesList"]->reload();
			$this->redrawControl("modal");
		};

		return $f;
	}

	public function createComponentEventDatesList() {
		$l = new DataGrid;

		$l->setPagination(false);

		$l->addColumnText("date", "Termín")->setRenderer(function($i) {
			$text = \Monty\Filters::dateTimeInterval($i->start, $i->end, true, true, true);
			$a = Html::el("a");
			$a->href($this->link("eventDateFormModal!", $i->id));
			$a->class[] = "ajax";
			$a->addHtml($text);

			return $a;
		});
		// $l->addAction("participants", null, null)
		// 	->setRenderer(function($item) {
		// 		$hasForm = $this->EventsManager->hasEventForm($item->id);
		// 		$btn = Html::el("a");
		// 		$btn->class[] = $hasForm ? "text-primary" : "invisible";
		// 		$btn->addHtml(Html::el("i class='fas fa-users'"));

		// 		if ($hasForm) $btn->href($this->link("eventParticipantsList", $item->id));

		// 		return $btn;
		// 	});
		$l->addAction("published", "published")
			->setRenderer(function($item) {
				$button = Html::el("a");
				$button->class[] = "ajax";
				$button->addAttributes([
					"data-toggle" => "tooltip",
					"data-confirm" => "Opravdu?"
				]);
				$icon = Html::el("i class='fas fa-eye'");
				$button->addHtml($icon);

				$button->href($this->link("eventDateActiveToggle!", $item->id));

				if ($item->active) {
					$button->setTitle("Skrýt");
					$button->class[] = "text-success";
				} else {
					$button->setTitle("Publikovat");
					$button->class[] = "text-secondary";
				}

				return $button;
			});
		$l->addAction("delete", "", "eventDateDelete!", [
			"date_id" => "id"
		])->setClass("fas fa-trash text-danger ajax")
			->setConfirmation(new StringConfirmation("Opravdu chceš smazat %s?", "start"));

		$l->addGroupAction("Zveřejnit")->onSelect[] = function($ids) use ($l) {
			// \Tracy\Debugger::barDump($ids, "ids");
			$this->EventsManager->getEventsDates()->where("id", $ids)->update(["active" => true]);
			$this->flashMessage("Termíny zveřejněny");
			// $l->reload();
			// $this->redrawControl();
		};

		$l->addGroupAction("Skrýt")->onSelect[] = function($ids) use ($l) {
			// \Tracy\Debugger::barDump($ids, "ids");
			$this->EventsManager->getEventsDates()->where("id", $ids)->update(["active" => false]);
			$this->flashMessage("Termíny skryté", "alert-warning");
			// $this->redrawControl("content");
			// $this->redrawControl("flashes");
		};

		$l->addGroupAction("Smazat")->onSelect[] = function($ids) use ($l) {
			\Tracy\Debugger::barDump($ids, "ids");

			$this->EventsManager->getEventsDates()->where("id", $ids)->delete();
			
			// $this->redrawControl();
		};

		return $l;
	}

	public function handleEventDelete($event_id) {
		$this->ContentsManager->getContent($event_id)->delete();
		$this->flashMessage("Událost odstraněna", "alert-danger");
		$this->redrawControl("content");
	}

	public function handleEventDateDelete($date_id) {
		$this->EventsManager->getEventDate($date_id)->delete();
		$this->flashMessage("Událost odstraněna", "alert-danger");
		$this->redrawControl("content");
	}

	public function handleEventPublishedToggle($event_id) {
		$event = $this->EventsManager->getEvent($event_id);

		if ($event->active) {
			$state = false;
			$this->flashMessage("Akce byla skryta", "alert-warning");
		} else {
			$state = true;
			$this->flashMessage("Akce zveřejněna", "alert-success");
		}

		$event->update(["active" => $state]);
		// $this["eventsList"]->reload();
		$this->redrawControl("content");
		$this->redrawControl("flashes");
	}

	public function handleEventActiveToggle($event_id) {
		$event = $this->EventsManager->getEvent($event_id);

		if ($event->active) {
			$state = false;
			$this->flashMessage("Akce byla skryta", "alert-warning");
		} else {
			$state = true;
			$this->flashMessage("Akce zveřejněna", "alert-success");
		}

		$event->update(["active" => $state]);
		// $this["eventsList"]->reload();
		$this->redrawControl("content");
		$this->redrawControl("flashes");
	}

	public function handleEventDateActiveToggle($date_id) {
		$date = $this->EventsManager->getEventDate($date_id);

		if ($date->active) {
			$state = false;
			$this->flashMessage("Akce byla skryta", "alert-warning");
		} else {
			$state = true;
			$this->flashMessage("Akce zveřejněna", "alert-success");
		}

		$date->update(["active" => $state]);
		// $this["eventsList"]->reload();
		$this->redrawControl("content");
		$this->redrawControl("flashes");
	}

	public function getContentCustomData($id) {
		return $this->ContentsManager->getContentCustomData($id);
	}

}