<?php
require_once 'config.php';
require_once 'functions.php';

class Template {
    static $aDays = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY');
    const DAY = 'j M';

    static function getWeeks($aTables, $aInfos, $aWeeks) {
        $aRoosters = array();
        $sRooster = '';
        foreach ($aWeeks as $aWeek) {
            $sRooster .= self::getRooster(current($aTables), current($aInfos), $aWeek[0], $aWeek[1]);
            next($aTables);
            next($aInfos);
        }
        return str_replace(
            array('{LEGENDA}', '{ROOSTER}'),
            array(self::getLegenda(), $sRooster),
            file_get_contents('template/page.html'));
    }

    static function getWeek($aTable, $aInfo, $iYear, $iWeek) {
        return str_replace(
            array('{LEGENDA}', '{ROOSTER}'),
            array(self::getLegenda(), self::getRooster($aTable, $aInfo, $iYear, $iWeek)),
            file_get_contents('template/page.html'));
    }

    static function getRooster($aTable, $aInfo, $iYear, $iWeek) {
        global $aConfig;
        $iGroups = count($aGroups = array_keys($aTable));
        $sBarTemplate = file_get_contents('template/rooster-bar.html');
        $sBar = '';
        foreach ($aGroups as $iGroup => $sGroup) {
            $sBar .= str_replace('{GROUP}', $sGroup, $sBarTemplate);
        }

        $sBar = str_repeat($sBar, 5);
        $sRooster =  str_replace(
            array('{COLUMNS}', '{WEEK}', '{GROUPS}', '{BAR}', '{BODY}'),
            array(5 * $iGroups, $iWeek, $iGroups, $sBar, self::getRoosterBody($aTable, $aInfo)),
            file_get_contents('template/rooster.html'));

        foreach (self::$aDays as $iDay => $sDay) {
            $sRooster = str_replace(sprintf('{%s}', $sDay), date(self::DAY, getTime($iYear, $iWeek, $iDay)), $sRooster);
        }

        return $sRooster;
    }

    static function getRoosterBody($aTable, $aInfo) {
        global $aConfig;
        $sRowTemplate = file_get_contents('template/rooster-body-row.html');
        $sRowHourTemplate = file_get_contents('template/rooster-body-row-hour.html');
        $sRowMinuteTemplate = file_get_contents('template/rooster-body-row-minute.html');
        $sRowDataTemplate = file_get_contents('template/rooster-body-row-data.html');
        $iGroups = count($aGroups = array_keys($aTable));
        $sRoosterBody = '';
        for ($iHour = $aConfig['start']['hour']; $iHour <= $aConfig['end']['hour']; ++$iHour) {
            if ($iHour == $aConfig['start']['hour']) {
                $iMinute = $aConfig['start']['minute'] / $aConfig['step'];
                $iSteps = $aConfig['steps'] - floor($iMinute);
            } else {
                $iMinute = 0;
                $iSteps = $aConfig['steps'];
            }
            if ($iHour == $aConfig['end']['hour']) {
                $iStop = $aConfig['end']['minute'] / $aConfig['step'];
                $iSteps = ceil($iStop);
            } else {
                $iStop = $aConfig['steps'];
            }
            $bHour = true;
            for (; $iMinute < $iStop; ++$iMinute) {
                if ($bHour) {
                    $sRowHour = str_replace(array('{SPAN}', '{HOUR}'), array($iSteps, $iHour), $sRowHourTemplate);
                    $bHour = false;
                } else {
                    $sRowHour = null;
                }
                $sRowMinute = str_replace('{MINUTE}', sprintf('%02d', $aConfig['step'] * $iMinute), $sRowMinuteTemplate);
                $sRowData = '';
                for ($iDay = 0; $iDay < 5; ++$iDay) {
                    foreach ($aGroups as $iGroup => $sGroup) {
                        if (isset($aTable[$sGroup][$iDay][$iHour][$iMinute])) {
                            $iId = $aTable[$sGroup][$iDay][$iHour][$iMinute];
                            $sText = $aInfo[$iId]['text'];
                            $sType = $aInfo[$iId]['type'];
                        } else {
                            $sText = null;
                            $sType = 'none';
                        }
                        $sRowData .= str_replace(
                            array('{TYPE}', '{BORDER}', '{TITLE}'),
                            array($sType, $iGroup == $iGroups - 1 ? ' border' : null, $sText),
                            $sRowDataTemplate);
                    }
                }
                $sRoosterBody .= str_replace(
                    array('{HOUR}', '{MINUTE}', '{DATA}'),
                    array($sRowHour, $sRowMinute, $sRowData), $sRowTemplate);
            }
        }
        return $sRoosterBody;
    }

    static function getLegenda() {
        global $aConfig;
        $sRowTemplate = file_get_contents('template/legenda-row.html');
        $sData = '';
        foreach ($aConfig['colors'] as $sType => $aColor) {
            $sData .= str_replace(array('{NAME}', '{TYPE}'), array($aColor[0], $sType), $sRowTemplate);
        }
        return str_replace('{BODY}', $sData, file_get_contents('template/legenda.html'));
    }

    static function getFormWeeks() {
        global $aConfig;

        $sOption = file_get_contents('template/form-option-selected.html');
        $aOptions = array();
        $iSelected = ceil($aConfig['weeks'] / 2);
        for ($iWeeks = 1; $iWeeks <= $aConfig['weeks']; ++$iWeeks) {
            $aOptions[] = str_replace(
                array('{VALUE}', '{SELECTED}', '{TEXT}'),
                array($iWeeks, $iWeeks == $iSelected ? ' selected="selected"' : null, $iWeeks),
                $sOption);
        }
        return str_replace('{OPTIONS}', implode("\n", $aOptions), file_get_contents('template/form-weeks.html'));
    }

    static function getFormWeek() {
        global $aConfig;
        $sOption = file_get_contents('template/form-option.html');
    
        /* From */
        $aNow = now();
        $aOptions = self::getWeekOptions($sOption, $aConfig['year'], $aNow, $aConfig['week'], 53);
        $sFrom = vsprintf('%d-%d', $aNow);
        $aOptgroups = array(str_replace(
            array('{LABEL}', '{OPTIONS}'), 
            array($aConfig['year'], implode("\n", $aOptions)),
            file_get_contents('template/form-optgroup.html')));

        /* To */
        $iWeeks = ceil($aConfig['weeks'] / 2);
        $aNow = array(
            $aNow[0] + ($aNow[1] + $iWeeks > 52 ? 1 : 0),
            ($aNow[1] + $iWeeks) % 52);
        $aOptions = self::getWeekOptions($sOption, $aConfig['year'] + 1, $aNow, 1, $aConfig['week']);
        $sTo = vsprintf('%d-%d', $aNow);

        $aOptgroups[] = str_replace(
            array('{LABEL}', '{OPTIONS}'), 
            array($aConfig['year'] + 1, implode("\n", $aOptions)),
            file_get_contents('template/form-optgroup.html'));
        $sOptions = implode("\n", $aOptgroups);

        return str_replace(
            array('{OPTIONS-FROM}', '{OPTIONS-TO}'),
            array(
                str_replace($sFrom, sprintf('%s" selected="selected', $sFrom), $sOptions),
                str_replace($sTo, sprintf('%s" selected="selected', $sTo), $sOptions)),
            file_get_contents('template/form-week.html'));        
    }

    static function getWeekOptions($sOption, $iYear, $aNow, $iFrom, $iTo) {
        $aOptions = array();
        for ($iWeek = $iFrom; $iWeek < $iTo; ++$iWeek) {
            $aOptions[] = str_replace(
                array('{VALUE}', '{TEXT}'),
                array($sValue = sprintf('%d-%d', $iYear, $iWeek), $iWeek),
                $sOption);
            if ($aNow[0] && $iWeek == $aNow[1]) {
                $sFrom = $sValue;
            }
        }
        return $aOptions;
    }

    static function getFormGroup() {
        global $aConfig;
        $aOptgroups = array();
        $sOption = file_get_contents('template/form-option.html');
        foreach ($aConfig['groups'] as $sCategory => $aGroups) {
            $aOptions = array();
            foreach ($aGroups[1] as $sValue => $sText) {
                $aOptions[] = str_replace(
                    array('{VALUE}', '{TEXT}'),
                    array($sValue, $sText),
                    $sOption);
            }
            $aOptgroups[] = str_replace(
                array('{LABEL}', '{OPTIONS}'), 
                array($aGroups[0], implode("\n", $aOptions)),
                file_get_contents('template/form-optgroup.html'));
        }
        return str_replace('{OPTIONS}', implode("\n", $aOptgroups), file_get_contents('template/form-group.html'));
    }

    static function getForm() {
        return str_replace(
            array('{WEEKS}', '{WEEK}', '{GROUP}'),
            array(self::getFormWeeks(), self::getFormWeek(), self::getFormGroup()),
            file_get_contents('template/form.html'));
    }
}