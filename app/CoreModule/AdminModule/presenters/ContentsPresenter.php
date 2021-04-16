<?php

namespace App\CoreModule\AdminModule\Presenters;

use App;
use App\CoreModule\AdminModule\Traits\ContentsTrait;
use Monty\DataGrid;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Monty\Html;
use Monty\Modal;
use Nette\Utils\Strings;


class ContentsPresenter extends AdminPresenter {

	use ContentsTrait;


    /** @var \App\CoreModule\Model\ContentsManager @inject */
    public $ContentsManager;

    /** @var \App\CoreModule\Model\EventsManager @inject */
    public $EventsManager;

    /** @var \App\CoreModule\Model\CategoriesManager @inject */
    public $CategoriesManager;

    /** @var \App\CoreModule\Model\TagsManager @inject */
    public $TagsManager;

    /** @var \App\CoreModule\Model\FilesManager @inject */
    public $FilesManager;

    /** @var \App\CoreModule\Model\PersonsManager @inject */
    public $PersonsManager;

    protected $type;
    protected $id;

    //** @persistent */
    public $archive;


    public function startup(): void
    {
    	\Tracy\Debugger::barDump("startup");
    	parent::startup();
    	$template = $this->template;

    	$type = $this->getParameter("type");
    	$id = $this->getParameter("id");

    	if ($type) {
    		$type = $this->ContentsManager->getContentType($type);
    	} elseif ($id) {
    		$type = $this->ContentsManager->getContent($id)->ref("type");
    	} else {
    		$type = null;
    	}

    	\Tracy\Debugger::barDump($id, "id");
    	\Tracy\Debugger::barDump($type, "type");
    	
    	$this->id = $id;
    	$this->type = $type;

    	$template->type = $type;
    	$template->types = $types = $this->ContentsManager->getContentTypes();
    	$template->typesArr = (clone $types)->fetchPairs("short", "title");
    }

    public function actionContentsList($type): void
    {
    	\Tracy\Debugger::barDump("actionContentsList");
    	$template = $this->template;
    	$list = $this["contentsList"];
    	$list->setRefreshUrl(false);

    	$type = $this->type->short;

    	if ($type == "event") {
	    	$events = $this->EventsManager->getFutureEvents(true)->where("archived IS NULL OR archived = 0");
	    	$list->setDataSource($events);
    	} else {
			$list->setDataSource($this->ContentsManager->getContents($type));
    	}
    }

    public function renderContentsList($type)
    {
    	\Tracy\Debugger::barDump("renderContentsList");
    	$template = $this->template;
    	$list = $this["contentsList"];
    }

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

	public function actionContentForm($id, $type): void
	{
		// \Tracy\Debugger::barDump("actionContentForm...");
		$this->contentForm($id, $type);

		$template = $this->template;

		$form = $this["contentForm"];

		$categories = $this->ContentsManager->getContentTypeCategories($this->type)->fetchPairs("id", "title");
		$tags = $this->ContentsManager->getContentTypeTags($this->type)->fetchPairs("id", "title");

		if (!$categories) {
			unset($form["category"]);
		}
		if (!$tags) {
			unset($form["tags"]);
		}

		$template->headerImageConf = $this->SettingsManager->getSetting("content_header_image");
		
		$form["save_stay"]->onClick[] = function($form, $vals) {
			\Tracy\Debugger::barDump($this->id, "id");
			$this->redirect("this", $this->id);
			// $this->redrawControl();
		};
		$form["cancel"]->onClick[] = function() {
			$this->redirect("contentsList", $this->type->short);
		};

    $dates = $this->EventsManager->getEventDates($id);
    $template->hasDates = count($dates);
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
				if (!$i->registration) return;

				if ($i->registration == "event" && $i->reg_part) {
					$summary = $this->EventsManager->getEventRegSummary($i->id);
					$wrap = Html::el("span");
					$el = Html::el("a");
					$el->href = $this->link(":Core:Admin:EventsPersons:eventPersonsList", $i->id);
					$el->class[] = "badge badge-$summary->spotsColor";
					$el->addAttributes([
						"data-toggle" => "popover",
						"data-trigger" => "hover",
						"data-content" => "Přihlášených: <strong>" . $summary->allCount . "</strong> z <strong>" . $summary->spots . "</strong>"
					]);
					$el->addHtml($summary->allCount . " / " . $summary->spots);
					$wrap->addHtml($el);

					$confirmed = count((clone $summary->all)->where("state.short", "confirmed"));
					$confEl = Html::el("span");
					$confEl->class[] = "ml-2 badge";
					if ($confirmed == $summary->spots) {
						$confEl->class[] = "badge-success";
						$confEl->addHtml('<i class="far fa-check"></i>');
					} else {
						$confEl->class[] = "badge-soft";
						$confEl->setText($confirmed);
					}
					$confEl->addAttributes([
						"data-toggle" => "popover",
						"data-trigger" => "hover",
						"data-content" => "Potvrzených: " . "<strong>$confirmed</strong>"
					]);
					$wrap->addHtml($confEl);

					return $wrap;
				}

				if ($i->registration == "dates") {
					$persons = count($this->EventsManager->getEventPersons($i->id));
					$el = Html::el("a");
					$el->href = $this->link(":Core:Admin:EventsPersons:eventPersonsList", $i->id);
					$el->class[] = "badge badge-primary";
					$el->setText($persons);
					$el->addAttributes([
						"data-toggle" => "popover",
						"data-trigger" => "hover",
						"data-content" => "Celkem přihlášených: <strong>" . $persons . "</strong>"
					]);
					return $el;
				}

			})->setAlign("center");
		}
		$list->addColumnDateTime("created", "Vytvořeno")->setFormat("j.n.Y H:i")->setSortable();
		// $list->addAction("edit", "", "contentForm")->setClass("fas fa-pencil btn btn-warning");
		$list->addAction("archive", "", "contentToggleArchive!", ["event_id" => "id"])->setClass("fas fa-trash btn btn-danger ajax")
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
		
		$list->addGroupButtonAction("Export CSV")->onClick[] = function($ids) use ($list) {
			// $this->ContentsManager->getContents()->where("id", $ids)->delete();
      $contSess = $this->getSession('contents');
      $contSess->export = $ids;
      $this->redirect('exportContents');
		};

		$list->addGroupButtonAction("Smazat")->onClick[] = function($ids) use ($list) {
			$this->ContentsManager->getContents()->where("id", $ids)->delete();
			$list->reload();
		};
		$groupCollection = $list->getGroupActionCollection();
		$delBtn = $groupCollection->getGroupAction("Smazat");
		$delBtn->setAttribute("data-datagrid-confirm", "Opravdu smazat?");
		$delBtn->setClass("btn btn-sm btn-danger ajax");

		$list->setDefaultSort($type == "event" ? ["interval" => "ASC"] : ["created" => "DESC"]);
		$list->setStrictSessionFilterValues(false);
		$list->setRememberState(true);

		return $list;
	}

  // public function actionExportContents()
  // {
  //   $contSess = $this->getSession('contents');
  //   $ids = $contSess->export;
  //   bdump($ids);

  //   // $this->sendResponse();

  //   // $this->redrawControl();
  //   exit();
  // }

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

	public function handleContentToggleArchive($event_id) {
		$content = $this->ContentsManager->getContent($event_id);
		$content->update(["archived" => !$content->archived]);
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

	

  
}