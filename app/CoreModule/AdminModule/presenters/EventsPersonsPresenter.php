<?php

namespace App\CoreModule\AdminModule\Presenters;

use Monty\DataGrid;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class EventsPersonsPresenter extends AdminPresenter
{

  /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
  public $FormsManager;

  /** @var \App\CoreModule\FormsModule\Components\FormsFactory @inject */
  public $FormsFormsFactory;

  /** @persistent */
  public $id;

  /** @persistent */
  public $date;


  public function actionPersonsList($id, $date): void
  {
    $template = $this->template;
    $list = $this["personsList"];
    $event = $this->ContentsManager->getEvent($id);
    $template->event = $event;

    if ($event->registration == "dates") {
      if (!$date) {
        $lastDate = $this->ContentsManager->getEventDates($id)->order("start DESC")->fetch();
        $date = $lastDate->id;
      }
      
      $date = $this->ContentsManager->getEventDate($date);
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

    $list->setDataSource($persons);
  }

  public function actionPersonForm(int $eventId = null, int $id): void
  {
    $template = $this->template;
    $form = $this["personForm"];

    if ($eventId) {
      $event = $this->ContentsManager->getEvent($eventId);
      $template->form = $event->ref("reg_form");
    } else {
      $person = $this->ContentsManager->getEventPerson($id);
      $personData = $template->person = ArrayHash::from($this->FormsManager->getRecord($id, true));
      $template->form = $person->ref("record")->ref("form");
      $form->setDefaults($personData);
    }

    $template->formComponent = "personForm";
  }

  public function createComponentPersonsList()
  {
    $list = new DataGrid;

    $eventId =  $this->getParameter("id");

    $list->addColumnText("krestni_jmeno", "Jméno");
    $list->addColumnText("prijmeni", "Příjmení");
    $list->addColumnText("e_mail", "E-mail");
    $list->addColumnText("telefon", "Tel");
    $list->addColumnDateTime("time", "Registrace")->setFormat(self::DATETIME_FORMAT);
    $list->addAction("personForm", "", "personForm", [
      "id" => "id"
    ])->setClass("fad fa-pencil text-warning");

    return $list;
  }

  public function createComponentDateSelectForm()
  {
    $f = new Form;

    $id = $this->getParameter("id");
    $dateFormat = "%d.%m.%Y";
    $dates = $this->ContentsManager->getEventDates($id)->select('*, DATE_FORMAT(start, ?) AS start_date', $dateFormat)->fetchPairs("id", "start_date");

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
      $event = $this->ContentsManager->getEvent($eventId);
    } else {
      $person = $this->ContentsManager->getEventPerson($id);
      $event = $person->ref("event");
    }

    $form = $event->ref("reg_form");

    $f = $this->FormsFormsFactory->customForm($form->id);

    $f->onSuccess[] = function($f, $v) use ($form, $event) {
      bdump($v);
      $recId = $this->FormsManager->saveRecord($form->id, $v);

      $this->ContentsManager->getEventsPersons()->insert([
        "event" => $event->id,
        "record" => $recId
      ]);

      $this->redirect("personsList");
    };

    return $f;
  }

}