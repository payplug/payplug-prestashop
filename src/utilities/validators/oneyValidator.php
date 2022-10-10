<?php

namespace PayPlug\src\utilities\validators;

class oneyValidator
{
    /**
     * @description  check if the oney is activated
     * for belgium/spain
     * @param $isOneyCountryValidFeature
     * @param $oneyAllowedCountries
     * @param $country
     *
     */
    public function isOneyAllowedCountry($oneyAllowedCountries='', $country='')
    {
        if (!is_string($oneyAllowedCountries) || !$oneyAllowedCountries) {
            return [
                'result' => false,
                'message' => 'Invalid oney allowed countries format'
            ];
        }
        if (!is_string($country) || !$country) {
            return [
                'result' => false,
                'message' => 'Invalid country format'
            ];
        }

        return [
            'result' => in_array($country, explode(",", $oneyAllowedCountries)),
            'message' => 'Success'
        ];
    }
}
