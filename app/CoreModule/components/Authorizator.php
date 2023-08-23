<?php

namespace App\CoreModule\Components;


class Authorizator extends \Nette\Security\Permission implements \Nette\Security\IAuthorizator
{

	protected $acl, $user, $UsersManager;

	public function __construct(\App\CoreModule\Model\UsersManager $UsersManager)
	{
		$this->UsersManager = $UsersManager;
	}

    public function setUser($user) {
    	$this->user = $user;
    }

    // public function setRoles($roles) {
    // 	$this->addRole("guest");

    // 	foreach ($roles as $role) {
	 		// #bdump($role->short);
	 		// #bdump("dedi od " . $role->inherit);
	 		// // $parent = $role->parent ? $role->ref("parent")->short : null;
    // 		// $this->addRole($role->short, $parent);
    // 		$this->addRole($role->short);
    // 	}

    // 	#$this->addRole("user", "registered"); //** for individual resources
    // }

    // public function setResources($resources) {
    // 	foreach ($resources as $resource) {
    // 		$this->addResource($resource);
    // 	}
    // }

    // public function setUserResources($resources) {
    // 	foreach ($resources as $resource) {
    // 		$this->allow(null, $resource);
    // 	}
    // }

	public function defineRoles(): void
	{
		foreach ($this->UsersManager->getRoles() as $role) {
			// bdump($role, "role");
			$this->addRole($role->short, $role->inherit ? $role->ref("inherit")->short : null);
		}
	}
    
}