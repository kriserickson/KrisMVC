<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
class DateHelpers {

    public static function PrintableDate($dbDate)
    {
        return date(KrisConfig::DATE_STR, strtotime($dbDate));
    }

    public static function TwentyFourToHuman($start_time, $length)
    {
        $start_time = substr($start_time, 0, 5);
        $start_hours = substr($start_time, 0, 2);
        $start_minutes = substr($start_time, 3, 2);

        $length_minutes = $length - intval($length);

        $end_minutes = $start_minutes;
        $end_hours = $start_hours + intval($length);

        if ($length_minutes > 0)
        {
            $end_minutes += ($length_minutes * 60);
            if ($end_minutes >= 60)
            {
                $end_minutes -= 60;
                $end_hours++;
            }
        }



        return self::GetAmPm($start_hours, $start_minutes)." - ".self::GetAmPm($end_hours, $end_minutes);

    }

    static function GetAmPm($hours, $minutes)
    {
        if ($hours >= 12)
        {
            if ($hours > 12)
            {
                $hours -= 12;
            }
            $am_pm = " PM";
        }
        else
        {
            $am_pm = " AM";
        }

        return $hours.":".str_pad($minutes, 2, '0', STR_PAD_LEFT).$am_pm;
    }
}
