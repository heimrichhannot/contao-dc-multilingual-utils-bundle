<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @author  Thomas Körner <t.koerner@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


namespace HeimrichHannot\DcMultilingualUtilsBundle\DataContainer;


use Contao\CalendarEventsModel;
use Contao\Database;
use Contao\StringUtil;
use DC_Multilingual;

class CalendarEventsContainer
{
    /**
     * Adjust start end end time of the event based on date, span, startTime and endTime
     *
     * @param DC_Multilingual $dc
     */
    public function adjustTime(DC_Multilingual $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $parentRecord = $dc->activeRecord;
        if ($dc->getCurrentLanguage() !== '' && $dc->getCurrentLanguage() !== $dc->getFallbackLanguage())
        {
            $parentRecord = CalendarEventsModel::findByPk($parentRecord->{$dc->getPidColumn()});
        }

        $arrSet['startTime'] = $parentRecord->startDate;
        $arrSet['endTime'] = $parentRecord->startDate;

        // Set end date
        if ($parentRecord->endDate)
        {
            if ($parentRecord->endDate > $parentRecord->startDate)
            {
                $arrSet['endDate'] = $parentRecord->endDate;
                $arrSet['endTime'] = $parentRecord->endDate;
            }
            else
            {
                $arrSet['endDate'] = $parentRecord->startDate;
                $arrSet['endTime'] = $parentRecord->startDate;
            }
        }

        // Add time
        if ($parentRecord->addTime)
        {
            $arrSet['startTime'] = strtotime(date('Y-m-d', $arrSet['startTime']) . ' ' . date('H:i:s', $parentRecord->startTime));
            $arrSet['endTime'] = strtotime(date('Y-m-d', $arrSet['endTime']) . ' ' . date('H:i:s', $parentRecord->endTime));
        }

        // Adjust end time of "all day" events
        elseif (($parentRecord->endDate && $arrSet['endDate'] == $arrSet['endTime']) || $arrSet['startTime'] == $arrSet['endTime'])
        {
            $arrSet['endTime'] = (strtotime('+ 1 day', $arrSet['endTime']) - 1);
        }

        $arrSet['repeatEnd'] = 0;

        // Recurring events
        if ($parentRecord->recurring)
        {
            // Unlimited recurrences end on 2038-01-01 00:00:00 (see #4862)
            if ($parentRecord->recurrences == 0)
            {
                $arrSet['repeatEnd'] = 2145913200;
            }
            else
            {
                $arrRange = StringUtil::deserialize($parentRecord->repeatEach);

                if (\is_array($arrRange) && isset($arrRange['unit']) && isset($arrRange['value']))
                {
                    $arg = $arrRange['value'] * $parentRecord->recurrences;
                    $unit = $arrRange['unit'];

                    $strtotime = '+ ' . $arg . ' ' . $unit;
                    $arrSet['repeatEnd'] = strtotime($strtotime, $arrSet['endTime']);
                }
            }
        }

        Database::getInstance()->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }
}