<?php

declare(strict_types=1);

namespace App\CoreModule\Traits;

use Nette;
use Monty\Form;


trait UsersTrait
{
	/** @var \Monty\OAuth\OAuthFacebookControl @inject */
	public $OAuthFacebookControl;

	protected $FacebookClient;

	/** @var \Monty\OAuth\OAuthGoogleControl @inject */
	public $OAuthGoogleControl;

	protected $GoogleClient;


	public function loginStartup(): void
	{
		$template = $this->template;

		// \Tracy\Debugger::barDump($this->OAuthFacebookControl, "OAuthFacebookControl");
		$this->FacebookClient = $this->OAuthFacebookControl->getClient();
		// \Tracy\Debugger::barDump($this->FacebookClient, "FacebookClient");
		$template->fbLogin = $this->FacebookClient ? true : false;

		// \Tracy\Debugger::barDump($this->OAuthGoogleControl, "OAuthGoogleControl");
		$this->GoogleClient = $this->OAuthGoogleControl->getClient();
		// \Tracy\Debugger::barDump($this->GoogleClient, "GoogleClient");
		$template->googleLogin = $this->GoogleClient ? true : false;
	}


	public function actionGoogleSignIn()
	{
		#$this->gClient = $gClient;
		// \Tracy\Debugger::barDump($this->gClient, "gClient");
		$loginUrl = $this->GoogleClient->createAuthUrl();
		$this->redirectUrl($loginUrl);
	}

	public function actionGoogleSignInCallback()
	{
		$code = $this->getParameter("code");
		if ($code) {
			$token = $this->GoogleClient->fetchAccessTokenWithAuthCode($code);
		} else {
			$this->redirect("googleSignIn");
		}

		$gUser = $this->OAuthGoogleControl->getUserInfo($this->GoogleClient);
		$user = $this->UsersManager->getUser($gUser->email);

		if (!$user) {
			$this->isRegistrationAllowed(true);

			$user = $this->UsersManager->userSave([
				"email" => $gUser->email,
				"firstname" => $gUser->givenName,
				"lastname" => $gUser->familyName,
				"active" => true
			]);
		}

		$this->loginUser($gUser->email);

		exit();
	}

	public function actionFacebookSignIn() {
		$fb = $this->FacebookClient;
		$fb_helper = $fb->getRedirectLoginHelper();

		$pars = $this->getParameters();
		// \Tracy\Debugger::barDump($pars, "pars");
		if (!empty($pars["code"])) {
			// $facebook_login_url = $fb_helper->getLoginUrl($this->link("//:Front:Users:facebookSignInCallbak"));

			// $facebook_login_url = $fb_helper->getLoginUrl($this->link("//:Front:Users:facebookSignIn"));
			// $access_token = $fb_helper->getAccessToken();

			try {
				$access_token = $fb_helper->getAccessToken();
				$fb->setDefaultAccessToken($access_token);

				$graph_response = $fb->get("/me?fields=email,first_name,last_name", $access_token);
				// \Tracy\Debugger::barDump($graph_response, "graph res");
				$fb_user = $graph_response->getGraphUser();
				// \Tracy\Debugger::barDump($fb_user, "fb user info");

				$email = $fb_user->getEmail();
				$user = $this->UsersManager->getUser($email);

				if (!$user) {
					$this->isRegistrationAllowed(true);
					
					$user = $this->UsersManager->userSave([
						"email" => $email,
						"firstname" => $fb_user->getFirstname(),
						"lastname" => $fb_user->getLastname(),
						"active" => true
					]);
				}
				$this->loginUser($user->email);
			} catch (\Facebook\Exceptions\FacebookSDKException $e) {
				$this->loginNotSuccessfull();
			} catch (\Facebook\Exceptions\FacebookResponseException $e) {
		    	// $this->error('Graph returned an error: ' . $e->getMessage());
		    	$this->loginNotSuccessfull();
		    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
		        // When validation fails or other local issues
		        // $this->error('Facebook SDK returned an error: ' . $e->getMessage());
		        $this->loginNotSuccessfull();
		    }
		} else {
			if (isset($pars["error"])) {
				$this->loginNotSuccessfull();
			}

			$url = $this->OAuthFacebookControl->getRedirectUri();
			$this->redirectUrl($url);

			// Render Facebook login button
			// $facebook_login_url = '<div align="center"><a href="'.$facebook_login_url.'"><img src="php-login-with-facebook.gif" /></a></div>';
		}


		exit();
	}


	public function createComponentUserForm(): Form
	{
		$form = $this->FormsFactory->userForm();

		return $form;		
	}

	public function saveUser($v)
	{
		return $this->UsersManager->saveUser($v);
	}

	public function createComponentLoginForm()
	{
		$form = $this->FormsFactory->loginForm();

		$form->onSuccess[] = function($form, $vals) {
			// \Tracy\Debugger::barDump($vals, "login vals");
			try {
				$user = $this->UsersManager->getUser($vals->user);
				// if (!$user || ($user && !$user->active)) throw new \Nette\Security\AuthenticationException;

				$this->User->login($vals->user, $vals->password);
				$this->User->setExpiration($vals->remember ? '7 days' : '1 hour');
				$this->loginUser($user);
				// $this->flashMessage("Vítej zpátky $user->vocative :)", "alert-success");
				// $this->loginRedirect();
			} catch (Nette\Security\AuthenticationException $e) {
				$this->flashMessage("Špatné přihlašovací údaje, zkus to znovu", "alert-danger");
				$this->redirect("this");

				return;
			} catch (\Exceptions\AccountInactiveException $e) {
				$this->flashMessage("Váš účet není aktivní", "alert-warning");
				$this->redrawControl("flashes");
			}
			
		};

		$form->setPlaceholders();

		return $form;
	}

	public function loginRedirect() {
		$loginSession = $this->getSession("login");
		// \Tracy\Debugger::barDump($loginSession, "loginSession");
		
		if ($loginSession->backlink_custom) {
			$backlink = $this->getSession("login")->backlink_custom;
			// \Tracy\Debugger::barDump($backlink, "backlink custom");
			unset($this->getSession("login")->backlink_custom);
			if (strpos($backlink["page"], "/") !== false) {
				$this->redirectUrl($backlink["page"]);
			} else {
				$this->redirect($backlink["page"], $backlink["params"]);
			}
		} elseif ($loginSession->backlink) {
			// \Tracy\Debugger::barDump($loginSession->backlink, "backlink");
			$redirect = $loginSession->backlink;
			unset($loginSession->backlink);
			$this->restoreRequest($redirect);
		} else {
			$this->redirect(self::HOME_ROUTE);
		}
	}

	public function loginUser($user) {
		$identity = $this->UsersManager->createUserIdentity($user);
		$user = $this->getUser();
		$user->login($identity);
		$user->setExpiration("7 days");

		$data = $this->getUserData($user);

		if ($data->last_visit) {
			$new = false;
		} else {
			$new = true;
		}

		$str = "Vítej ";
		$str .= !$new ? "zpátky" : "";
		$str .= " " . $data->vocative . " :)";
		// $this->flashMessage($str, "alert-success");
		$this->bigMessage($str);

		$data->update(["last_visit" => new \DateTime]);

		$this->loginRedirect();
	}

	public function getUserData($id)
	{
		return $this->UsersManager->getUser($id);
	}

	public function isRegistrationAllowed($redirect = false)
	{
		$reg = $this->getSetting("registration");

		if (!$reg) {
			if ($redirect) {
				$this->bigMessage("Registrace není momentálně povolena :)", true);
				$this->redirect($this->getHomeRoute());
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

}