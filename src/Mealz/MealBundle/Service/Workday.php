<?php


namespace App\Mealz\MealBundle\Service;

use DateInterval;
use DateTime;

/**
 * logic to determine the next workday
 */
class Workday
{

    /**
     * array of weekdays that are considered workdays
     *
     * monday(1) to sunday(7)
     *
     * @var array
     */
    protected $weekdayWhitelist = array('1', '2', '3', '4', '5');

    /**
     * array of dates that are not considered workdays
     *
     * format "mm-dd"
     *
     * @var array
     */
    protected $dayBlacklist = array(
        // new year
        '01-01',
        // christmas
        '12-24',
        '12-25',
        '12-26',
    );

    protected $blacklistRelToEaster = array(
        '-2 days',
        '+ 0 days',
        '+1 day',
        '+39 days',
        '+50 days'
    );

    public function setWeekdayWhitelist($whitelist): void
    {
        $this->weekdayWhitelist = $whitelist;
    }

    /**
     * @param array $dayBlacklist
     */
    public function setDayBlacklist($dayBlacklist): void
    {
        $this->dayBlacklist = $dayBlacklist;
    }

    /**
     * @param array $blacklistRelToEaster
     */
    public function setBlacklistRelToEaster($blacklistRelToEaster): void
    {
        $this->blacklistRelToEaster = $blacklistRelToEaster;
    }

    /**
     * gives the next workday after the given date
     *
     * @param null|string|DateTime $date
     * @return DateTime
     */
    public function getNextWorkday($date = null)
    {
        if ($date === null) {
            $date = new DateTime();
        } elseif ($date instanceof DateTime === true) {
            $date = clone $date;
        } else {
            $date = new DateTime($date);
        }

        while (true) {
            $date->modify('+1 day');
            if ($this->dateIsValid($date)) {
                return $date;
            }
        }
    }

    protected function dateIsValid(DateTime $date): bool
    {
        if (in_array($date->format('N'), $this->weekdayWhitelist) === false) {
            return false;
        }

        if (in_array($date->format('m-d'), $this->dayBlacklist) === true) {
            return false;
        }

        $easter = $this->getEasterDate($date->format('Y'));
        foreach ($this->blacklistRelToEaster as $easterRelative) {
            $blacklistDay = clone $easter;
            $blacklistDay->modify($easterRelative);

            if ($blacklistDay->format('m-d') == $date->format('m-d')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @see https://www.php.net/manual/de/function.easter-date.php
     *
     * @param string $year
     *
     * @return DateTime
     */
    private function getEasterDate($year)
    {
        $base = new DateTime($year . '-03-21');
        $days = easter_days($year);
    
        return $base->add(new DateInterval("P{$days}D"));
    }
}
