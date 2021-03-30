<?php

namespace App\AdminModule\Presenters;

class DashboardPresenter extends \App\CoreModule\AdminModule\Presenters\AdminPresenter
{

    public function renderDashboard(): void
    {
        $template = $this->template;

        // $template->times = self::getTimes(); 
    }

}