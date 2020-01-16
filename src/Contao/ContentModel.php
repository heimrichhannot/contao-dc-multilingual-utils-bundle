<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Terminal42\DcMultilingualBundle\Model\Multilingual;

class ContentModel extends Multilingual
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_content';

    /**
     * Find all published content elements by their parent ID and parent table
     *
     * @param integer $intPid The article ID
     * @param string $strParentTable The parent table name
     * @param array $arrOptions An optional options array
     *
     * @return Model\Collection|ContentModel[]|ContentModel|null A collection of models or null if there are no content elements
     */
    public static function findPublishedByPidAndTable($intPid, $strParentTable, array $arrOptions = [])
    {
        $t = static::$strTable;

        // Also handle empty ptable fields
        if ($strParentTable == 'tl_article') {
            $arrColumns = ["$t.pid=? AND ($t.ptable=? OR $t.ptable='')"];
        } else {
            $arrColumns = ["$t.pid=? AND $t.ptable=?"];
        }

        if (!static::isPreviewMode($arrOptions)) {
            $time         = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.invisible=''";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.sorting";
        }

        return static::findBy($arrColumns, [$intPid, $strParentTable], $arrOptions);
    }

    /**
     * Find all published content elements by their parent ID and parent table
     *
     * @param integer $intPid The article ID
     * @param string $strParentTable The parent table name
     * @param array $arrOptions An optional options array
     *
     * @return integer The number of matching rows
     */
    public static function countPublishedByPidAndTable($intPid, $strParentTable, array $arrOptions = [])
    {
        $t = static::$strTable;

        // Also handle empty ptable fields (backwards compatibility)
        if ($strParentTable == 'tl_article') {
            $arrColumns = ["$t.pid=? AND ($t.ptable=? OR $t.ptable='')"];
        } else {
            $arrColumns = ["$t.pid=? AND $t.ptable=?"];
        }

        if (!static::isPreviewMode($arrOptions)) {
            $time         = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.invisible=''";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.sorting";
        }

        return static::countBy($arrColumns, [$intPid, $strParentTable], $arrOptions);
    }
}
