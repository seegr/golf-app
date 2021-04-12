<?php

namespace App\CoreModule\Traits;


trait EventsTrait
{

  public function handlePersonToggle($personId)
  {
    $person = $this->FormsManager->getFormRecord($personId);
    bdump($person);
    $person->update(["active" => !$person->active]);

    $this["personsList"]->reload();
  }

}