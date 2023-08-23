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
    	bdump("startup");
    	parent::startup();
			$this->defineType();
    }

		public function contentForm($id, $type) {
			// bdump("contentForm...");
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
	
			// bdump($form->getGroup("custom_fields"), "custom fields group");
			if ($customFieldsGroup = $form["custom_fields"]) {
				$template->_customFieldsGroup = $customFieldsGroup;
				$template->_customFields = !empty($form["custom_fields"]) ? $form["custom_fields"]->getControls() : [];
			}
	
			foreach ($this->ContentsManager->getContentExcludeFields($type->short) as $exField) {
				unset($form[$exField]);
			}
			
		}
	
		public function renderContentForm($id, $type) {
			bdump("renderContentForm...");
			$user = $this->getUser();
	
			$template = $this->template;
			$form = $this->getForm();
			$gal = $this->getComponent("imagesGallery");
	
			$id = $id ? $id : $this->id;
	
			if ($id) {
				$content = $this->ContentsManager->getContent($id);
				$type = $content->ref("type");
				bdump($content, "content");
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
	
				// bdump($type->short, "type short");
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
	
			bdump("content images loop");
			foreach ($images as $image) {
				bdump($image, "image");
				$file = $image->ref("file");
				$gal->addImage($file->url, $this->getThumb($file->id))
					->setId($image->id);
			}
	
			// bdump($form, "form");
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
				bdump($vals, "save");
				$this->saveContent($vals);
				$type = $this->ContentsManager->getContentType($vals->type);
				$this->redirect(":Core:Admin:ContentsList:contentsList", ["type" => $type->short]);
				// $this->redrawControl();
			};
			$form["save_stay"]->onClick[] = function($btn, $vals) {
				bdump($vals, "save_stay");
				$id = $this->saveContent($vals);
				// bdump($id, "id");
				$this->redirect("this", ["id" => $id]);
				$this->id = $id;
			};
			$form->onError[] = function($form) {
				bdump($form->getErrors(), "errors");
			};
	
			return $form;
		}

	public function actionContentForm($id, $type): void
	{
		// bdump("actionContentForm...");
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
			bdump($this->id, "id");
			$this->redirect("this", $this->id);
			// $this->redrawControl();
		};
		$form["cancel"]->onClick[] = function() {
			$this->redirect(":Core:Admin:ContentsList:contentsList", $this->type->short);
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
			// bdump($ids, "ids");
			$this->EventsManager->getEventsDates()->where("id", $ids)->update(["active" => true]);
			$this->flashMessage("Termíny zveřejněny");
			// $l->reload();
			// $this->redrawControl();
		};

		$l->addGroupAction("Skrýt")->onSelect[] = function($ids) use ($l) {
			// bdump($ids, "ids");
			$this->EventsManager->getEventsDates()->where("id", $ids)->update(["active" => false]);
			$this->flashMessage("Termíny skryté", "alert-warning");
			// $this->redrawControl("content");
			// $this->redrawControl("flashes");
		};

		$l->addGroupAction("Smazat")->onSelect[] = function($ids) use ($l) {
			bdump($ids, "ids");

			$this->EventsManager->getEventsDates()->where("id", $ids)->delete();
			
			// $this->redrawControl();
		};

		return $l;
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
		bdump($date_id, "date_id");
		$modal = $this["eventDateFormModal"];
		bdump($modal, "modal");

		$modal->show();
	}

	public function createComponentEventDateForm()
	{
		$f = $this->FormsFactory->eventDateForm();

		unset($f["save_stay"]);

		bdump($this->getParameters(), "createComponentEventDateFormModal pars");

		$content = $this->ContentsManager->getContent($this->id);

		$pars = $this->getParameters();
		$date = isset($pars["date_id"]) ? $this->EventsManager->getEventDate($pars["date_id"]) : null;

		bdump($date, "date");
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
			bdump($v, "EventDateForm vals");

			$this->EventsManager->saveEventDate($v);

			// $this->redrawControl("event-dates");
			$this["eventDatesList"]->reload();
			$this->redrawControl("modal");
		};

		return $f;
	}

	public function handleChangeAttTitle($attId, $title) {
		bdump($attId, "attId");
		bdump($title, "title");
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
		bdump($imageId, "imageId");
		$template = $this->template;
		$this["contentImageForm"]->setDefaults($this->ContentsManager->getContentImage($imageId));

		$this->modal([
			"title" => "Fotka",
			"content" => $template->getLatte()->renderToString(__DIR__ . "/../templates/Contents/contentImageForm.latte")
		]);
	}

	public function handleAttOrderChange($itemId, $prevItemId, $nextItemId) {
		bdump($itemId, "id");
		bdump($prevItemId, "prevItemId");
		bdump($nextItemId, "nextItemId");

		$items = $this->ContentsManager->getContentAttachments($this->getParameter("id"));
		$this->ContentsManager->itemOrderChange($itemId, $prevItemId, $nextItemId, $items);
	}

	public function handleEventDateDelete($date_id) {
		$this->EventsManager->getEventDate($date_id)->delete();
		$this->flashMessage("Událost odstraněna", "alert-danger");
		$this->redrawControl("content");
	}

	public function handleEventDateActiveToggle($date_id) {
		$date = $this->EventsManager->getEventDate($date_id);

		if ($date->active) {
			$state = false;
			$this->flashMessage("Termín skrytý", "alert-warning");
		} else {
			$state = true;
			$this->flashMessage("Termín zveřejněný", "alert-success");
		}

		$date->update(["active" => $state]);
		// $this["eventsList"]->reload();
		$this->redrawControl("content");
		$this->redrawControl("flashes");
	}

	

  
}