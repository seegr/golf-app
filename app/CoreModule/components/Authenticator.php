<?php

declare(strict_types=1);

namespace App\CoreModule\Components;

use Nette;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;


class Authenticator extends \App\CoreModule\Model\UsersManager implements \Nette\Security\IAuthenticator
{

	/**
	 * Performs an authentication against e.g. database.
	 * and returns IIdentity on success or throws AuthenticationException
	 * @throws AuthenticationException
	 */
	function authenticate(array $credentials): IIdentity
	{
		// bdump($credentials, "credentials");

		list($user, $password) = $credentials;

		$user = $this->getUser($user);
		// bdump($user, "user");
		$pass = new Passwords;

		if (!$user) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
		} elseif (!$pass->verify($password, $user[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		} elseif (!$user->active) {
			throw new \Exceptions\AccountInactiveException("User account is not active");
		} elseif ($pass->needsRehash($user[self::COLUMN_PASSWORD_HASH])) {
			$user->update([
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			]);
		}

		return $this->createUserIdentity($user);
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	// public function authenticate(array $credentials): Nette\Security\IIdentity {
	// 	list($username, $password) = $credentials;

	// 	$user = $this->getUser($username);
	// 	// bdump($user, "user");

	// 	if (!$user) {
	// 		throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
	// 	} elseif (!Passwords::verify($password, $user[self::COLUMN_PASSWORD_HASH])) {
	// 		throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
	// 	} elseif (!$user->active) {
	// 		throw new \Exceptions\AccountInactiveException("User account is not active");
	// 	} elseif (Passwords::needsRehash($user[self::COLUMN_PASSWORD_HASH])) {
	// 		$user->update([
	// 			self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
	// 		]);
	// 	}

	// 	return $this->createUserIdentity($user);
	// }

}