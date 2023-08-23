<?php

namespace Monty;

use Nette;
use Nette\Mail\SmtpMailer;
use Nette\Mail\SendmailMailer;
use Monty\Mail;
use Monty\Mailgun;


class Mailer {

	protected $mailer;
	protected $smtp;
	protected $host;
	protected $username;
	protected $password;
	protected $port;
	protected $secure;
	protected $sender;
    public $mailgun;
	// private $senderEmail;
	// private $senderName;
	protected $templates;
	public $mode, $send;
	protected $presenter;
	protected $styles = [];
	protected $mails = [];
	protected $linkGenerator;
	protected $templateFactory;
	public $onSend = [];



	public function __construct(Nette\Application\LinkGenerator $linkGenerator, Nette\Application\UI\ITemplateFactory $templateFactory) {
		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
		#bdump($linkGenerator, "linkGenerator");
	}

	public function setConfig($config) {
		foreach ($config as $par => $val) {
			if (property_exists($this, $par)) {
				$this->$par = $val;
			} else {
				throw new \Exception("mailing config parameter $par does not exist");
			}
		}

		if ($this->smtp) {
			$this->mailer = new SmtpMailer([
				"host" => $this->host,
				"port" => ($this->port) ? $this->port : 25,
				"username" => $this->username,
				"password" => $this->password,
				"secure" => $this->secure
			]);
		} else {
			$this->mailer = new SendmailMailer;
		}

		if ($this->send) {
			$this->mode = $this->send;
		}
	}

	/*public function setLinkGenerator($linkGenerator) {
		$this->linkGenerator = $linkGenerator;
	}*/

	/*public function setPresenter($presenter) {
		$this->presenter = $presenter;
	}*/

	public function setTemplatesFolder($folder) {
		$this->templates = $folder;
	}

	public function addMail() {
		if (!empty($this->mailgun)) {
			$mail = new \Monty\Mailgun($this, $this->mailer, $this->linkGenerator, $this->templateFactory);
			$mail->createMg();
			// $mail->setFrom($this->sender);
		} else {
			$mail = new Mail($this, $this->mailer, $this->linkGenerator, $this->templateFactory);
		}

		if ($this->templates) {
			$mail->setTemplatesFolder($this->templates);
		}

		$mail->setStyles($this->styles);
		$mail->setDefaultSender($this->sender);

		$this->mails[] = $mail;

		return $mail;
	}

	public function addEmail() {
		$email = $this->addMail();

		return $email;
	}

	public function sendAll() {
		foreach ($mails as $mail) {
			if (!$mail->getFrom()) {
				$mail->setFrom($this->sender);
			}
			$mail->send();
		}
	}

	public function setStyle($stylePath) {
		if (is_array($stylePath)) {
			foreach ($stylePath as $style) {
				$this->styles[] = $style;
			}
		} else {
			$this->styles[] = $stylePath;
		}
	}

	public function setMode($mode) {
		$this->mode = $mode;

		return $this;
	}

	public function getMailgunClient($domain = null) {
		if (!empty($this->mailgun)) {
			return \Mailgun\Mailgun::create($this->mailgun["key"]);
		} else {
			throw new \Exception("Cant find mailgun parameters in config.neon");
		}
	}

	public function getMgClient($domain = null) {
		return $this->getMailgunClient($domain = null);
	}

}