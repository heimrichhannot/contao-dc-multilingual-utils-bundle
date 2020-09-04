<?php

/*
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle\EventListener\DataContainer;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Database;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContentListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function loadCurrentLanguageRecord($dc)
    {
        $sessionKey = 'dc_multilingual:'.$dc->table.':'.$dc->id;

        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSessionBag */
        $objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

        $language = $objSessionBag->get($sessionKey);

        if (null === $language || !in_array($language, $GLOBALS['TL_DCA'][$dc->table]['config']['languages'])) {
            return;
        }

        $options['language'] = $language;

        $model = \Contao\ContentModel::findByPk($dc->id, $options);

        if (null !== $model) {
            $dc->activeRecord = $model;
        }
    }

    public function setTranslateAbleCustomContentElementFields($dc)
    {
        if (!class_exists('MadeYourDay\RockSolidCustomElements\CustomElements')) {
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

    public function prepareRsceData(&$element, $return)
    {
        if (!class_exists('MadeYourDay\RockSolidCustomElements\CustomElements')) {
            return $return;
        }

        if ($GLOBALS['TL_DCA']['tl_content']['config']['fallbackLang'] === $GLOBALS['TL_LANGUAGE']) {
            return $return;
        }

        $langPid = $GLOBALS['TL_DCA']['tl_content']['config']['langPid'] ?? 'langPid';

        $rsceConfig = \MadeYourDay\RockSolidCustomElements\CustomElements::getConfigByType($element->type);

        if (!is_array($rsceConfig) || empty($rsceConfig)) {
            return $return;
        }

        $translatedElement = Database::getInstance()->prepare('SELECT rsce_data FROM tl_content WHERE tl_content.' . $langPid . '=? AND tl_content.language=?')->execute($element->id, $GLOBALS['TL_LANGUAGE']);

        if ($translatedElement->numRows < 1) {
            return $return;
        }

        $originalElement = Database::getInstance()->prepare('SELECT rsce_data FROM tl_content WHERE tl_content.id=?')->execute($element->id);

        if ($originalElement->numRows < 1) {
            return $return;
        }

        $originalElement->next();

        $rsceData = \json_decode($originalElement->rsce_data, true);
        $translatedRsceData = json_decode($element->rsce_data, true);

        foreach ($translatedRsceData as $field => $value) {
            $rsceData[$field] = $value;
        }

        $element->rsce_data = \json_encode($rsceData);

        return $return;
    }
}
