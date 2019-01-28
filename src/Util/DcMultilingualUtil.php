<?php

/*
 * Copyright (c) 2019 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle\Util;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DcMultilingualUtil implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

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
}