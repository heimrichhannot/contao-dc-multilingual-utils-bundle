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


use Contao\Database;
use Contao\NewsModel;
use DC_Multilingual;

class NewsContainer
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
            $parentRecord = NewsModel::findByPk($dc->activeRecord->{$dc->getPidColumn()});
        }

        $arrSet['date'] = strtotime(date('Y-m-d', $parentRecord->date) . ' ' . date('H:i:s', $parentRecord->time));
        $arrSet['time'] = $arrSet['date'];

        Database::getInstance()->prepare("UPDATE tl_news %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }
}