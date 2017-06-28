<?php

namespace App\Models;

class Users extends ModelBase
{
    const ROLE_CLIENT       = 1;
    const ROLE_ORGANIZATION = 2;

    public function beforeValidationOnCreate()
    {
        $this->usr_created = $this->defaultValue();
    }
}