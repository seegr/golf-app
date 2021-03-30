<?php

namespace Monty;

use Nette;
use Exception;
use Nette\Utils\ArrayHash;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;


class Mail extends \Nette\Mail\Message {

	use \Monty\MailTrait;


	/*public function setPresenter($presenter) {
		$this->presenter = $presenter;
	}*/

	
	private function formatEmail($email, $name)
	{
		if (!$name && preg_match('#^(.+) +<(.*)>\z#', $email, $matches)) {
			return [$matches[2] => $matches[1]];
		} else {
			return [$email => $name];
		}
	}

	public function addTo($recipient, $name = null) {
		/*if (is_array($recipient)) {
			#\Tracy\Debugger::barDump("je pole");
			foreach ($recipient as $email => $name) {
				#\Tracy\Debugger::barDump($contact);
				#$this->setHeader('To', $this->formatEmail($contact, $name), true);
				$this->addRecipient($email, $name);
			}
		} else {
			#\Tracy\Debugger::barDump("neni pole");
			//$this->setHeader('To', $this->formatEmail($recipient, $name), true);
			$this->addRecipient($recipient, $name);
		}*/

		$this->addRecipient($recipient, $name);

		return $this;
	}

	public function addBcc($recipient, $name = null) {
		// $this->setHeader('Bcc', $this->formatEmail($email, $name), true);

		$this->addRecipient($recipient, $name, "Bcc");

		return $this;
	}

	public function addRecipient($email, $name, $header = "To") {
		if (!is_array($email)) {
			$arr = [$email];
		} else {
			$arr = $email;
		}

		foreach ($arr as $email => $name) {
			if (!is_numeric($email)) {
				$this->setHeader('To', $this->formatEmail($email, $name), true);
			} else {
				$this->setHeader('To', $this->formatEmail($name, $name), true);
			}
		}

		return $this;
	}

	public function send() {
		#\Tracy\Debugger::barDump("sending mail");
		#\Tracy\Debugger::barDump($this->getHeaders(), "mail headers");
		#\Tracy\Debugger::barDump($this->mailer, "mailer");

		#\Tracy\Debugger::barDump($this->getHtmlBody(), "html");
		#\Tracy\Debugger::barDump($this->getBody(), "body");

		$headers = $this->getHeaders();
		#\Tracy\Debugger::barDump($headers, "headers");

		if (!isset($headers["To"])) {
			throw new Exception("You have to add email recipient");
		}

		if ($this->styles) {
			$html = $this->getHtmlBody();
			if ($html) {
				$cssToInlineStyles = new CssToInlineStyles;
				$css = "";
				foreach ($this->styles as $style) {
					$css .= file_get_contents($style);
				}

				$styledHtml = $cssToInlineStyles->convert($html, $css);
				$this->setHtmlBody($styledHtml);
			}
		}

		if (!$this->getFrom()) {
			$this->setFrom($this->defaultSender);
		}


		$headers = $this->getHeaders();
		\Tracy\Debugger::barDump($headers, "headers");
		if (empty($headers["Subject"])) throw new \Exception("You have to set email subject!");
		
		switch ($this->mode) {
			case "send":
				\Tracy\Debugger::barDump("mail send");
				$this->mailer->send($this);
			break;

			case "log":
				\Tracy\Debugger::barDump("mail log");
				\Tracy\Debugger::barDump($this->getHeaders(), "mail headers");
			break;

			default:
				if (strpos($this->mode, "@") !== false) {
					$this->setHeader("To", null);
					$this->setHeader("Bcc", null);
					$this->addTo($this->mode);
				}
				#\Tracy\Debugger::barDump($this->getHeader("To"), "To header");
				$this->mailer->send($this);
			break;
		}

		$this->onSend($headers);

		return ArrayHash::from($headers);
	}
}