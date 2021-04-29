<?php

namespace App\CoreModule\Traits;


trait EventsTrait
{

  public function getEventPersons($event, $date = null, $onlyActive = false): array
  {
    $id = $event;
    $event = $this->EventsManager->getEvent($id);

    if ($onlyActive) {
      $conds[] = [':contents_events_persons.state.short != ?' => 'archived'];
    }

    if ($event->registration == "dates") {
      $conds[] = [
        ":contents_events_persons.event" => $id,
        ":contents_events_persons.date" => $date
      ];
    } else {
      $conds[] = [
        ":contents_events_persons.event" => $id
      ];
    }

    $persons = $this->FormsManager->getFormRecords($event->reg_form, true, $conds, "active DESC, time ASC");

    return $persons;
  }

}