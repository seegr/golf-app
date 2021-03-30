<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use Nette;


class HomePresenter extends FrontPresenter
{
	use \App\Traits\CoursesTrait;


	public function renderHome($id): void
	{
		$template = $this->template;

		$template->range = self::getCoursesRange();
		$template->courses = $this->getFutureCoursesBegins();

		$dates = $this->getCoursesSelection();
	}

}
