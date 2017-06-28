<?php

namespace App\Models;

class Admins extends ModelBase
{
    /*
    const EXAMPLE_CONST    = 0;

    // Use this method to use default values from DB
    public function beforeValidationOnCreate()
    {
        $this->some_field = $this->some_field ?:  $this->defaultValue();
    }*/

	public static function auth($login, $pwd)
	{
		if (empty($login) || empty($pwd)) {
			return false;
		}

		return self::findFirst(array(
        	'login = :login: AND password = :password:',
            'bind' => array('login' => $login, 'password' => md5($pwd))
        ));
	}

}