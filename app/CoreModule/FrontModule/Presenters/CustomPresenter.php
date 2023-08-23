<?php

declare(strict_types=1);

namespace App\CoreModule\FrontModule\Presenters;

use Nette;


class CustomPresenter extends FrontPresenter
{

	public function actionDefault($template): void
	{
		if ($template) {
			// $file = $this->getTemplateFile();
			$this->view = $template;
			// $this->template->setFile($template . ".latte");
		}
		// bdump($template, "template");
		// bdump($this->getParameters(), "pars");
	}


	public function actionTest(): void
	{
		// $this->bigMessage("Tralala test BIG message :)", true);
	}

	public function actionMessagePage(): void
	{
		$session = $this->getSession("message_page");
		$template = $this->template;

		if ($session->template) {
			bdump($session, "session");
			$template->setFile($session->template);
			$template->setParameters($session->attrs);
		}

		$template->heading = $session->heading;
		$template->message = $session->message;
		$template->actions = $session->actions;
	}

	public function handleTestAction()
	{
		$this->bigMessage("popup BIG message");
	}

}