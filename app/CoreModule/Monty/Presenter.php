<?php

namespace Monty;

use Nette;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Strings;

use App\Components\Translator;
use Monty\Navigation;
use Monty\Helper;
use App\Filters\DateIntervalFilter;


abstract class Presenter extends \Nette\Application\UI\Presenter {

	// protected $request, $payload, $httpResponse, $signalReceiver;
	// protected $globalParams = [];

	/**
	 * @return Nette\Application\IResponse
	 */
	/*public function run(\Nette\Application\Request $request)
	{
		$this->httpResponse = $this->getHttpResponse();

		try {
			// STARTUP
			$this->request = $request;
			$this->payload = $this->payload ?: new \stdClass;
			$this->setParent($this->getParent(), $request->getPresenterName());

			if (!$this->httpResponse->isSent()) {
				$this->httpResponse->addHeader('Vary', 'X-Requested-With');
			}

			$this->initGlobalParameters();
			$this->checkRequirements($this->getReflection());
			$this->onStartup($this);
			$this->startup();
			if (!$this->startupCheck) {
				$class = $this->getReflection()->getMethod('startup')->getDeclaringClass()->getName();
				throw new Nette\InvalidStateException("Method $class::startup() or its descendant doesn't call parent::startup().");
			}
			// calls $this->action<Action>()
			$this->tryCall($this->formatActionMethod($this->action), $this->params);

			// autoload components
			foreach ($this->globalParams as $id => $foo) {
				$this->getComponent($id, false);
			}

			if ($this->autoCanonicalize) {
				$this->canonicalize();
			}
			if ($this->httpRequest->isMethod('head')) {
				$this->terminate();
			}

			// SIGNAL HANDLING
			// calls $this->handle<Signal>()
			$this->processSignal();

			// RENDERING VIEW
			$this->beforeRender();
			// calls $this->render<View>()
			$this->tryCall($this->formatRenderMethod($this->view), $this->params);
			$this->render();
			$this->afterRender();

			// save component tree persistent state
			$this->saveGlobalState();
			if ($this->isAjax()) {
				$this->payload->state = $this->getGlobalState();
			}

			// finish template rendering
			if ($this->getTemplate()) {
				$this->sendTemplate();
			}

		} catch (Application\AbortException $e) {
			// continue with shutting down
			if ($this->isAjax()) {
				try {
					$hasPayload = (array) $this->payload;
					unset($hasPayload['state']);
					if ($this->response instanceof Responses\TextResponse && $this->isControlInvalid()) {
						$this->snippetMode = true;
						$this->response->send($this->httpRequest, $this->httpResponse);
						$this->sendPayload();
					} elseif (!$this->response && $hasPayload) { // back compatibility for use terminate() instead of sendPayload()
						trigger_error('Use $presenter->sendPayload() instead of terminate() to send payload.');
						$this->sendPayload();
					}
				} catch (Application\AbortException $e) {
				}
			}

			if ($this->hasFlashSession()) {
				$this->getFlashSession()->setExpiration($this->response instanceof Responses\RedirectResponse ? '+ 30 seconds' : '+ 3 seconds');
			}

			// SHUTDOWN
			$this->onShutdown($this, $this->response);
			$this->shutdown($this->response);

			return $this->response;
		}
	}*/


	/**
	 * Initializes $this->globalParams, $this->signal & $this->signalReceiver, $this->action, $this->view. Called by run().
	 * @return void
	 * @throws Nette\Application\BadRequestException if action name is not valid
	 */
	// private function initGlobalParameters()
	// {
	// 	// init $this->globalParams
	// 	$this->globalParams = [];
	// 	$selfParams = [];

	// 	$params = $this->request->getParameters();
	// 	if (($tmp = $this->request->getPost('_' . self::SIGNAL_KEY)) !== null) {
	// 		$params[self::SIGNAL_KEY] = $tmp;
	// 	} elseif ($this->isAjax()) {
	// 		$params += $this->request->getPost();
	// 		if (($tmp = $this->request->getPost(self::SIGNAL_KEY)) !== null) {
	// 			$params[self::SIGNAL_KEY] = $tmp;
	// 		}
	// 	}

	// 	foreach ($params as $key => $value) {
	// 		if (!preg_match('#^((?:[a-z0-9_]+-)*)((?!\d+\z)[a-z0-9_]+)\z#i', $key, $matches)) {
	// 			continue;
	// 		} elseif (!$matches[1]) {
	// 			$selfParams[$key] = $value;
	// 		} else {
	// 			$this->globalParams[substr($matches[1], 0, -1)][$matches[2]] = $value;
	// 		}
	// 	}

	// 	// init & validate $this->action & $this->view
	// 	$this->changeAction(isset($selfParams[self::ACTION_KEY]) ? $selfParams[self::ACTION_KEY] : self::DEFAULT_ACTION);

	// 	// init $this->signalReceiver and key 'signal' in appropriate params array
	// 	$this->signalReceiver = $this->getUniqueId();
	// 	if (isset($selfParams[self::SIGNAL_KEY])) {
	// 		$param = $selfParams[self::SIGNAL_KEY];
	// 		if (!is_string($param)) {
	// 			$this->error('Signal name is not string.');
	// 		}
	// 		$pos = strrpos($param, '-');
	// 		if ($pos) {
	// 			$this->signalReceiver = substr($param, 0, $pos);
	// 			$this->signal = substr($param, $pos + 1);
	// 		} else {
	// 			$this->signalReceiver = $this->getUniqueId();
	// 			$this->signal = $param;
	// 		}
	// 		if ($this->signal == null) { // intentionally ==
	// 			$this->signal = null;
	// 		}
	// 	}

	// 	$this->loadState($selfParams);
	// }



	// public function getPresenterTemplatePath() {
	// 	$trace = explode(":", $this->name);
	// 	$name = end($trace);

	// 	$path = __DIR__ . "/../templates/" . $name;

	// 	bdump($path, "path");
	// 	return $path;
	// }
}