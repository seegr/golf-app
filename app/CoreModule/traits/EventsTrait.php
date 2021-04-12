<?php

namespace App\CoreModule\Traits;


trait EventsTrait
{

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

    return $list;
  }

  public function handlePersonToggle($personId)
  {
    $person = $this->FormsManager->getFormRecord($personId);
    bdump($person);
    $person->update(["active" => !$person->active]);

    $this["personsList"]->reload();
  }

}