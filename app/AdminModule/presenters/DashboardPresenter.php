<?php

namespace App\AdminModule\Presenters;

class DashboardPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter
{

    public function startup(): void
    {
        parent::startup();

        $this->redirect(":Core:Admin:Contents:contentsList", "event");
    }

    public function renderDashboard(): void
    {
        $template = $this->template;

        // $template->times = self::getTimes(); 
    }

}