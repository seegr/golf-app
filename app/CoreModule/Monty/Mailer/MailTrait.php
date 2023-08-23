<?php

namespace Monty;

use Nette\Utils\Callback;


trait MailTrait {

	protected $mailer;
    protected $montyMailer;
	protected $latte;
	protected $templatesFolder;
	protected $defaultSender;
	protected $styles = [];
    protected $linkGenerator, $templateFactory;
    protected $mode;
    protected $templatePath;
    protected $templatePars = [];
    protected $html;
    public $onSend = [];


	public function __construct($montyMailer, $mailer, $linkGenerator, $templateFactory) {
		$this->mailer = $mailer;
		$this->montyMailer = $montyMailer;
		$this->mode = $this->montyMailer->mode;
		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
	}


	public function setTemplatesFolder($templatesFolder) {
		$this->templatesFolder = $templatesFolder;
	}

	public function setTemplate($file, $params = []) {
		$this->html = true;

		$path = strpos($file, "/") !== false ? $file : $this->templatesFolder . "/" . $file;

		bdump($path, "path");
		$template = $this->templateFactory->createTemplate();
		$template->setFile($path);

		bdump($this->templatePars, "templatePars");
		$this->templatePars = $this->templatePars + $template->getParameters() + $params;
		$template->setParameters($this->templatePars);

		$latte = $template->getLatte();
		$latte->addProvider('uiControl', $this->linkGenerator);
		\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

		$this->latte = $latte;
		$this->templatePath = $path;
		
		bdump($template, "template");
		$this->setHtmlBody($template);

		return $this;
	}

	public function setDefaultSender($sender) {
		$this->defaultSender = $sender;

		return $this;
	}

	public function setStyles($styles) {
		$this->styles = $styles;

		return $this;
	}

	public function addTemplatePars($pars) {
		$this->templatePars = $this->templatePars + $pars;

		return $this;
	}

	public function onSend($vals) {
		bdump("onSend...");
		bdump($this->montyMailer->onSend, "mailer onSend callbacks");
		bdump($this->onSend, "send");

		$callbacks = array_merge($this->montyMailer->onSend, $this->onSend);
		bdump($callbacks, "callbacks");
		foreach ($callbacks as $callback) {
			bdump($callback, "callback");
			Callback::invoke($callback, $vals);
		}
	}

}