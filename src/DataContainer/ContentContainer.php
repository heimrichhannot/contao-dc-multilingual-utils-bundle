<?php

namespace HeimrichHannot\DcMultilingualUtilsBundle\DataContainer;

use Contao\ContentModel;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use MadeYourDay\RockSolidCustomElements\CustomElements;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ContentContainer
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Callback(table="tl_content", target="config.onload", priority=101)
     */
    public function loadCurrentLanguageRecord(DataContainer $dc = null): void
    {
        if (!$dc || !$dc->id || !$dc->table) {
            return;
        }

        $sessionKey = 'dc_multilingual:'.$dc->table.':'.$dc->id;

        $sessionBag = $this->session->getBag('contao_backend');

        $language = $sessionBag->get($sessionKey);

        if (null === $language || !in_array($language, $GLOBALS['TL_DCA'][$dc->table]['config']['languages'])) {
            return;
        }

        $options['language'] = $language;

        $model = ContentModel::findByPk($dc->id, $options);

        if (null !== $model) {
            $dc->activeRecord = $model;
        }
    }

    /**
     * @Callback(table="tl_content", target="config.onload")
     */
    public function setTranslateAbleCustomContentElementFields($dc)
    {
        if (!class_exists(CustomElements::class)) {
            return;
        }

        $allowedFields     = [];
        $allowedInputTypes = ['rsce_list_start', 'rsce_list_stop', 'rsce_list_item_start', 'rsce_list_item_stop', 'rsce_group_start', 'rsce_list_hidden'];

        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields']['rsce_data']['eval']['translatableFor'])) {
            return;
        }

        $translatableFor = $GLOBALS['TL_DCA'][$dc->table]['fields']['rsce_data']['eval']['translatableFor'];

        foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'] as $field => &$data) {

            if ((isset($data['inputType']) && in_array($data['inputType'], $allowedInputTypes)) || in_array($field, $allowedFields)) {
                $data['eval']['translatableFor'] = $translatableFor;
            }
        }
    }
}