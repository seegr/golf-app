<?php

namespace App\AdminModule\Presenters;

use App\CoreModule\AdminModule\Presenters\AdminPresenter;
use Monty\DataGrid;
use Monty\Form;

class EventsPersonsListPresenter extends AdminPresenter
{

    /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
    public $FormsManager;

    /** @persistent */
    public $search;

    /** @persistent */
    public $lektor;

    /** @persistent */
    public $archived;

    /** @persistent */
    public $start;

    /** @persistent */
    public $end;

    public function createComponentAllPersonsList()
    {
        $persons = $this->FormsManager->getFormsRecords()
            ->alias(":contents_events_persons.event", "e")
            ->where(":contents_events_persons.event IS NOT NULL")
            ->where(":contents_events_persons.event.archived IS NULL OR :contents_events_persons.event.archived != ?", 1)
            ->where(':contents_events_persons.state.short != ?', 'archived')
            ->select("forms_records.*, :contents_events_persons.event.title AS course, :contents_events_persons.event AS event, :contents_events_persons.event.custom_fields AS custom_fields");
        // ->select("(SELECT MIN(start) FROM contents_events_dates WHERE event = e.id) AS begin");

        $search = $this->getParameter("text");
        $lektor = $this->getParameter('lektor');

        if ($search) {
            $persons->whereOr([
                "data LIKE ?" => "%$search%",
                ":contents_events_persons.event.title LIKE ?" => "%$search%"
            ]);
        }
        if ($lektor) {
            $persons->whereOr([
                "LOWER(:contents_events_persons.event.custom_fields) LIKE ?" => "%".strtolower($lektor)."%"
            ]);
        }
        $persons = $this->FormsManager->fetchFormRecords($persons);
        // bdump($persons, "persons");

        $list = new DataGrid;

        $list->setDataSource($persons);
        $list->setDefaultSort(['time' => 'DESC']);
        $list->setRowCallback(function($i, $tr) {
            if (!$i["active"]) {
                $tr->addClass("unactive");
            }
        });
        $list->setStrictSessionFilterValues(false);
        $list->setRememberState(false);

        $list->setDataSource($persons);
        $list->addColumnLink("course", "Kurz", ":Core:Admin:Contents:contentForm", null, ['id' => 'event'])->setSortable();
        $list->addColumnDateTime("start", "Začátek")->setRenderer(function($i) {
        	bdump($i);
            $date = $this->EventsManager->getEventDates($i['event'])->order("start ASC")->fetch();
            if (!$date) return;

            return $date->start->format(self::DATETIME_FORMAT);
        })
            ->setFitContent();
        // ->setSortable();
        // ->setSortableCallback(function($data, $sort) {
        //   \Tracy\Debugger::barDump($data, "data");
        //   \Tracy\Debugger::barDump($sort, "sort");
        //   $sort = reset($sort);
        //   bdump($sort, "reset sort");

        //   $data = Helper::sortAssocArray($data, "start", "asc");
        //   return $data;
        // });
        $list->addColumnText("lector", "Lektor")->setRenderer(function($i) {
            $fields = json_decode($i['custom_fields']);
            bdump($fields);
            return !empty($fields->lektor) ? $fields->lektor : null;
        });
        $list->addColumnText("firstname", "Jméno")->setSortable();
        $list->addColumnText("lastname", "Přijmení")->setSortable();
        $list->addColumnText("e_mail", "E-mail")->setSortable();
        $list->addColumnText("telefon", "Telefon")->setSortable();
        $list->addColumnText("email_odeslan", "E-mail odeslán")->setSortable();
        $list->addColumnText("cislo_clenstvi", "Číslo členství")->setSortable();
        $list->addColumnDateTime("time", "Registrace")->setFormat(self::DATETIME_FORMAT)->setSortable();
        // $list->addColumnText("zaplaceno", "Zaplaceno (Kč)")->setSortable();

        $list->addAction("personForm", "", ":Core:Admin:EventsPersons:personForm", [
            "id" => "id"
        ])->setClass("fad fa-pen btn btn-warning");
        // $list->addAction("personToggle", "", "personToggle!", [
        //   "personId" => "id"
        // ])->setClass(function($i) {return $i["active"] ? "fad fa-check btn btn-success ajax" : "fad fa-check btn btn-grey ajax";});

        $list->addExportCsv("Export účastníků (CSV)", "ucastnici.csv", "windows-1250")
            ->setClass("btn btn-primary");

        return $list;
    }

    public function createComponentPersonSearchForm(): Form
    {
        $f = $this->FormsFactory->newForm();

        $f->setMethod("get");

        $f->addText("text");
        $f->addText("lektor");
        $f->addText("start");
        $f->addText("end");
        $f->addSubmit("submit");

        return $f;
    }

}