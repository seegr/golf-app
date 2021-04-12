<?php

namespace App\AdminModule\Presenters;

use Monty\Form;
use Monty\DataGrid;
use League\Csv\Writer;


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

      $template->events = $this->EventsManager->getEvents();
  }

  public function renderEventsPersonsList()
  {
  }


  public function createComponentAllPersonsList()
  {
    $persons = $this->FormsManager->getFormsRecords()
      ->where(":contents_events_persons.event IS NOT NULL")
      ->select("forms_records.*, :contents_events_persons.event.title AS course");

    $search = $this->getParameter("text");
    if ($search) {
      $persons->whereOr([
        "data LIKE ?" => "%$search%",
        ":contents_events_persons.event.title LIKE ?" => "%$search%",
      ]);
    }
    $persons = $this->FormsManager->fetchFormRecords($persons);
    bdump($persons, "persons");

    $list = new DataGrid;

    $list->setDataSource($persons);
    $list->setRowCallback(function($i, $tr) {
      if (!$i["active"]) {
        $tr->addClass("unactive");
      }
    });
    
    $list->setDataSource($persons);
    $list->addColumnLink("course", "Kurz", ":Core:Admin:Contents:contentForm");
    $list->addColumnText("jmeno", "Jméno");
    $list->addColumnText("prijmeni", "Přijmení");
    $list->addColumnText("e_mail", "E-mail");
    $list->addColumnText("telefon", "Telefon");

    $list->addAction("personForm", "", ":Core:Admin:EventsPersons:personForm", [
      "id" => "id"
    ])->setClass("fad fa-pen btn btn-warning");
    $list->addAction("personToggle", "", "personToggle!", [
      "personId" => "id"
    ])->setClass(function($i) {return $i["active"] ? "fad fa-check btn btn-success ajax" : "fad fa-check btn btn-grey ajax";});

    bdump(mb_list_encodings(), "encoding");
    $list->addExportCsv("Export účastníků (CSV)", "ucastnici.csv", "ISO-8859-2")
      ->setClass("btn btn-primary");

    return $list;
  }

  public function createComponentPersonSearchForm(): Form
  {
    $f = $this->FormsFactory->newForm();

    $f->addText("text");
    $f->addSubmit("submit");
    $f->setMethod("get");

    return $f;
  }

  public function handleExportPersons()
  {
    $csv = Writer::createFromFileObject(new \SplTempFileObject());
    
    $records = $this->FormsManager->getFormsRecords();
    $records = $this->FormsManager->fetchFormRecords($records);

    bdump($records);

    $csv->insertAll($records);

    // $csv->output("persons.csv");
    // die;
  }

}