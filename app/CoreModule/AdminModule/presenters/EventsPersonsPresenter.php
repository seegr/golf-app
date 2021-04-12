<?php

namespace App\CoreModule\AdminModule\Presenters;

use Monty\DataGrid;
use Monty\Form;
use Nette\Utils\ArrayHash;

class EventsPersonsPresenter extends AdminPresenter
{
  use \App\CoreModule\Traits\EventsTrait;

  /** @var \App\CoreModule\Model\EventsManager @inject */
  public $EventsManager;

  /** @var \App\CoreModule\FormsModule\Components\FormsFactory @inject */
  public $FormsFormsFactory;

  /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
  public $FormsManager;

  /** @persistent */
  public $id;

  /** @persistent */
  public $date;


  public function renderEventPersonsList($id, $date): void
  {
    $template = $this->template;
    $list = $this["personsList"];
    $event = $this->EventsManager->getEvent($id);
    $template->event = $event;

    if ($event->registration == "dates") {
      if (!$date) {
        $lastDate = $this->EventsManager->getEventDates($id)->order("start DESC")->fetch();
        $date = $lastDate->id;
      }
      
      $date = $this->EventsManager->getEventDate($date);
      bdump($date, "date");
      $template->date = $date;
      // $persons = $this->ContentsManager->getEventDatePersons($date->id);
      $persons = $this->FormsManager->getFormRecords($event->reg_form, true, [
        ":contents_events_persons.event" => $id,
        ":contents_events_persons.date" => $date
      ]);
    } else {
      // $persons = $this->ContentsManager->getEventPersons($id);
      $persons = $this->FormsManager->getFormRecords($event->reg_form, true, [
        ":contents_events_persons.event" => $id
      ]);
    }

    $template->regSummary = $this->EventsManager->getEventRegSummary($id);

    $list->setDataSource($persons);
  }

  public function actionPersonForm(int $eventId = null, int $id): void
  {
    $template = $this->template;
    $form = $this["personForm"];

    if ($eventId) {
      $event = $this->EventsManager->getEvent($eventId);
      $template->form = $event->ref("reg_form");
    } else {
      $person = $this->EventsManager->getEventPerson($id);
      $event = $person->ref("event");
      $personData = $template->person = ArrayHash::from($this->FormsManager->getRecord($id, true));
      $template->form = $person->ref("record")->ref("form");
      $form->setDefaults($personData);
    }

    $template->event = $event;
    $template->formComponent = "personForm";
  }
  

  public function createComponentPersonsList()
  {
    $eventId =  $this->getParameter("id");
    $event = $this->EventsManager->getEvent($eventId);

    $list = $this->FormsFormsFactory->formRecordsList($event->reg_form);

    $list->setRowCallback(function($i, $tr) {
      if (!$i["active"]) {
        $tr->addClass("unactive");
      }
    });

    $list->addAction("personForm", "", ":Core:Admin:EventsPersons:personForm", [
      "id" => "id"
    ])->setClass("fad fa-pen btn btn-warning");
    $list->addAction("personToggle", "", "personToggle!", [
      "personId" => "id"
    ])->setClass(function($i) {return $i["active"] ? "fad fa-check btn btn-success ajax" : "fad fa-check btn btn-grey ajax";});

    // $list->addExportCsv("Export účastníků (CSV)", "ucastnici.csv", "windows-1250")
    // ->setClass("btn btn-primary");

    return $list;
  }

  public function createComponentDateSelectForm()
  {
    $f = new Form;

    $id = $this->getParameter("id");
    $dateFormat = "%d.%m.%Y";
    $dates = $this->EventsManager->getEventDates($id)->select('*, DATE_FORMAT(start, ?) AS start_date', $dateFormat)->fetchPairs("id", "start_date");

    $f->setMethod("get");
    $f->addSelect("date", "Termín", $dates)
    ->setRequired();

    return $f;
  }

  public function createComponentPersonForm()
  {
    $eventId = $this->getParameter("eventId");
    $id = $this->getParameter("id");

    if ($eventId) {
      $event = $this->EventsManager->getEvent($eventId);
    } else {
      $person = $this->EventsManager->getEventPerson($id);
      $event = $person->ref("event");
    }

    $form = $event->ref("reg_form");

    $f = $this->FormsFormsFactory->customForm($form->id);

    $f->onSuccess[] = function($f, $v) use ($form, $event) {
      bdump($v);
      $recId = $this->FormsManager->saveRecord($form->id, $v);

      if (empty($v->id)) {
        $this->EventsManager->insertEventPerson($event->id, $recId, "part");
      }

      $this->redirect("eventPersonsList", ["id" => $event->id]);
    };

    return $f;
  }

}