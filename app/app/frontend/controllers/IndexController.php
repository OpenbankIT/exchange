<?php

namespace App\Frontend\Controllers;

class IndexController extends ControllerBase
{
	public function indexAction()
	{
        $payments = \App\Collections\Cashin::find();

        $this->view->payments = $payments;
	}
}