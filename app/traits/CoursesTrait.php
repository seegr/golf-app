<?php

namespace App\Traits;

use Nette\Database\Table\Selection;

use DateInterval;
use DatePeriod;
use DateTime;

trait CoursesTrait
{

    public function getCoursesSelection()
    {
        $dates = $this->ContentsManager->getEventsDates()->order("start DESC");

        bdump($dates->fetchAll(), "dates");
    }

    public static function getTimes(): array
    {
        return ["08:00", "09:00", "10:00", "17:00", "18:00", "18:30", "19:00", "19:30"];
    }

    public static function getCoursesRange(bool $toArray = false)
    {
        $period = new DatePeriod(new DateTime("08:00"), new DateInterval("PT1H"), new DateTime("20:01"));
        
        if ($toArray) {
            $times = [];
            foreach ($period as $time) {
                $times[] = $time;
            }
            return $times;
        } else {
            return $period;
        }
    }

    public function getDayTimeCourses($day, $time)
    {
        // bdump($day, "day");
        // bdump($time->format("H:i"), "time");
        // bdump(" ");
    }

    public function getFutureCoursesBegins()
    {
        $fEvents = $this->ContentsManager->getEventsDates()
            ->where("start >= ?", new DateTime)
            ->order("start ASC");
        // bdump($fEvents->fetchAll());

        $times = self::getCoursesRange(true);
        // bdump($times, "times");

        $events = [];
        $len = count($times);
        $coursesParents = [];
        for ($day = 1; $day <= 7; $day++) {
            $events[$day] = [];
            for ($i = 0; $i <= $len - 1; $i++) {
                $time = $times[$i]->format("H:i");
                $next = !empty($times[$i+1]) ? $times[$i+1]->format("H:i") : null;
                $events[$day][$time] = [];
                // bdump($time, "time start");
                // bdump($next, "time end");
                
                foreach ($fEvents as $ev) {
                    // bdump($ev, "ev");
                    if ($ev->start->format("N") == $day && $ev->start->format("H:i") >= $time && $ev->start->format("H:i") < $next) {
                        if (!in_array($ev->content, $coursesParents)) {
                            // bdump($ev->start->format("N"), "ev start day");
                            // bdump($ev->start->format("H:i"), "ev start time");
                            // bdump($time, "time");
                            // bdump($next, "next");
                            $events[$day][$time][] = $ev;
                            $coursesParents[] = $ev->content;
                        }
                    }
                }
            }
        }

        bdump($events);

        return $events;
    }

}