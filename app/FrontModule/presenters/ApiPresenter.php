<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\CoreModule\FormsModule\Model\FormsManager;
use App\CoreModule\Model\ClientUniqueId;
use App\CoreModule\Traits\EventsTrait;
use App\model\GolfConfig;
use App\Traits\CoursesTrait;
use Nette;
use Nette\Utils\Json;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Response;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;
use Monty\Mailer;
use Nette\Mail\SmtpMailer;
use Nette\Bridges\ApplicationLatte\TemplateFactory;


class ApiPresenter extends Nette\Application\UI\Presenter
{

  use CoursesTrait;
  use EventsTrait;

  /** @var \App\CoreModule\Model\ContentsManager @inject */
  public $ContentsManager;

  /** @var \App\CoreModule\Model\EventsManager @inject */
  public $EventsManager;

    /** @var FormsManager @inject */
    public $FormsManager;

    /** @var ClientUniqueId @inject */
    public ClientUniqueId $clientUniqueId;

    /** @var SmtpMailer @inject */
    public SmtpMailer $mailer;

    /** @var GolfConfig @inject */
    public GolfConfig $golfConfig;

    /** @var TemplateFactory @inject */
    public TemplateFactory $templateFactory;


  public function startup(): void
  {
    parent::startup();

    $response = $this->getHttpResponse();
    $response->setHeader('Access-Control-Allow-Headers', 'Accept,Origin,Content-Type')
//        ->setHeader('Content-Type', 'application/json,charset=utf-8')
        ->setHeader('Access-Control-Allow-Methods', 'GET,POST')
        ->setHeader('Access-Control-Allow-Origin', '*');
  }

  public function actionCalendarInit($dump = false): void
  {
    $courses = $this->getFutureCoursesBegins();

    $days = \Monty\Helper::getDaysArr();

	$isWeekend = false;

	foreach ($courses as $_days) {
		if ($_days[6] || $_days[7]) {
			$isWeekend = true;
			break;
		}
	}

	if (!$isWeekend) {
		unset($days[6], $days[7]);
	}

    $data = [
      'courses' => $courses,
      "days" => $days,
      "isWeekend" => $isWeekend
    ];

    if ($dump) {
      exit();
    } else {
      $this->sendResponse(new JsonResponse($data));
    }
  }

  public function actionGetCourse(int $date_id)
  {
      $date = $this->EventsManager->getEventDate($date_id);

      if (!$date) {
          $this->notFoundResponse();
      }

      $event = $date->ref('content');
      $customFields = Json::decode($event->custom_fields ?: '{}');

      $data = [
          'id' => $date->id,
          'title' => $event->title,
          'course' => isset($customFields->course) ? $this->golfConfig->get('courses')[$customFields->course] : null,
          'start' => $date->start,
          'end' => $date->end,
          'lektor' => !empty($customFields->lektor) ? $customFields->lektor : null,
          'summary' => $this->EventsManager->getEventRegSummary($event->id)
      ];

      $this->sendResponse(new JsonResponse($data));
  }

  public function actionSaveParticipant()
  {
      $raw = $this->getHttpRequest()->getRawBody();

      if (!$raw) {
          $this->emptyRequestResponse();
      }

      Debugger::log($raw);
      $arr = Json::decode($raw, Json::FORCE_ARRAY);
      $vals = ArrayHash::from($arr);
      $date = $this->EventsManager->getEventDate($vals->id);
      $event = $date->ref('content');

      $role = $this->getParticipantRole($event->id);

      unset($vals->id);

      $recId = $this->FormsManager->saveRecord($event->reg_form, $vals);

      $this->EventsManager->insertEventPerson($event->id, $recId, "part");

      Debugger::log('event:' . $event->id . ' role:' . $role);

      $this->sendConfirmationEmail($vals, $event);

      $this->sendResponse(new JsonResponse([
          'status' => 'registered',
          'role' => $role
      ]));
  }

  public function notFoundResponse()
  {
      $this->sendResponse(new JsonResponse([
          'code' => 404,
          'status' => 'not found'
      ]));
  }

  public function emptyRequestResponse()
  {
      $this->sendResponse(new JsonResponse([
          'code' => 202,
          'status' => 'empty request'
      ]));
  }

  protected function sendEventFullResponse()
  {
      $this->sendResponse(new JsonResponse([
          'status' => 'event_full'
      ]));
  }

  protected function getParticipantRole(int $event, int $date = null): string
  {
      $summary = $this->EventsManager->getEventRegSummary($event, $date);

      if ($summary->free) {
          if ($summary->subLimit) {
              return $summary->partSpace > 0 ? 'part' : 'sub';
          } else {
              return 'part';
          }
      } else {
          return 'full';
      }
  }

  public function actionSendTestEmail()
  {
      $mailer = $this->mailer;
      $mail = new Nette\Mail\Message();
      $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostivař')
          ->addTo('info@montyit.cz')
          ->addReplyTo($this->golfConfig->get('adminEmail'), $this->golfConfig->get('adminName'))
          ->setSubject('Potvrzení registrace')
          ->setBody('Děkujeme za registraci na náš golf kurz. Ozveme se Vám');

      $mailer->send($mail);

      exit();
  }

  public function sendConfirmationEmail($vals, $event)
  {
      $mailer = $this->mailer;
      $eventCustomFields = $event->custom_fields ? Json::decode($event->custom_fields) : null;

      if ($courseType = $eventCustomFields->course ?? null) {
          $template = $this->templateFactory->createTemplate();
          $html = $template->renderToString(__DIR__ . "/../templates/mails/registrationConfirm-$courseType.latte", [
              'title' => $event->title
          ]);

          $mail = new Nette\Mail\Message();
          $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostivař')
              ->addTo($vals->e_mail)
              ->addReplyTo($this->golfConfig->get('adminEmail'), $this->golfConfig->get('adminName'))
              ->setSubject('Potvrzení registrace')
              ->setHtmlBody($html);
          $mailer->send($mail);
      }

      // admin mail
      $link = $this->link('//:Core:Admin:EventsPersons:eventPersonsList', [
          'id' => $event->id
      ]);
      $mail = new Nette\Mail\Message();
      $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostivař')
          ->addTo($this->golfConfig->get('adminEmail'))
          ->setSubject("Nová registrace na $event->title")
          ->setHtmlBody("Nový registrovaný účastník. <a href='$link'>koukni</a>");
      $mailer->send($mail);
  }

  private function log($what)
  {
      Debugger::log($what);
  }

}