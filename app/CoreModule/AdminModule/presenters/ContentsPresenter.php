<?php

namespace App\CoreModule\AdminModule\Presenters;

use App;
use App\CoreModule\AdminModule\Traits\ContentsTrait;
use Monty\DataGrid;


class ContentsPresenter extends AdminPresenter {

	use ContentsTrait;


    /** @var \App\CoreModule\Model\ContentsManager @inject */
    public $ContentsManager;

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
	    	$events = $this->ContentsManager->getFutureEvents(true);
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

    $dates = $this->ContentsManager->getEventDates($id);
    $template->hasDates = count($dates);
	}
  
}