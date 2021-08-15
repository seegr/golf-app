<?php


namespace App\CoreModule\Model;


use Ramsey\Uuid\Uuid;
use Nette\Http\Session;

class ClientUniqueId
{
    /** @var Session @inject */
    public $session;

    public function set(): void
    {
        $sess = $this->session->getSection("client");
//        bdump($sess->uuid);
        if (!$sess->uuid) {
            $uuid = Uuid::uuid4();
            $sess->uuid = $uuid->toString();
        }

        // bdump($sess->uuid, "uuid");
    }

    public function get(): ?string
    {
        return $this->session->getSection("client")->uuid;
    }

}