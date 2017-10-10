<?php
abstract class Rooster {
    const SEMESTER1_YEAR = 2011;
    const SEMESTER2_YEAR = 2012;
    const SEMESTER1_WEEK = 36;
    const SEMESTER2_WEEK = 36;

    var $iYear;
    var $iWeek;
    var $sWeek;

    function __construct() {
        $this->iYear = (int) date('Y');
        $this->iWeek = (int) date('W');
        $this->setWeek($this->iYear, $this->iWeek);
    }

    function setWeek($iYear, $iWeek) {
        if ($iYear == self::SEMESTER1_YEAR && $iWeek >= self::SEMESTER1_WEEK && $iWeek <= 52) {
            $iWeeks = $iWeek - self::SEMESTER1_WEEK + 1;
        } else if ($iYear == self::SEMESTER2_YEAR && $iWeek >= 1 && $iWeek < self::SEMESTER2_WEEK) {
            $iWeeks = $iWeek - self::SEMESTER2_WEEK + 52 + 1;
        } else {
            throw new Exception('Given week is out of range');
        }
        $this->iYear = $iYear;
        $this->iWeek = $iWeek;
        $this->sWeek = $iWeeks;        
    }

    function nextWeek() {
        if ($this->iWeek == 52) {
            ++$this->iYear;
            $this->iWeek = 0;
        }
        ++$this->iWeek;
    }

    function getWeek() {
        return array($this->iYear, $this->iWeek);
    }

    abstract function getPage($sObject);
    abstract function getData($sRooster = null);
}