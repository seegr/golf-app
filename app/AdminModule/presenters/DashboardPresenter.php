<?php

namespace App\AdminModule\Presenters;

use Monty\DataGrid;
use League\Csv\Writer;
use Monty\Helper;
use Monty\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;


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
    $list->addColumnLink("course", "Kurz", ":Core:Admin:Contents:contentForm")->setSortable();
    $list->addColumnText("firstname", "Jméno")->setSortable();
    $list->addColumnText("lastname", "Přijmení")->setSortable();
    $list->addColumnText("e_mail", "E-mail")->setSortable();
    $list->addColumnText("telefon", "Telefon")->setSortable();
    $list->addColumnText("email_odeslan", "E-mail odeslán")->setSortable();
    $list->addColumnText("cislo_clenstvi", "Číslo členství")->setSortable();

    $list->addAction("personForm", "", ":Core:Admin:EventsPersons:personForm", [
      "id" => "id"
    ])->setClass("fad fa-pen btn btn-warning");
    // $list->addAction("personToggle", "", "personToggle!", [
    //   "personId" => "id"
    // ])->setClass(function($i) {return $i["active"] ? "fad fa-check btn btn-success ajax" : "fad fa-check btn btn-grey ajax";});

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

  public function createComponentExportEventsForm()
  {
    $f = new Form;

    $f->addMultiSelect('courses', 'Kurzy', $this->EventsManager->getEvents()->fetchPairs('id', 'title'))->setRequired();
    $f->addSubmit('export');

    $f->onSuccess[] = function($f, $v) {
      $contents = $this->ContentsManager->getContents()->where("id", $v->courses);
      $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
      $i = 0;
      foreach ($contents as $cont) {
        if ($i) {
          $spreadsheet->createSheet();
          $spreadsheet->setActiveSheetIndex($i);
        }
  
        $title = Strings::webalize($cont->title);
        bdump($title, 'title');
        $spreadsheet->getActiveSheet()->setTitle($title);
  
        $i++;
      }

      bdump($spreadsheet, 'spreadsheet');
  
      $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
      bdump($writer, 'writer');
      // $writer->setOutputEncoding('utf-8');
      $this->getHttpResponse()->setHeader("Content-Type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
      $this->getHttpResponse()->setHeader("Content-Disposition", "attachment; filename=kurzy.xlsx");
      $writer->save('php://output');
      // $writer->save("contents.csv");
      exit();
    };

    return $f;
  }

	public function createComponentImportEventsForm() {
		$f = $this->FormsFactory->newForm();

    $mime = ["xls", "xlsx"];
		$f->addUpload("file", "Vyber soubor (Excel)")
      // ->addRule(Form::MIME_TYPE, "Nepodporovaný formát souboru." , $mime)
      ->getControlPrototype()->addAttributes(["accept" => Helper::getMimeString($mime)])
      ->setRequired();

		$f->onSuccess[] = function($f, $v) {
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
			\Tracy\Debugger::barDump($v->file, "file");
			$file = $v->file;
			$excel = $reader->load($file->getTemporaryFile());
			$sheet = $excel->getSheet(0)->toArray();
			\Tracy\Debugger::barDump($sheet, "sheet");

			foreach ($sheet as $row) {
				$data = ArrayHash::from([
					"title" => $row[0],
					"start" => $row[1],
					"end" => $row[2]
				]);

        bdump($data);
        // continue;

        $id = $this->ContentsManager->saveContent([
          "title" => $data->title,
          "user" => $this->getUser()->id,
          "type" => "event",
          "registration" => "event",
          "reg_form" => 1,
          "reg_part" => 4
        ]);

        $this->EventsManager->saveEventDate([
          "content" => $id,
          "start" => $data->start,
          "end" => $data->end
        ]);
			}

			// $this->flashMessage("Importováno!");
			$this->redirect(":Core:Admin:Contents:contentsList", ["type" => "event"]);
		};

		return $f;
	}

}