<?php

namespace App\CoreModule\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Monty\FileSystem;


class UsersManager extends BaseManager
{
	use Nette\SmartObject;

	const
		TABLE_USERS = 'users',
		TABLE_USERS_ROLES = "users_roles",
		COLUMN_ID = 'id',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_EMAIL = 'email',
		USERS_PATH = "users/";


	protected $userRoles;


	public function createUserIdentity($user) {
		$id = is_object($user) ? $user->id : $user;
		// \Tracy\Debugger::barDump($id, "id");

		$user = $this->getUser($id);
		$user = ArrayHash::from($user->toArray());
		// \Tracy\Debugger::barDump($user, "user");

		unset($user[self::COLUMN_PASSWORD_HASH]);

		$roles = $this->getUserRolesArray($user->id);
		\Tracy\Debugger::barDump($roles, "roles");
		//$roles = array_unique($roles);

		return new Nette\Security\Identity($user->id, $roles, $user); 
	}

	public function getUserIdentity($id) {
		return $this->createUserIdentity($id);
	}

	public function getUserRolesArray($user_id) {
		$roles = ["guest", "registered"];

		foreach ($this->getUserRoles($user_id) as $role) {
			$roles[] = $role->ref("role")->short;
		}

		//$roles = $this->assignUserRoles($roles);
		// \Tracy\Debugger::barDump($roles, "roles");

		return $roles;
	}

	/*public function assignUserRoles($roles) {
		foreach ($roles as $role) {
			$role = $this->getUserRole($role);
			#\Tracy\Debugger::barDump($role, "role");
			if ($role) {
				if (!in_array($role->short, $roles)) {
					$roles[] = $role;
				}
				$inherit = $role->inherit;
				if ($inherit && !in_array($inherit, $roles)) {
					$roles[] = $inherit;
					$roles = array_unique(array_merge($roles, $this->assignUserRoles($roles)));
				}
			}
		}

		return $roles;
	}*/

	public function getUserRole($role) {
		$table = $this->getRoles();
		if (is_numeric($role)) {
			#\Tracy\Debugger::barDump($role, "is numeric");
			$role = $table->get($role);
		} else {
			#\Tracy\Debugger::barDump($role, "is NOT numeric");
			$role = $table->where("short", $role)->fetch();
		}

		return $role;
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void
	 * @throws DuplicateNameException
	 */
	public function userSave($vals) {
		\Tracy\Debugger::barDump($vals, "vals");

		if (is_array($vals)) $vals = ArrayHash::from($vals);

		$name = "";
		if (!empty($vals->pre_title)) $name = $vals->pre_title;
		$name .= " " . $vals->firstname;
		$name .= " " . $vals->lastname;
		if (!empty($vals->suf_title)) $name .= " " . $vals->suf_title;

		$data = [			
			"firstname" => $vals->firstname,
			"lastname" => $vals->lastname,
			"fullname" => $name,
			"short" => !empty($vals->short) ? $vals->short : null,
			"email" => $vals->email,
			"gmail" => !empty($vals->gmail) ? $vals->gmail : null,
			"password" => !empty($vals->password) ? (new Passwords)->hash($vals->password) : null
		];

		if (isset($vals->active)) {
			$data["active"] = !empty($vals->active) ? $vals->active : 0;
		}
		if (!empty($vals->username)) {
			$data["username"] = Strings::lower(Strings::webalize($vals->username));
		}
		if (!empty($vals->tel)) {
			$data["tel"] = $vals->tel;
		}
		if (empty($vals->password)) {
			\Tracy\Debugger::barDump("heslo prazdne");
			unset($data["password"]);
		}

		try {
			if (!empty($vals->id)) {
				$id = $vals->id;

				if (!empty($vals->vocative)) {
					$data["vocative"] = $vals->vocative;
				}

				$this->getUser($id)->update($data);
			} else {
				//** vokativ
				\Tracy\Debugger::barDump("neni vocative");
				$vocative = new \Vokativ\Name;
				$voc = $vocative->vokativ($vals->firstname);
				
				$data["vocative"] = $voc ? ucfirst($voc) : $vals->firstname;

				$id = $this->getUsers()->insert($data);
			}
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			\Tracy\Debugger::barDump($e, "e");
			$col = $this->getDuplicateColumn($e);

			switch ($col) {
				case "username";
					throw new \Exceptions\DuplicateNameException("Uživatelské jméno již existuje");
				break;

				case "email":
					throw new \Exceptions\DuplicateEmailException("Email uživatele již existuje");
				break;

				case "short":
					throw new \Exceptions\DuplicateShortException("Zkratka uživatele již existuje");
				break;
			}
		}

		\Tracy\Debugger::barDump($id, "id");

		if (isset($vals->image) && $vals->image->hasFile()) {
			$this->userImageSave($id, $vals->image);
		}

		$this->generateUserHash($id);

		$user = $this->getUser($id);
		\Tracy\Debugger::barDump($user, "user");

		return $id;
	}

	public function saveUser($vals) {
		return $this->userSave($vals);
	}

	public function userImageSave($id, $file) {
		$fileData = FileSystem::getFileInfo($file);
		$image = Image::fromFile($file);
		$image->resize(500, null);
		FileSystem::createDir(self::USERS_PATH);
		$fileName = $id . "_" . $fileData->uniqueFileName;
		$image->save(self::USERS_PATH . $fileName);
		$this->getUser($id)->update(["image" => $fileName]);
	}

	public function getUsersTable() {
		return $this->db->table("users");
	}

	public function getUsers($ids = null) {
		$sel = $this->db->table("users")->select("users.*, CONCAT(users.firstname, ' ', users.lastname) AS fullname");

		if ($ids) $sel->where("users.id", $ids);

		return $sel;
	}

	public function getLektors($active = null) {
		$sel = $this->getUsers()
			->where(":users_roles.role.short", "lektor");

		if ($active) $sel->where("active", true);

		return $sel;
	}

	public function getUser($id, $formData = false) {
		$id = is_object($id) ? $id->id : $id;

		if (is_numeric($id)) {
			$selection = $this->getUsers()->get($id);
		} else {
			$selection = $this->getUsers()->whereOr([
				"username" => $id,
				"email" => $id,
				"gmail" => $id
			])->fetch();
		}


		if ($formData) {
			$data = ArrayHash::from($data);			
		} else {
			$data = $selection;
		}

		return $data;
	}

	public function getUsersRoles() {
		return $this->db->table(self::TABLE_USERS_ROLES);
	}

	public function userRolesSave($id, $roles) {
		$this->saveReferences("users_roles", "user", $id, "role", $roles);
	}

	public function addUserRole($id, $role) {
		$role = $this->getUserRole($role);

		$this->getUsersRoles()->insert(["user" => $id, "role" => $role->id]);
	}

	public function getRoles() {
		return $this->db->table("roles");
	}

	public function getRole($id) {
		$sel = $this->getRoles();

		return is_numeric($id) ? $sel->get($id) : $sel->where("short", $id)->fetch();
	}

	public function saveRole($vals) {
		$data = [
			"short" => !empty($vals->short) ? $vals->short : Strings::webalize($vals->title),
			"title" => $vals->title,
			"parent" => !empty($vals->parent) ? $vals->parent : null
		];

		if ($vals->id) {
			$id = $this->getRole($vals->id)->update($data);
		} else {
			$id = $this->getRoles()->insert($data);
		}

		return $id;
	}

	public function getResources() {
		return $this->db->table("resources");
	}

	public function getUserRoles($id) {
		return $this->db->table("users_roles")->where("user", $id);
	}

	public function getUserResources($id) {
		return $this->db->table("users_resources")->where("user", $id)->select("*, resource.resource AS resource_string");
	}

	public function isEmailUnique($email, $user_id = null) {
		$selection = $this->db->table("users")->where("email", $email);

		if ($user_id) {
			$selection->where("id != ?", $user_id);
		}

		if ($selection->count("*") > 0) {
			throw new \Exceptions\DuplicateEmailException("Uživatel s tímto emailem již existuje");
		}
	}

	public function userDelete($id) {
		$this->db->table("users")->get($id)->delete();
	}

	public function getUsersPath() {
		return self::USERS_PATH;
	}

	public function getDuplicateColumn($e) {
		$output = [];
		preg_match('/(?<=key \')(.*)(?=\')/', $e, $output);

		#\Tracy\Debugger::barDump($e, "sql error");
		#\Tracy\Debugger::barDump($output, "output");

		return $output[0];
	}

	public function generateUserHash($id) {
		if ($user = $this->getUser($id)) {

			$hash = Random::generate(40);
			while ($this->getUserByHash($hash)) {
				$hash = Random::generate(40);
			}

			$user->update([
				"hash" => $hash
			]);

			return $hash;
		}
	}

	public function getUserByHash($hash) {
		return $this->getUsers()->where(["hash" => $hash])->fetch();
	}

	public function saveUserPassword($id, $pass) {
		$this->getUser($id)->update([
			"password" => (new Passwords)->hash($pass)
		]);
	}
}


namespace Exceptions;

class DuplicateNameException extends \Exception {
	
}
class DuplicateEmailException extends \Exception {

}
class DuplicateShortException extends \Exception {

}
class AccountInactiveException extends \Exception {

}