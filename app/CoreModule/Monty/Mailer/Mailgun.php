<?php

namespace Monty;

use Mailgun\Mailgun as MG;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;


class Mailgun {

	use \Monty\MailTrait;

	private $config;
	private $mg;
	private $from, $subject, $body, $replyTo;
	private $to = [];
	private $bcc = [];
	public $batch;
	

	public function setMailer($mailer) {
		$this->mailer = $mailer;

		return $this;
	}

	public function createMg() {
		// $this->mg = MG::create($this->config["key"]);
		$this->config = $this->montyMailer->mailgun;

		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;

		return $this;
	}

	public function setBody($text) {
		$this->body = $text;

		return $this;
	}

	public function setHtmlBody($text) {
		$this->html = true;
		$this->setBody($text);

		return $this;
	}

	public function setFrom($from) {
		$this->from = $from;

		return $this;
	}

	public function addTo($email, $name = null) {
		if (!is_array($email)) {
			$to = $email;
			// $to .= $name ? "<" . $name . ">" : null;

			$this->to[] = $to;
		} else {
			bdump($email, "email");

			foreach ($email as $address => $name) {
				$mail = is_numeric($address) ? $name : $address;
				$this->to[] = $mail;
			}
		}

		return $this;
	}

	public function addReplyTo($to) {
		$this->replyTo = $to;

		return $this;
	}

	public function addBcc($email) {
		if (is_array($email)) {
			$this->bcc = $this->bcc + $email;
		} else {
			$this->bcc[] = $bcc;
		}

		return $this;
	}

	public function getFrom() {
		return $this->from ? $this->from : $this->defaultSender;
	}

	public function send() {
		$mg = MG::create($this->config["key"]);
		bdump($this->config, "config");
		bdump($mg, "mg");
		$from = $this->getFrom();

		$pars = [
			"from" => $from,
			"to" => $this->to ? implode(";", $this->to) : $from,
			"bcc" => $this->bcc ? implode(";", $this->bcc) : null,
			"subject" => $this->subject
		];

		if ($this->replyTo) {
			$pars["reply-to"] = $this->replyTo;
			$pars["h:Reply-To"] = $this->replyTo;
		}

		if ($this->html) {
			if ($this->templatePath) {
				$template = $this->templateFactory->createTemplate();
				$latte = $template->getLatte();
				$latte->addProvider('uiControl', $this->linkGenerator);
				// $template->setFile($path);
				// $template->setParameters($params);

				$this->body = $latte->renderToString($this->templatePath, $this->templatePars);
				$this->styleHtml();
			}

			$pars["html"] = $this->body;
			$pars["text"] = $this->buildText($this->body);
		} else {
			$pars["text"] = $this->body;
		}

		bdump($this->mode, "send mode");
		switch ($this->mode) {
			case "send":
				bdump("mail send");
				bdump($pars, "pars");
				$resp = $mg->messages()->send($this->config["domain"], $pars);

				$this->onSend($pars + ["mg" => $resp]);
			break;

			case "log":
				bdump("mail log");
				bdump($this, "mail");
			break;

			default:
				bdump("mail test mode");
				bdump($pars, "pars");
				if (strpos($this->mode, "@") !== false) {
					$pars["to"] = $this->mode;
					$pars["bcc"] = null;
					$resp = $mg->messages()->send($this->config["domain"], $pars);
					
					$this->onSend($pars + ["mg" => $resp]);
				}
			break;
		}
	}

	public function sendBatch() {
		$client = new MG($this->config["key"]);
		$msg = $client->BatchMessage($this->config["domain"]);
		$msg->setFromAddress($this->getFrom());
		$msg->setSubject($this->subject);
		if ($this->html) {
			$this->body = $this->latte->renderToString($this->templatePath, $this->templatePars);
			$this->styleHtml();
			$msg->setHtmlBody($this->body);
		} else {
			$msg->setHtmlBody($this->body);
		}

		bdump($this->to, "to");
		bdump($this->mode, "mode");
		switch ($this->mode) {
			case "send":
				foreach ($this->to as $email) {
					bdump($email, "email");
					$msg->addToRecipient($email);
				}

				bdump($msg, "msg");
				$msg->finalize();
			break;

			case "log":
				bdump("mail log");
				bdump($this, "mail");
			break;

			default:
				if (strpos($this->mode, "@") !== false) {
					$msg->addToRecipient($this->mode);
				}
				
				bdump($msg, "msg");
				$msg->finalize();
			break;
		}

	}

	public function batchSend() {
		$this->sendBatch();
	}

	public function styleHtml() {
		if ($this->styles) {
			if ($this->html) {
				$html = $this->body;
				$cssToInlineStyles = new CssToInlineStyles;
				$css = "";
				foreach ($this->styles as $style) {
					$css .= file_get_contents($style);
				}
				bdump($css, "css");

				$styledHtml = $cssToInlineStyles->convert($html, $css);
				$this->body = $styledHtml;
				bdump($this->body, "body");
			}
		}
	}

	protected function buildText($html) {
		$text = Strings::replace($html, [
			'#<(style|script|head).*</\\1>#Uis' => '',
			'#<t[dh][ >]#i' => ' $0',
			'#<a\s[^>]*href=(?|"([^"]+)"|\'([^\']+)\')[^>]*>(.*?)</a>#is' => '$2 &lt;$1&gt;',
			'#[\r\n]+#' => ' ',
			'#<(/?p|/?h\d|li|br|/tr)[ >/]#i' => "\n$0",
		]);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$text = Strings::replace($text, '#[ \t]+#', ' ');
		return trim($text);
	}

	// public function getMailgunClient($domain = null) {
	// 	return \Mailgun\Mailgun::create($this->config["key"]);
	// }

	// public function getMgClient($domain = null) {
	// 	return $this->getMailgunClient($domain = null);
	// }

}