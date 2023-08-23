<?php

namespace App\AdminModule\Presenters;

use Monty\DataGrid;
use League\Csv\Writer;
use Monty\Helper;
use Monty\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tracy\Debugger;


class DashboardPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter
{

    use \App\CoreModule\Traits\EventsTrait;

    /** @var \App\CoreModule\FormsModule\Model\FormsManager @inject */
    public $FormsManager;

    const COLOR_GREEN = '5e7d3c';
    const COLOR_GREEN_LIGHT = 'a3c979';
    const COLOR_DARK = '4f4f4f';

    private array $allCounter = [
        'persons' => 0,
        'money' => 0
    ];
    private int $counter = 0;


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

        $f->onSuccess[] = function ($f, $v) {
            if ($v->all) {
                $courses = $this->EventsManager->getEvents()->where('contents.active', true);
            } else if ($v->courses) {
                $courses = $this->EventsManager->getEvents()->where("contents.id", $v->courses);
            } else {
                return;
            }
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $i = 0;
            foreach ($courses as $e) {
                if ($i) {
                    $sheet = $spreadsheet->createSheet();
                } else {
                    $sheet = $spreadsheet->setActiveSheetIndex($i);
                }

                $date = $e->related('contents_events_dates')->order('start ASC')->fetch();
                if (!$date) continue;

                $i++;
//
                $this->addCourseText($sheet, $e, $date);
                $this->addPersonsTable($sheet, $e);
                //        bdump($rows, 'rows');
                //        $sheet->fromArray($rows);
            } //course end

            $this->setSummarySheet($spreadsheet, $i);
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

    private function addCourseText(Worksheet $sheet, $course, $date)
    {
        $start = $date->start;
        $end = $date->end;
        $timeFormat = 'H:i';
        $title = Helper::getDay($start->format("N"))['short'] . '_' . $start->format("H-i") . ' (' . $start->format("j.n.Y") . ')';

        $eCustomData = json_decode($course->custom_fields);
        if (!empty($eCustomData->lektor)) {
            $lektor = $eCustomData->lektor;
            $title .= ' (' . Strings::webalize($eCustomData->lektor) . ')';
        } else {
            $lektor = null;
        }

        $title = Helper::shorten($title, 31);

        $sheet->setTitle($title);

        $sheet->setCellValue('C1', 'BUDU GOLFISTA');
        $sheet->setCellValue('C3', $this->formatHtml('Vždy se ptejte, zda si budou půjčovat hole a pokud ano, rovnou si vezměte <strong>zálohu.</strong>'));
        $sheet->setCellValue('C5', $this->formatHtml('Na první lekci každému odbavit <strong>Karta ke kurzu zdarma</strong>.'));
        $sheet->setCellValue('C6', $this->formatHtml('Na každou <strong>lekci s trenérem</strong> jim odbavte půjčení hole a 2 koše na driving, <strong>nebo</strong> akademii - pokud tak rozhodne trenér, ale většinou to budou koše.'));
        $sheet->setCellValue('C7', 'Při tréninku mimo lekci se musí odbavovat jako běžný člen - tzn. čerpat z členství (pozor v typu platby z čeho čerpáte), nebo platit.');
        $sheet->setCellValue('C8', $this->formatHtml('Pokud lekci vynechají, <strong>na náhradu nemají nárok</strong>.'));
        $sheet->setCellValue('C10', 'Termín: ' . $start->format("j.n.Y"));
        $sheet->setCellValue('C11', 'Čas: ' . $start->format($timeFormat) . ' - ' . $end->format($timeFormat));
        $sheet->setCellValue('C12', 'Trenér: ' . $lektor);

        $sheet->getStyle('C1')
            ->getFont()
            ->getColor()
            ->setARGB(self::COLOR_GREEN);
        $sheet->getStyle('C1')
            ->getFont()
            ->setSize(14);
        $this->setCellBold($sheet, 'C1');
        $this->setCellBold($sheet, 'C10:H13');
        $this->setCellBold($sheet, 'B14:B17');

        $this->setCellBackground($sheet, 'B10:H12');
        $this->setCellBackground($sheet, 'B13');
        $this->setCellBorder($sheet, 'B10:H12');
        $this->setAllBorders($sheet, 'B13:H17');
        $this->setCellBorder($sheet, 'B10:H17', self::COLOR_DARK, Border::BORDER_MEDIUM);
        $this->setCellStringFormat($sheet, 'A1:J30');
        $this->setCellWidth($sheet, 'A', 3);
        $this->setCellWidth($sheet, 'B', 5.5);
        $this->setCellWidth($sheet, 'C', 20);
        $this->setCellWidth($sheet, 'D', 20);
        $this->setCellWidth($sheet, 'E', 20);
        $this->setCellWidth($sheet, 'F', 20);
        $this->setCellWidth($sheet, 'G', 15);
        $this->setCellWidth($sheet, 'H', 15);
    }

    private function setCellBold(Worksheet $sheet, string $cells)
    {
        $sheet->getStyle($cells)
            ->getFont()
            ->setBold(true);
    }

    private function setCellWidth(Worksheet $sheet, string $cells, $width)
    {
        $sheet->getColumnDimension($cells)->setWidth($width);
    }

    private function setCellBackground(Worksheet $sheet, string $cells, string $color = self::COLOR_GREEN_LIGHT)
    {
        $sheet
            ->getStyle($cells)
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($color);
    }

    private function setCellBorder(Worksheet $sheet, string $cells, string $color = self::COLOR_DARK, string $width = Border::BORDER_THIN)
    {
        $sheet
            ->getStyle($cells)
            ->getBorders()
            ->getOutline()
            ->setBorderStyle($width)
            ->setColor(new Color($color));
    }

    private function setAllBorders(Worksheet $sheet, string $cells, string $color = self::COLOR_DARK, string $width = Border::BORDER_THIN)
    {
        $sheet->getStyle($cells)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle($width)
            ->setColor(new Color($color));
    }

    private function setCellStringFormat(Worksheet $sheet, string $cell)
    {
        $sheet->getStyle($cell)
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    }

    private function formatHtml(string $string): RichText
    {
        $html = new \PhpOffice\PhpSpreadsheet\Helper\Html();

        return $html->toRichTextObject($string);
    }

    private function addPersonsTable(Worksheet $sheet, $course)
    {
        $allCounter = $this->allCounter;

        $fields = $this->FormsManager->getFormFields($course->reg_form)->fetchAssoc('name->');
        $order = ['lastname', 'firstname', 'e_mail', 'telefon', 'cislo_clenstvi', 'email_odeslan'];

        $rows = [];

        $header = [];
        foreach ($order as $fieldName) {
            $header[$fieldName] = $fields[$fieldName]->label;
        }
        $header = array_intersect_key($header, array_flip($order));
        $rows[] = [''] + $header;

        $persons = $this->getEventPersons($course->id, null, true);

        $this->counter = 0;
        $i = 1;
        foreach ($persons as $per) {
            $pRow = [$i];
            $pData = array_merge(array_flip($order), $per);
            $pData = array_intersect_key($pData, array_flip($order));

            $pRow = $pRow + $pData;
            $rows[] = $pRow;
            $paid = (int)$per['zaplaceno'];
            $this->counter = $this->counter + $paid;
            $allCounter['persons'] = $allCounter['persons'] + 1;
            $allCounter['money'] = $allCounter['money'] + $paid;
            $i++;
        }

        $this->allCounter = $allCounter;

        $sheet->fromArray($rows, null, 'B13');
    }

    private function setSummarySheet(Spreadsheet $spreadsheet, $index)
    {
        $allCounter = $this->allCounter;
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('Přehled');
        $sheet->fromArray([
            ['Celkem členů', $allCounter['persons']],
            ['Celkem zaplaceno', $allCounter['money']]
        ]);

        $spreadsheet->setActiveSheetIndex(0);
    }

    public function createComponentImportEventsForm()
    {
        $f = $this->FormsFactory->newForm();

        $mime = ["xls", "xlsx"];
        $f->addUpload("file", "Vyber soubor (Excel)")
            // ->addRule(Form::MIME_TYPE, "Nepodporovaný formát souboru." , $mime)
            ->getControlPrototype()->addAttributes(["accept" => Helper::getMimeString($mime)])
            ->setRequired();

        $f->onSuccess[] = function ($f, $v) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            bdump($v->file, "file");
            $file = $v->file;
            $excel = $reader->load($file->getTemporaryFile());
            $sheet = $excel->getSheet(0)->toArray();
            bdump($sheet, "sheet");

            foreach ($sheet as $row) {
            	if (empty($row[0])) continue;

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