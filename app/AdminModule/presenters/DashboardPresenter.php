<?php

namespace App\AdminModule\Presenters;

use Monty\Form;


class DashboardPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter
{

  use \App\CoreModule\Traits\EventsTrait;

  /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
  public $FormsManager;


  public function startup(): void
  {
      parent::startup();
  }

  public function renderDashboard(): void
  {
      $template = $this->template;

      // $template->times = self::getTimes(); 
  }

  public function renderEventsPersonsList()
  {
    $persons = $this->FormsManager->getFormsRecords()
    ->where(":contents_events_persons.event IS NOT NULL")
    ->select("forms_records.*, :contents_events_persons.event.title AS course");

    $search = $this->getParameter("text");
    if ($search) {
      $persons->where("data LIKE ?", "%$search%");
    }
    $persons = $this->FormsManager->fetchFormRecords($persons);
    bdump($persons, "persons");

    $list = $this["personsList"];
    $list->setDataSource($persons);
    $list->addColumnLink("course", "Kurz", ":Core:Admin:Contents:contentForm");
    $list->addColumnText("krestni_jmeno", "Jméno");
    $list->addColumnText("prijmeni", "Přijmení");
    $list->addColumnText("e_mail", "E-mail");
    $list->addColumnText("telefon", "Telefon");
  }

  public function createComponentPersonSearchForm(): Form
  {
    $f = $this->FormsFactory->newForm();

    $f->addText("text");
    $f->addSubmit("submit");
    $f->setMethod("get");

    return $f;
  }

}