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


  public function createComponentExportEventsForm()
  {
    $f = new Form;

    $f->addMultiSelect('courses', 'Kurzy', $this->EventsManager->getActiveContents()->fetchPairs('id', 'title'));
    $f->addCheckBox('all', 'Všechny kurzy');
    $f->addSubmit('export');

    $f->onSuccess[] = function($f, $v) {
      if ($v->all) {
        $contents = $this->EventsManager->getEvents()->where('contents.active', true);
      } else if ($v->courses) {
        $contents = $this->EventsManager->getEvents()->where("contents.id", $v->courses);
      } else {
        return;
      }
      $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
      
      $i = 0;
      $allCounter = [
        'persons' => 0,
        'money' => 0
      ];
      foreach ($contents as $e) {
        $eCustomData = json_decode($e->custom_fields);
        if ($i) {
          $spreadsheet->createSheet();
          $spreadsheet->setActiveSheetIndex($i);
        }

          bdump('here');
        // $title = Strings::webalize($e->title);
        $firstDate = $e->related('contents_events_dates')->order('start ASC')->fetch();
        if (!$firstDate) continue;
        // $timestamp = $firstDate->start->getTimestamp();
        // $title = Strings::webalize(strftime('%a %H:%M', $timestamp));

        $firstDate = $firstDate->start;
        $title = Helper::getDay($firstDate->format("N"))['short'] . '_' . $firstDate->format("H-i") . ' (' . $firstDate->format("j.n.Y") . ')' ;
//        bdump($title, 'title');
        $sheet = $spreadsheet->getActiveSheet();
        $fields = $this->FormsManager->getFormFields($e->reg_form);
        $fieldsKeys = $fields->fetchPairs(null, 'name');
//        bdump($fieldsKeys, 'fields keys');
  
        $i++;
        
        $rows = [];

        $header = [];
        foreach ($fields as $field) {
          $header[] = $field->label;
        }
        $rows[] = $header;

        $persons = $this->getEventPersons($e->id, null, true);

        $counter = 0;
        foreach ($persons as $per) {
          $pData = array_intersect_key($per, array_flip($fieldsKeys));
          $rows[] = $pData;
          $paid = (int)$per['zaplaceno'];
          $counter = $counter + $paid;
          $allCounter['persons'] = $allCounter['persons'] + 1;
          $allCounter['money'] = $allCounter['money'] + $paid;
        }

        $rows[] = [];
        if (!empty($eCustomData->lektor)) {
          $rows[] = ['Lektor', $eCustomData->lektor];
          $title .= ' (' . Strings::webalize($eCustomData->lektor) . ')';
        }
        $rows[] = ['Zaplaceno', $counter];

        $title = Helper::shorten($title, 31);
        $sheet->setTitle($title);
        bdump('baf');
        bdump($rows, 'rows');
        $sheet->fromArray($rows);
      }

      $spreadsheet->createSheet();
      $spreadsheet->setActiveSheetIndex($i);
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setTitle('Přehled');
      $sheet->fromArray([
        ['Celkem členů', $allCounter['persons']],
        ['Celkem zaplaceno', $allCounter['money']]
      ]);

      bdump($spreadsheet, 'spreadsheet');
      // exit();
  
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
			$this->redirect(":Core:Admin:ContentsList:contentsList", ["type" => "event"]);
		};

		return $f;
	}

}