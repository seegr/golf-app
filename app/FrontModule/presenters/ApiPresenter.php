<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use Nette;
use Nette\Utils\Json;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Response;


class ApiPresenter extends Nette\Application\UI\Presenter
{

  use \App\Traits\CoursesTrait;

  /** @var \App\CoreModule\Model\ContentsManager @inject */
  public $ContentsManager;

  public function startup(): void
  {
    parent::startup();
    //do some auth
    
    $response = $this->getHttpResponse();
    $response->setHeader('Access-Control-Allow-Headers', 'accept');
    $response->setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,DELETE,PUT');
    $response->setHeader('Access-Control-Allow-Origin', '*');
  }

  public function actionFrontInit($dump = false): void
  {
		// $template->range = self::getCoursesRange();
		// $template->courses = $this->getFutureCoursesBegins();
    
    // $range = self::getCoursesRange();
    $courses = $this->getFutureCoursesBegins();

    // bdump($range);
    bdump($courses);

    $data = [
      // 'range' => $range,
      'courses' => $courses,
      "days" => \Monty\Helper::getDaysArr()
    ];

    $data;

    if ($dump) {
      // dump($range);
      dump($courses);
      dump($data);
      exit();
    } else {
      $this->sendResponse(new JsonResponse($data));
    }
  } 

}
