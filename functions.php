<?php
function now() {
    return array(date('Y'), date('W'));
}

function timeToSeconds($sTime) {
    $aTime = explode(':', $sTime);
    return 60 * (60 * $aTime[0] + $aTime[1]);
}

function arrayToSeconds($aTime) {
    return 60 * (60 * $aTime['hour'] + $aTime['minute']);
}

function getTime($iYear, $iWeek, $iDay = 0) { 
    $iFirst = mktime(1, 1, 1, 1, 1, $iYear);
    $iOffset = (11 - date('w', $iFirst)) % 7 - 3 + $iDay; 
    return strtotime(($iWeek - 1) . ' weeks ' . $iOffset . ' days', $iFirst);
}

function convertType($sType) {
    global $aConfig;
    $sType = strtolower($sType);
    foreach ($aConfig['types'] as $sKey => $aTypes) {
        if ($sKey == $sType || in_array($sType, $aTypes)) {
            return $sKey;
        }
    }
    return $sKey;
}

function convertData($aData, &$aTable, &$aInfo) {
    global $aConfig;
    foreach ($aData as $sGroup => $aDays) {
        $aTable[$sGroup] = array();
        foreach ($aDays as $iDay => $aDay) {
            foreach ($aDay as $aCourse) {            
                $sText = isset($aCourse['room'])
                    ? sprintf('[%s-%s] %s @ %s', $aCourse['start'], $aCourse['end'], $aCourse['course'], $aCourse['room'])
                    : sprintf('[%s-%s] %s', $aCourse['start'], $aCourse['end'], $aCourse['course']);
                $aInfo[] = array(
                    'text' => $sText,
                    'type' => convertType($aCourse['type']));
                $iId = count($aInfo) - 1;
                $iStart = timeToSeconds($aCourse['start']);
                $iEnd = timeToSeconds($aCourse['end']);
                for ($iHour = $aConfig['start']['hour']; $iHour <= $aConfig['end']['hour']; ++$iHour) {
                    for ($iMinute = 0; $iMinute < $aConfig['steps']; ++$iMinute) {
                        $iTime = 60 * (60 * $iHour + $aConfig['step'] * $iMinute);
                        if ($iTime >= $iStart && $iTime <= $iEnd) {
                            $aTable[$sGroup][$iDay][$iHour][$iMinute] = $iId;
                        }
                    }
                }
            }
        }
    }
}