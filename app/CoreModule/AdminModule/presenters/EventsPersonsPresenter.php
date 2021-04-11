<?php

namespace App\CoreModule\AdminModule\Presenters;

use Monty\DataGrid;
use Nette\Application\UI\Form;

class EventsPersonsPresenter extends AdminPresenter
{

  /** @persistent */
  public $id;

  /** @persistent */
  public $date;


  public function actionEventPersonsList($id, $date): void
  {
    $template = $this->template;
    $list = $this["eventPersonsList"];

    if (!$date) {
      $lastDate = $this->ContentsManager->getEventDates($id)->order("start DESC")->fetch();
      $date = $lastDate->id;
    }
    
    $date = $this->ContentsManager->getEventDate($date);
    bdump($date, "date");
    $template->date = $date;

    $persons = $this->ContentsManager->getEventDatePersons($date->id);
    $list->setDataSource($persons);
  }

  public function createComponentEventPersonsList()
  {
    $list = new DataGrid;

    $list->addColumnText("firstname", "Jméno");
    $list->addColumnText("lastname", "Příjmení");
    $list->addColumnText("email", "E-mail");
    $list->addColumnText("tel", "Tel");

    return $list;
  }

  public function createComponentDateSelectForm()
  {
    $f = new Form;

    $id = $this->getParameter("id");
    
    $f->setMethod("get");
    $f->addSelect("date", "Termín", $this->ContentsManager->getEventDates($id)->fetchPairs("id", "start"))
    ->setRequired();

    return $f;
  }

}