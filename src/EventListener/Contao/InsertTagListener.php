<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle\EventListener\Contao;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;
use HeimrichHannot\UtilsBundle\Util\Utils;

/**
 * @Hook("replaceInsertTags")
 */
class InsertTagListener
{
    /**
     * @var DcaUtil
     */
    private $dcaUtil;
    /**
     * @var DatabaseUtil
     */
    private $databaseUtil;
    private InsertTagParser $insertTagParser;
    private Utils           $utils;

    public function __construct(
        DcaUtil $dcaUtil,
        DatabaseUtil $databaseUtil,
        InsertTagParser $insertTagParser,
        Utils $utils
    ) {
        $this->dcaUtil = $dcaUtil;
        $this->databaseUtil = $databaseUtil;
        $this->insertTagParser = $insertTagParser;
        $this->utils = $utils;
    }

    /**
     * Multilingual insert tags (e.g. {{dcmu_event_url::1::de}}).
     *
     * @param $tag
     *
     * @return bool|string
     */
    public function __invoke($tag)
    {
        $tagData = explode('::', $tag);

        if (0 !== strpos($tagData[0], 'dcmu')) {
            return false;
        }

        switch ($tagData[0]) {
            case 'dcmu_event_url':
            case 'dcmu_news_url':
            case 'dcmu_faq_url':
                if (!isset($tagData[1]) || !isset($tagData[2])) {
                    return false;
                }

                $type = explode('_', $tagData[0])[1];

                if ('event' === $type) {
                    $table = 'tl_calendar_events';
                } else {
                    $table = 'tl_'.$type;
                }

                $entity = $tagData[1];
                $language = $tagData[2];

                if (null === ($entityObj = $this->utils->model()->findModelInstanceByPk($table, $entity))) {
                    return false;
                }

                if (!$this->dcaUtil->isDcMultilingual($table)) {
                    return $this->insertTagParser->replace('{{'.$type.'_url::'.$entityObj->id.'}}');
                }

                $dca = $GLOBALS['TL_DCA'][$table];

                $ptable = $dca['config']['ptable'];

                if (null === ($archive = $this->utils->model()->findOneModelInstanceBy($ptable, [$ptable.'.id=?'], [$entityObj->pid]))) {
                    return false;
                }

                $url = $this->insertTagParser->replace('{{changelanguage_link_url::'.$archive->jumpTo.'::'.$language.'}}');

                // alias
                if (isset($dca['fields']['alias']['eval']['translatableFor']) && (
                    '*' === $dca['fields']['alias']['eval']['translatableFor'] ||
                    \in_array($language, explode(',', $dca['fields']['alias']['eval']['translatableFor'])))
                ) {
                    $langName = $dca['config']['langColumnName'];
                    $langPidName = $dca['config']['langPid'];

                    $translationRecord = $this->databaseUtil->findOneResultBy($table, [
                        "$table.$langPidName=?",
                        "$table.$langName=?",
                    ], [
                        $entity,
                        $language,
                    ]);

                    $alias = $entityObj->alias;

                    if ($translationRecord->numRows > 0) {
                        $alias = $translationRecord->alias ?: $alias;
                    }

                    $url .= '/'.$alias;
                } else {
                    $url .= '/'.$entityObj->alias;
                }

                return $url;
        }

        return false;
    }
}
