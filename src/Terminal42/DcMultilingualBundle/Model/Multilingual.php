<?php

/*
 * dc_multilingual Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2011-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-dc_multilingual
 */

namespace Terminal42\DcMultilingualBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderFactoryInterface;

class Multilingual extends \Model
{
    /**
     * Prevent the model from saving.
     *
     * @param \Database\Result $objResult An optional database result
     */
    public function __construct(\Database\Result $objResult = null)
    {
        parent::__construct($objResult);

        $this->preventSaving(false);
    }

    /**
     * Overridden in order to allow saving using clone
     *
     * @return $this|\Model
     */
    public function save()
    {
        // Deprecated call
        if (\count(\func_get_args())) {
            throw new \InvalidArgumentException('The $blnForceInsert argument has been removed (see system/docs/UPGRADE.md)');
        }

        // Fix: allow saving
//        if ($this->blnPreventSaving)
//        {
//            throw new \RuntimeException('The model instance has been detached and cannot be saved');
//        }

        $objDatabase = \Database::getInstance();
        $arrFields   = $objDatabase->getFieldNames(static::$strTable);

        // Fix: check if record already exists in db
        if ($this->{static::$strPk}) {
            $objExistingRecord = $objDatabase->prepare("SELECT * FROM ".static::$strTable." WHERE ".\Database::quoteIdentifier(static::$strPk)."=?")->execute($this->{static::$strPk});
        }

        // The model is in the registry (Fix: also check for db since model instances aren't saved to the registry)
        if (\Model\Registry::getInstance()->isRegistered($this) || $objExistingRecord && $objExistingRecord->numRows > 0) {
            $arrSet = [];
            $arrRow = $this->row();

            // Only update modified fields
            foreach ($this->arrModified as $k => $v) {
                // Only set fields that exist in the DB
                if (\in_array($k, $arrFields)) {
                    $arrSet[$k] = $arrRow[$k];
                }
            }

            $arrSet = $this->preSave($arrSet);

            // No modified fiels
            if (empty($arrSet)) {
                return $this;
            }

            $intPk = $this->{static::$strPk};

            // Track primary key changes
            if (isset($this->arrModified[static::$strPk])) {
                $intPk = $this->arrModified[static::$strPk];
            }

            if ($intPk === null) {
                throw new \RuntimeException('The primary key has not been set');
            }

            // Update the row
            $objDatabase->prepare("UPDATE ".static::$strTable." %s WHERE ".\Database::quoteIdentifier(static::$strPk)."=?")->set($arrSet)->execute($intPk);

            $this->postSave(self::UPDATE);
            $this->arrModified = []; // reset after postSave()
        } // The model is not yet in the registry
        else {
            $arrSet = $this->row();

            // Remove fields that do not exist in the DB
            foreach ($arrSet as $k => $v) {
                if (!\in_array($k, $arrFields)) {
                    unset($arrSet[$k]);
                }
            }

            $arrSet = $this->preSave($arrSet);

            // No modified fiels
            if (empty($arrSet)) {
                return $this;
            }

            // Insert a new row
            $stmt = $objDatabase->prepare("INSERT INTO ".static::$strTable." %s")->set($arrSet)->execute();

            if (static::$strPk == 'id') {
                $this->id = $stmt->insertId;
            }

            $this->postSave(self::INSERT);
            $this->arrModified = []; // reset after postSave()

            // Fix: Skip storing to registry as this would break model retrieval using DC_Multilingual joins
//            \Model\Registry::getInstance()->register($this);
        }

        return $this;
    }

    /**
     * Returns the ID of the fallback language.
     */
    public function getLanguageId()
    {
        $pidColumn = static::getPidColumn();

        if ($this->{$pidColumn} > 0) {
            return $this->{$pidColumn};
        }

        return $this->id;
    }

    /**
     * Get the alias of a multilingual model.
     *
     * @param string $language
     * @param string $aliasColumnName
     *
     * @return mixed
     */
    public function getAlias($language, $aliasColumnName = 'alias')
    {
        // Do not load any translation if already target language
        $langColumn = static::getLangColumn();

        if ($language === $this->{$langColumn}) {
            return $this->{$aliasColumnName};
        }

        // Try to load the translated model
        $translatedModel = static::findByPk($this->id, ['language' => $language]);

        if (null === $translatedModel) {
            // Get fallback
            $fallbackLang = static::getFallbackLanguage();

            if ($language === $fallbackLang) {
                return $this->{$aliasColumnName};
            }

            $fallbackModel = static::findByPk($this->id, ['language' => $fallbackLang]);

            return $fallbackModel->{$aliasColumnName};
        }

        return $translatedModel->{$aliasColumnName};
    }

    /**
     * Find a model by its alias.
     *
     * @param        $alias
     * @param string $aliasColumnName
     * @param array  $options
     *
     * @return mixed
     */
    public static function findByAlias($alias, $aliasColumnName = 'alias', $options = [])
    {
        $options = array_merge(
            [
                'limit'  => 1,
                'column' => ["t1.$aliasColumnName=?"],
                'value'  => [$alias],
                'return' => 'Model',
            ],
            $options
        );

        return static::find($options);
    }

    /**
     * Find a model by its alias when using multilingal aliases.
     *
     * @param        $alias
     * @param string $aliasColumnName
     * @param array  $options
     *
     * @return mixed
     */
    public static function findByMultilingualAlias($alias, $aliasColumnName = 'alias', $options = [])
    {
        $options = array_merge(
            [
                'limit'  => 1,
                'column' => ["(t1.$aliasColumnName=? OR t2.$aliasColumnName=?)"],
                'value'  => [$alias, $alias],
                'return' => 'Model',
            ],
            $options
        );

        return static::find($options);
    }

    /**
     * Get the language column.
     *
     * @return string
     */
    public static function getLangColumn()
    {
        static::ensureDataContainerIsLoaded();

        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['langColumn']) {
            return $GLOBALS['TL_DCA'][static::getTable()]['config']['langColumn'];
        }

        return 'language';
    }

    /**
     * Get the fallback language if available.
     *
     * @return string|null
     */
    public static function getFallbackLanguage()
    {
        static::ensureDataContainerIsLoaded();

        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['fallbackLang']) {
            return $GLOBALS['TL_DCA'][static::getTable()]['config']['fallbackLang'];
        }

        return null;
    }

    /**
     * Build a query based on the given options.
     * The method returns a QueryBuilder instance so you can easily modify
     * the query in your child class. We can just return the instance as the
     * QueryBuilder implements the __toString() method so we don't have to call
     * ->getSql() manually.
     *
     * @param array $options The options array
     *
     * @return QueryBuilder
     */
    protected static function buildFindQuery(array $options)
    {
        $mlqb = static::getMultilingualQueryBuilder();

        // Use the current language if none provided
        if (!isset($options['language'])) {
            $options['language'] = str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
        }

        // Consider the fallback language
        $fallbackLang = static::getFallbackLanguage();
        if (null !== $fallbackLang && $fallbackLang === $options['language']) {
            $options['language'] = '';
        }

        $mlqb->buildQueryBuilderForFind($options['language']);

        static::applyOptionsToQueryBuilder($mlqb->getQueryBuilder(), $options);

        return $mlqb->getQueryBuilder();
    }

    /**
     * Build a query based on the given options to count the number of records.
     * The method returns a QueryBuilder instance so you can easily modify
     * the query in your child class. We can just return the instance as the
     * QueryBuilder implements the __toString() method so we don't have to call
     * ->getSql() manually.
     *
     * @param array $options The options array
     *
     * @return QueryBuilder
     */
    protected static function buildCountQuery(array $options)
    {
        $mlqb = static::getMultilingualQueryBuilder();

        $mlqb->buildQueryBuilderForCount();

        static::applyOptionsToQueryBuilder($mlqb->getQueryBuilder(), $options);

        return $mlqb->getQueryBuilder();
    }

    /**
     * Apply the model options to the query builder.
     *
     * @param QueryBuilder $qb
     * @param array        $options
     */
    protected static function applyOptionsToQueryBuilder(QueryBuilder $qb, array $options)
    {
        $from = $qb->getQueryPart('from')[0];

        // Columns
        if (null !== $options['column']) {
            if (is_array($options['column'])) {
                foreach ($options['column'] as $column) {
                    $qb->andWhere(static::replaceTableNameWithAlias($column, $from));
                }
            } else {
                // Default is likely t1
                $qb->andWhere('t1.'.$options['column'].'=?');
            }
        }

        // Group by
        if (null !== $options['group']) {
            $qb->groupBy(static::replaceTableNameWithAlias($options['group'], $from));
        }

        // Having
        if (null !== $options['having']) {
            $qb->having(static::replaceTableNameWithAlias($options['having'], $from));
        }

        // Order by
        if (null !== $options['order']) {
            $qb->add('orderBy', static::replaceTableNameWithAlias($options['order'], $from));
        }
    }

    /**
     * Get the MultilingualQueryBuilder.
     *
     * @return \Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderInterface
     */
    protected static function getMultilingualQueryBuilder()
    {
        /** @var MultilingualQueryBuilderFactoryInterface $factory */
        $factory = \System::getContainer()->get('terminal42.dc_multilingual.querybuilder_factory');

        return $factory->build(
            static::getTable(),
            static::getPidColumn(),
            static::getLangColumn(),
            static::getRegularFields(),
            static::getTranslatableFields()
        );
    }

    /**
     * Get the regular fields.
     *
     * @return array
     */
    protected static function getRegularFields()
    {
        $sqlFields = \Database::getInstance()->getFieldNames(static::getTable());

        $extractor = \DcaExtractor::getInstance(static::getTable());

        return array_intersect($sqlFields, array_keys($extractor->getFields()));
    }

    /**
     * Get the fields that are translatable.
     *
     * @return array
     */
    protected static function getTranslatableFields()
    {
        $sqlFields = \Database::getInstance()->getFieldNames(static::getTable());

        static::ensureDataContainerIsLoaded();

        $fields = [];

        foreach ($GLOBALS['TL_DCA'][static::getTable()]['fields'] as $field => $data) {
            if (!isset($data['eval']['translatableFor']) || !in_array($field, $sqlFields)) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Get the PID column.
     *
     * @return string
     */
    protected static function getPidColumn()
    {
        static::ensureDataContainerIsLoaded();

        if ($GLOBALS['TL_DCA'][static::getTable()]['config']['langPid']) {
            return $GLOBALS['TL_DCA'][static::getTable()]['config']['langPid'];
        }

        return 'langPid';
    }

    /**
     * Ensure the data container is loaded.
     */
    protected static function ensureDataContainerIsLoaded()
    {
        if (!isset($GLOBALS['TL_DCA'][static::getTable()])) {
            $loader = new \DcaLoader(static::getTable());
            $loader->load();
        }
    }


    protected static function replaceTableNameWithAlias($column, array $from)
    {
        $table = $from['table'];
        $alias = $from['alias'];

        return preg_replace("/$table./", "$alias.", $column);
    }
}
