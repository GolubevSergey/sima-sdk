<?php

declare(strict_types=1);

namespace SimaLandSdk;

use Exception;

class Validator
{

    /**
     * Method validates is email, phone or password has been passed
     */
    public static function validateAuthorizationData(array $data)
    {
        if (!isset($data['email']) && !isset($data['phone'])) {
            throw new Exception("Email of phone needed for authorization");
        }
        if (!isset($data['password'])) {
            throw new Exception("Password should be set for authorization");
        }
    }

}