<?php

namespace PayPlug\src\utilities\helpers;

class UserHelper
{
    /**
     * @description check if user is logged or not
     *
     * @param $isEmail
     * @param $isApiKey
     *
     * @return array
     */
    public function isLogged($isEmail, $isApiKey)
    {
        if (!is_bool($isEmail)) {
            return [
                'result' => false,
                'message' => '$isEmail must be a bool type',
            ];
        }
        if (!is_bool($isApiKey)) {
            return [
                'result' => false,
                'message' => '$isApiKey must be a bool type',
            ];
        }

        if (!$isEmail) {
            return [
                'result' => false,
                'message' => 'user is not logged because $email is not valid',
            ];
        }
        if (!$isApiKey) {
            return [
                'result' => false,
                'message' => 'user is not logged because $isApiKey is not valid',
            ];
        }

        return [
            'result' => $isEmail && $isApiKey,
            'message' => '$user is logged',
        ];
    }
}
