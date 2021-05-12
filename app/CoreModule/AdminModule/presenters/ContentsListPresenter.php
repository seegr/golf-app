<?php

namespace App\CoreModule\AdminModule\Presenters;

use App;
use App\CoreModule\AdminModule\Traits\ContentsTrait;
use Monty\DataGrid;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Monty\Html;
use Monty\Modal;
use Nette\Utils\Strings;


class ContentsListPresenter extends AdminPresenter {

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

  /** @persistent */
  public $archived;

  /** @persistent */
  public $expired;


  public function startup(): void
  {
    \Tracy\Debugger::barDump("startup");
    parent::startup();
    $this->defineType();
  }

    public function actionContentsList($type): void
    {
    	\Tracy\Debugger::barDump("actionContentsList");
    	$template = $this->template;
    	$list = $this["contentsList"];
    	$list->setRefreshUrl(false);

    	$type = $this->type->short;

    	if ($type == "event") {
        if (!$this->expired) {
          $events = $this->EventsManager->getFutureEvents(true);
        } else {
          $events = $this->EventsManager->getEvents();
        }

        if (!$this->archived) {
          $events->where("archived IS NULL OR archived = 0");
        }
        // $events->alias("contents", "c");
        $events->select("contents.*");
        $events->select("(SELECT COUNT(*) FROM contents_events_persons WHERE event = contents.id) AS persons");
        // bdump($events->fetchAll());
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

    public function createComponentContentsList(): DataGrid
    {
      $list = new DataGrid;
  
      $type = $this->type->short;
  
      // $list->addColumnText("type", "Typ", "type.title")
      // 	->setSortable()
      // 	->setFilterSelect(["- Všechny -"] + $this->ContentsManager->getContentTypes()->fetchPairs("short", "title"), "type.short");
      $list->addColumnLink("title", "Název", ":Core:Admin:Contents:contentForm")->setFilterText("contents.title");
      if ($type == "event") {
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
        })->setAlign("center")->setSortable()->setSortableCallback(function($data, $sort) {
          \Tracy\Debugger::barDump($data, "data");
          \Tracy\Debugger::barDump($sort, "sort");
          $sort = reset($sort);
          // bdump($sort, "reset sort");
          
          return $data->order("persons $sort");
        });
        $list->addColumnText('id', "#ID")->setAlign('center');
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


    public function handleContentToggleArchive($event_id) {
      $content = $this->ContentsManager->getContent($event_id);
      $content->update(["archived" => !$content->archived]);
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

}