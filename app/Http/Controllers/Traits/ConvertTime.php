<?php

namespace App\Http\Controllers\Traits;

trait ConvertTime
{
    private function convertTimeToAest($timestamp)
    {
        $dateTime = new \DateTime($timestamp);
        $dateTime->setTimezone(new \DateTimeZone('Australia/Brisbane'));
        return $dateTime->format('Y-m-d H:i:s');
    }

    private function convertTimeFromAest($timestamp)
    {
        $dateTime = new \DateTime($timestamp, new \DateTimeZone('Australia/Brisbane'));
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        return $dateTime->format('Y-m-d H:i:s');
    }
}