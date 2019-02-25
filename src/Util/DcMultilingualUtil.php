<?php

/*
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle\Util;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\DataContainer;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DcMultilingualUtil implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    const ADJUST_TIME_SERVICES = [
        'tl_news' => 'huh.dc_multilingual_utils.data_container.news',
        'tl_calendar_events' => 'huh.dc_multilingual_utils.data_container.calendar_events',
    ];

    /**
     * Adds terminal42/dc_multilingual support for a given data container.
     *
     * @param string $table
     * @param array $languages
     * @param string $fallbackLanguage
     * @param array $fields The translatable fields in the form ['fieldname1', ...] or [['field' => 'fieldname1', 'translatableFor' => 'de'], ...]
     * @param array $options
     */
    public function addDcMultilingualSupport(
        string $table,
        array $languages,
        string $fallbackLanguage,
        array $fields = [],
        array $options = []
    ) {
        $this->container->get('huh.utils.dca')->loadDc($table);

        $dca                = &$GLOBALS['TL_DCA'][$table];
        $languageColumnName = $options['langColumnName'] ?? 'language';
        $langPid            = $options['langPid'] ?? 'langPid';

        $dca['config']['dataContainer'] = 'Multilingual';
        $dca['config']['languages']     = $languages;
        $dca['config']['fallbackLang']  = $fallbackLanguage;

        $dca['config']['langColumnName']                   = $languageColumnName;
        $dca['fields'][$languageColumnName]['sql']         = "varchar(2) NOT NULL default ''";
        $dca['config']['sql']['keys'][$languageColumnName] = 'index';

        $dca['config']['langPid']               = $langPid;
        $dca['config']['sql']['keys'][$langPid] = 'index';
        $dca['fields'][$langPid]['sql']         = "int(10) unsigned NOT NULL default '0'";

        foreach ($fields as $data) {
            if (\is_array($data)) {
                $field           = $data['field'];
                $translatableFor = $data['translatableFor'] ?? '*';
            } else {
                $field           = $data;
                $translatableFor = '*';
            }

            $dca['fields'][$field]['eval']['translatableFor'] = $translatableFor;
        }

        if (in_array($table, ['tl_news', 'tl_calendar_events']))
        {
            foreach ($dca['config']['onsubmit_callback'] as $key => $callback)
            {
                if (!is_array($callback))
                {
                    continue;
                }
                if (isset($callback[0], $callback[1]) && $callback[0] === $table && $callback[1] === 'adjustTime')
                {
                    unset($dca['config']['onsubmit_callback'][$key]);
                    $dca['config']['onsubmit_callback']['huh.dc_multilingual_utils.adjustTime'] = [static::ADJUST_TIME_SERVICES[$table] ,'adjustTime'];
                    break;
                }
            }
        }
    }

    public function addDcMultilingualTranslatableAliasEval(array &$fieldDca, string $translatableFor = '*', string $aliasField = 'title', bool $skipRemoveSaveCallbacks = false)
    {
        $fieldDca['eval']['translatableFor']        = $translatableFor;
        $fieldDca['eval']['isMultilingualAlias']    = true;
        $fieldDca['eval']['generateAliasFromField'] = $aliasField;

        if (!$skipRemoveSaveCallbacks)
        {
            unset($fieldDca['save_callback']);
        }
    }

    public function addPublishFieldsFor(string $table, array $options = [])
    {
        $this->container->get('huh.utils.dca')->loadDc($table);

        $dca = &$GLOBALS['TL_DCA'][$table];

        $publishedField = $options['langPublished'] ?? 'langPublished';
        $startField = $options['langPublished'] ?? 'langStart';
        $stopField = $options['langPublished'] ?? 'langStop';
        $skipStartStop = $options['skipStartStop'] ?? false;

        // add the fields for the install tool
        $dca['fields'] += $this->getPublishFields(false, $options);

        // config
        $dca['config']['langPublished'] = $publishedField;

        if (!$skipStartStop) {
            $dca['config']['langStart'] = $startField;
            $dca['config']['langStop']  = $stopField;
        }

        // add the callback
        if ($this->container->get('huh.utils.container')->isBackend() && ($id = \Input::get('id')))
        {
            $sessionKey = 'dc_multilingual:'.$table.':'.$id;

            /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSessionBag */
            $objSessionBag = $this->container->get('session')->getBag('contao_backend');

            $language = $objSessionBag->get($sessionKey);

            if (!$language)
            {
                return;
            }

            // subpalettes aren't working with dc_multilingual atm

            /**
             * Fields
             */
            foreach ($this->getPublishFields(true, $options) as $field => $data)
            {
                $dca['fields'][$field] = $data;
            }
        }
    }

    public function getPublishFields(bool $addInputTypes = false, array $options = [])
    {
        $publishedField = $options['langPublished'] ?? 'langPublished';
        $startField = $options['langPublished'] ?? 'langStart';
        $stopField = $options['langPublished'] ?? 'langStop';
        $skipStartStop = $options['skipStartStop'] ?? false;
        $translatableFor = $options['translatableFor'] ?? '*';

        if (!$addInputTypes)
        {
            $fields = [
                $publishedField   => [
                    'sql'       => "char(1) NOT NULL default ''"
                ]
            ];

            if (!$skipStartStop)
            {
                $fields += [
                    $startField       => [
                        'sql'       => "varchar(10) NOT NULL default ''"
                    ],
                    $stopField        => [
                        'sql'       => "varchar(10) NOT NULL default ''"
                    ],
                ];
            }

            return $fields;
        }
        else
        {
            $fields = [
                $publishedField   => [
                    'label'     => &$GLOBALS['TL_LANG']['MSC']['dcMultilingualUtils']['langPublished'],
                    'exclude'   => true,
                    'filter'    => true,
                    'inputType' => 'checkbox',
                    'eval'      => ['doNotCopy' => true, 'translatableFor' => $translatableFor, 'tl_class' => 'w50'],
                    'sql'       => "char(1) NOT NULL default ''"
                ]
            ];

            if (!$skipStartStop)
            {
                $fields += [
                    $startField       => [
                        'label'     => &$GLOBALS['TL_LANG']['MSC']['dcMultilingualUtils']['langStart'],
                        'exclude'   => true,
                        'inputType' => 'text',
                        'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr', 'translatableFor' => $translatableFor],
                        'sql'       => "varchar(10) NOT NULL default ''"
                    ],
                    $stopField        => [
                        'label'     => &$GLOBALS['TL_LANG']['MSC']['dcMultilingualUtils']['langStop'],
                        'exclude'   => true,
                        'inputType' => 'text',
                        'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50', 'translatableFor' => $translatableFor],
                        'sql'       => "varchar(10) NOT NULL default ''"
                    ],
                ];
            }

            return $fields;
        }
    }
}