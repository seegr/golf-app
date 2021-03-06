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
		// $template->range = self::getCoursesRange();
		// $template->courses = $this->getFutureCoursesBegins();
    
    // $range = self::getCoursesRange();
    $courses = $this->getFutureCoursesBegins();

    // bdump($range);
    bdump($courses);

    $days = \Monty\Helper::getDaysArr();
    unset($days[6], $days[7]);

    $data = [
      // 'range' => $range,
      'courses' => $courses,
      "days" => $days
    ];

    if ($dump) {
      // dump($range);
      dump($courses);
      dump($data);
      exit();
    } else {
      $this->sendResponse(new JsonResponse($data));
    }
  }

  public function actionGetCourse(int $date_id)
  {
      bdump($date_id);
      $date = $this->EventsManager->getEventDate($date_id);
      bdump($date);

      if (!$date) {
          $this->notFoundResponse();
      }

      $event = $date->ref('content');
      $customFields = Json::decode($event->custom_fields ?: '{}');

      $data = [
          'id' => $date->id,
          'title' => $event->title,
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

      //todo mailer service

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
      $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostiva??')
          ->addTo('info@montyit.cz')
          ->addReplyTo($this->golfConfig->get('adminEmail'), $this->golfConfig->get('adminName'))
          ->setSubject('Potvrzen?? registrace')
          ->setBody('D??kujeme za registraci na n???? golf kurz. Ozveme se V??m');

      bdump($mail->getHeaders());
      bdump($mailer);
      $mailer->send($mail);

      exit();
  }

  public function sendConfirmationEmail($vals, $event)
  {
      $mailer = $this->mailer;

      $template = $this->templateFactory->createTemplate();
      $html = $template->renderToString(__DIR__ . '/../templates/mails/registrationConfirm.latte', [
          'title' => $event->title
      ]);

      $mail = new Nette\Mail\Message();
      $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostiva??')
          ->addTo($vals->e_mail)
          ->addReplyTo($this->golfConfig->get('adminEmail'), $this->golfConfig->get('adminName'))
          ->setSubject('Potvrzen?? registrace')
          ->setHtmlBody($html);

      $mailer->send($mail);

      // admin mail
      $link = $this->link('//:Core:Admin:EventsPersons:eventPersonsList', [
          'id' => $event->id
      ]);
      $mail = new Nette\Mail\Message();
      $mail->setFrom($this->golfConfig->get('emailFrom'), 'Golf Hostiva??')
          ->addTo($this->golfConfig->get('adminEmail'))
          ->setSubject("Nov?? registrace na $event->title")
          ->setHtmlBody("Nov?? registrovan?? ????astn??k. <a href='$link'>koukni</a>");

      $mailer->send($mail);
  }

  private function log($what)
  {
      Debugger::log($what);
  }

}