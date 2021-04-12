<?php

namespace App\CoreModule\Traits;


trait EventsTrait
{

  public function createComponentPersonsList()
  {
    $eventId =  $this->getParameter("id");
    $event = $this->EventsManager->getEvent($eventId);

    $list = $this->FormsFormsFactory->formRecordsList($event->reg_form);

    $list->addAction("personForm", "", "personForm", [
      "id" => "id"
    ])->setClass("fad fa-pen btn btn-warning");

    return $list;
  }

}