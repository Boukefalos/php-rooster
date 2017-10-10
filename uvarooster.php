<?php
require_once 'rooster.php';

class UvaRooster extends Rooster {
    const URL = 'http://rooster.uva.nl/current_nl/';

    function getPage($sObject) {
        $sUrl = sprintf('%sshowtimetable.aspx?%s', self::URL, http_build_query(array(
            'type' => 'posbydayurl',
            'idstring' => $sObject,
            'weeks' => $this->sWeek)));

        if (($this->sContents = file_get_contents($sUrl)) === false) {
            throw new Exception('Failed to load page');
        }
        return $this->sContents;
    }

    function getData($sRooster = null) {
        $sRooster = isset($sRooster) ? $sRooster : $this->sContents;        
        if (substr_count($sRooster, '<td><b>') != 7) {
            echo $sRooster;
            throw new Exception('Page does not contain valid data');
        }
        $aDays = explode('<td><b>', $sRooster);
        array_shift($aDays);
        array_pop($aDays);
        array_pop($aDays);
        $aData = array();
        foreach ($aDays as $iDay => $sDay) {
            if (strpos($sDay, '<tbody>') !== false) {        
                $aColumns = null;
                $aRows = explode('<tr>', $sDay);
                array_shift($aRows);
                array_pop($aRows);
                $sHeader = array_shift($aRows);
                if (!isset($aColumns)) {
                    foreach (explode("\n", strip_tags($sHeader)) as $iColumn => $sColumn) {
                        switch (trim($sColumn)) {
                            case 'Start':
                                $aColumns[$iColumn] = 'start';
                                break;
                            case 'Eind':
                                $aColumns[$iColumn] = 'end';
                                break;
                            case 'Vak':
                                $aColumns[$iColumn] = 'course';
                                break;
                            case 'Type':
                                $aColumns[$iColumn] = 'type';
                                break;
                            case 'Locatie':
                                $aColumns[$iColumn] = 'room';
                                break;
                        }
                    }
                }

                $aData[$iDay] = array();
                foreach ($aRows as $sRow) {
                    $aInfo = array();
                    $aRow = explode("\n", strip_tags($sRow));
                    foreach ($aColumns as $iColumn => $sColumn) {
                        if (isset($aRow[$iColumn])) {
                            $aRow[$iColumn] = trim($aRow[$iColumn]);
                            $aInfo[$sColumn] = $sColumn == 'course'
                                ? preg_replace('~[\s]+\([^\)]+\)$~', null, $aRow[$iColumn])
                                : $aRow[$iColumn];
                        }
                    }
                    $aData[$iDay][] = $aInfo;
                }
            }
        }
        return $aData;
    }
}