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
        $dates = $this->EventsManager->getEventsDates()->order("start DESC");

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
        $fEvents = $this->EventsManager->getEventsDates()
            ->where("start >= ?", new DateTime)
            ->order("start ASC");
        // bdump($fEvents->fetchAll());

        $times = self::getCoursesRange(true);
        // bdump($times, "times");

        $events = [];
        $len = count($times);
        $coursesParents = [];
        for ($i = 0; $i <= $len - 1; $i++) {
            $time = $times[$i]->format("H:i");
            bdump($time);

            $next = !empty($times[$i+1]) ? $times[$i+1]->format("H:i") : null;

            $events[$time] = [];
            for ($day = 1; $day <= 7; $day++) {
                $events[$time][$day] = [];
                
                foreach ($fEvents as $ev) {
                    if ($ev->start->format("N") == $day &&
                        $ev->start->format("H:i") >= $time &&
                        ($ev->start->format("H:i") < $next || !$next)) {
                        if (!in_array($ev->content, $coursesParents)) {
                            $data = [
                                'id' => $ev->id,
                                'content' => $ev->content,
                                'start' => $ev->start,
                                'end' => $ev->end,
                                "summary" => $this->EventsManager->getEventRegSummary($ev->content, null, true)
                            ];
                            $events[$time][$day][] = $data;
                            $coursesParents[] = $ev->content;
                        }
                    }
                }
            }
        }

        // bdump($events);

        return $events;
    }

}