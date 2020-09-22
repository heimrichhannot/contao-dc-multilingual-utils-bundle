<?php

namespace HeimrichHannot\DcMultilingualUtilsBundle\EventListener;

use HeimrichHannot\DcMultilingualUtilsBundle\Util\DcMultilingualUtil;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Page\PageUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;

class InsertTagListener
{
    /**
     * @var DcMultilingualUtil
     */
    private $dcMultilingualUtil;
    /**
     * @var ModelUtil
     */
    private $modelUtil;
    /**
     * @var StringUtil
     */
    private $stringUtil;
    /**
     * @var PageUtil
     */
    private $pageUtil;
    /**
     * @var DcaUtil
     */
    private $dcaUtil;
    /**
     * @var DatabaseUtil
     */
    private $databaseUtil;

    public function __construct(
        DcMultilingualUtil $dcMultilingualUtil,
        ModelUtil $modelUtil,
        StringUtil $stringUtil,
        PageUtil $pageUtil,
        DcaUtil $dcaUtil,
        DatabaseUtil $databaseUtil
    ) {
        $this->dcMultilingualUtil = $dcMultilingualUtil;
        $this->modelUtil          = $modelUtil;
        $this->stringUtil         = $stringUtil;
        $this->pageUtil           = $pageUtil;
        $this->dcaUtil            = $dcaUtil;
        $this->databaseUtil       = $databaseUtil;
    }

    /**
     * Multilingual insert tags (e.g. {{dcmu_event_url::1::de}})
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
            case 'dcmu_event_url';
            case 'dcmu_news_url':
            case 'dcmu_faq_url':
                if (!isset($tagData[1]) || !isset($tagData[2])) {
                    return false;
                }

                $type = explode('_', $tagData[0])[1];

                if ($type === 'event') {
                    $table = 'tl_calendar_events';
                } else {
                    $table = 'tl_' . $type;
                }

                $entity    = $tagData[1];
                $language = $tagData[2];

                if (null === ($entityObj = $this->modelUtil->findModelInstanceByPk($table, $entity))) {
                    return false;
                }

                if (!$this->dcaUtil->isDcMultilingual($table)) {
                    return $this->stringUtil->replaceInsertTags('{{' . $type . '_url::' . $entityObj->id . '}}');
                }

                $dca = $GLOBALS['TL_DCA'][$table];

                $ptable = $dca['config']['ptable'];

                if (null === ($archive = $this->modelUtil->findOneModelInstanceBy($ptable, [$ptable . '.id=?'], [$entityObj->pid]))) {
                    return false;
                }

                $url = $this->stringUtil->replaceInsertTags('{{changelanguage_link_url::' . $archive->jumpTo . '::' . $language . '}}');

                // alias
                if ($dca['fields']['alias']['eval']['isMultilingualAlias'] ?? false) {
                    $langName = $dca['config']['langColumnName'];
                    $langPidName = $dca['config']['langPid'];


                    $translationRecord = $this->databaseUtil->findOneResultBy($table, [
                        "$table.$langPidName=?",
                        "$table.$langName=?",
                    ], [
                        $entity,
                        $language
                    ]);

                    $alias = $entityObj->alias;

                    if ($translationRecord->numRows > 0) {
                        $alias = $translationRecord->alias ?: $alias;
                    }

                    $url .= '/' . $alias;
                } else {
                    $url .= '/' . $entityObj->alias;
                }

                return $url;
        }

        return false;
    }


}
