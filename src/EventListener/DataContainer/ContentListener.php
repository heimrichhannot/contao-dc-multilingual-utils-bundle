<?php

/*
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle\EventListener\DataContainer;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
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
