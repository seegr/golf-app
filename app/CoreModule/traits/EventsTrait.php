<?php

namespace App\CoreModule\Traits;


trait EventsTrait
{

  public function getEventPersons($event, $date = null): array
  {
    $id = $event;
    $event = $this->EventsManager->getEvent($id);

    if ($event->registration == "dates") {
      $related = [
        ":contents_events_persons.event" => $id,
        ":contents_events_persons.date" => $date
      ];
    } else {
      $related = [
        ":contents_events_persons.event" => $id
      ];
    }

    $persons = $this->FormsManager->getFormRecords($event->reg_form, true, $related, "active DESC, time ASC");

    return $persons;
  }

}