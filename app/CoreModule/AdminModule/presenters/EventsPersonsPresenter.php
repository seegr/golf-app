<?php

namespace App\CoreModule\AdminModule\Presenters;

use Monty\DataGrid;
use Monty\Form;
use Monty\Helper;
use Nette\Utils\ArrayHash;
use Monty\Html;


class EventsPersonsPresenter extends AdminPresenter
{
    use \App\CoreModule\Traits\EventsTrait;

    /** @var \App\CoreModule\Model\EventsManager @inject */
    public $EventsManager;

    /** @var \App\CoreModule\FormsModule\Components\FormsFactory @inject */
    public $FormsFormsFactory;

    /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
    public $FormsManager;

    /** @persistentx */
    public $id;

    protected $eventId;

    /** @persistent */
    public $date;


    public function renderEventPersonsList($id, $date): void
    {
        $template = $this->template;
        $list = $this["personsList"];
        $event = $this->EventsManager->getEvent($id);
        $template->event = $event;

        if ($event->registration == "dates") {
            if (!$date) {
                $lastDate = $this->EventsManager->getEventDates($id)->order("start DESC")->fetch();
                $date = $lastDate->id;
//                bdump($date);
            }

            $date = $this->EventsManager->getEventDate($date);
//            bdump($date, "date");
            $template->date = $date;
        }

        $persons = $this->getEventPersons($id, $date);

        $template->regSummary = $this->EventsManager->getEventRegSummary($id, $date);

        $list->setDataSource($persons);
    }

    public function actionPersonForm(int $eventId = null, int $id = null, $date = null): void
    {
        $template = $this->template;
        $form = $this["personForm"];

        if ($eventId) {
            $event = $this->EventsManager->getEvent($eventId);
            $template->form = $event->ref("reg_form");
        } else {
            $person = $this->EventsManager->getEventPerson($id);
            $event = $person->ref("event");
            $personData = $template->person = ArrayHash::from($this->FormsManager->getRecord($id, true));
            $template->form = $person->ref("record")->ref("form");
            $form->setDefaults($personData);
            if (!empty($personData->telefon)) {
                $form['telefon']->setDefaultValue(Helper::formatPhone($personData->telefon));
            }
        }

        $this->eventId = $event->id;

        $template->event = $event;
        $template->date = $date;
        $template->formComponent = "personForm";
    }


    public function createComponentPersonsList()
    {
        $eventId = $this->getParameter("id");
        $event = $this->EventsManager->getEvent($eventId);

        $list = $this->FormsFormsFactory->formRecordsList($event->reg_form);
        $list->moveColumnStart("lastname");

        $list->setRowCallback(function ($i, $tr) {
            if (!$i["active"]) {
                $tr->addClass("unactive");
            }
        });

        $list->addAction("personForm", "", ":Core:Admin:EventsPersons:personForm", [
            "id" => "id"
        ])->setClass("fad fa-pen btn btn-warning");
        $list->addAction("confirm", "")->setRenderer(function ($i) {
            $person = $this->EventsManager->getEventPerson($i["id"]);
            $confirmed = $person->ref("state")->short == "confirmed" ? true : false;
            $a = Html::el("a");
            $a->class[] = "ajax btn btn-sm fad fa-check";
            $a->class[] = $confirmed ? "btn-success" : "btn-grey";
            if ($person->ref("record")->active) {
                $a->href = $this->link("changePersonState!", [
                    "personId" => $person->record,
                    "state" => $confirmed ? "unconfirmed" : "confirmed"
                ]);
            } else {
                $a->class[] = "disabled";
            }
            return $a;
        });
        $list->addAction("archive", "")->setRenderer(function ($i) {
            $record = $this->FormsManager->getFormRecord($i["id"]);
            $a = Html::el("a");
            $a->class[] = "ajax btn btn-sm fad";
            $a->class[] = $record->active ? "btn-danger fa-trash" : "btn-primary fa-upload";
            $a->href = $this->link("personToggle!", [
                "personId" => $record->id
            ]);
            return $a;
        });
        // $list->addAction("personToggle", "", "personToggle!", [
        //   "personId" => "id"
        // ])->setClass(function($i) {return $i["active"] ? "fad fa-trash btn btn-danger ajax" : "fad fa-trash btn btn-grey ajax";});

        // $list->addExportCsv("Export účastníků (CSV)", "ucastnici.csv", "windows-1250")
        // ->setClass("btn btn-primary");

        $list->setRefreshUrl(false);

        return $list;
    }

    public function createComponentDateSelectForm()
    {
        $f = new Form;

        $id = $this->getParameter("id");
        $dateFormat = "%d.%m.%Y";
        $dates = $this->EventsManager->getEventDates($id)->select('*, DATE_FORMAT(start, ?) AS start_date', $dateFormat)->fetchPairs("id", "start_date");

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
            $event = $this->EventsManager->getEvent($eventId);
        } else {
            $person = $this->EventsManager->getEventPerson($id);
            $event = $person->ref("event");
        }

        $form = $event->ref("reg_form");

        $f = $this->FormsFormsFactory->customForm($form->id);

        $f['submit']->setCaption("Uložit");

        $f->onSuccess[] = function ($f, $v) use ($form, $event) {
            bdump($v);
            bdump($this->getParameters(), "pars");
            $recId = $this->FormsManager->saveRecord($form->id, $v);

            if (empty($v->id)) {
                $date = $this->getParameter("date");
                $this->EventsManager->insertEventPerson($event->id, $recId, "part", $date);
            }

            $this->redirect("eventPersonsList", ["id" => $event->id]);
        };

        return $f;
    }

    public function createComponentMovePersonForm()
    {
        $form = new Form;

        $id = $this->getParameter('id');

        $events = $this->EventsManager->getActiveContents();
        $eventsArr = [];
        foreach ($events as $ev) {
            $eventsArr[$ev->id] = $ev->title . ' (' . $this->EventsManager->getEventMinDate($ev->id)->format(self::DATE_FORMAT) . ')';
        }

        $form->addHidden('id', $id);
        $form->addSelect("event", "Akce")
            ->setRequired()
            ->setItems($eventsArr)
            ->setDefaultValue($this->eventId);
        $form->addSubmit('submit', 'Přesunout');

        $form->onSuccess[] = function($f, $v) {
            $person = $this->EventsManager->getEventPerson($v->id);
            $person->update(['event' => $v->event]);
            $this->redirect("this");
        };

        return $form;
    }

    public function handlePersonToggle($personId)
    {
        $record = $this->FormsManager->getFormRecord($personId);
        $record->update(["active" => !$record->active]);

        $this->handleChangePersonState($personId, $record->active ? "unconfirmed" : "archived");
        // $this["personsList"]->reload();
        $this->redrawControl();
    }

    public function handleChangePersonState($personId, $state)
    {
        $state = $this->EventsManager->getState($state);
        $this->EventsManager->getEventPerson($personId)->update(["state" => $state->id]);
        $this["personsList"]->reload();
    }

}