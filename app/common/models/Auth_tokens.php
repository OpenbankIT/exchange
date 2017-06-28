<?php

namespace App\Models;

class Auth_tokens extends ModelBase
{
    public function beforeValidationOnCreate()
    {
        $this->atk_created = $this->defaultValue();
    }

    public static function removeOld()
    {
        $q = "DELETE FROM auth_tokens
            WHERE atk_created < DATE_SUB(NOW(), INTERVAL 7 DAY)";

        return self::execute($q);
    }
}