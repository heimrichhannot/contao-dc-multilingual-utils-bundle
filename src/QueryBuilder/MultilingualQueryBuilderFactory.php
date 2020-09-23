<?php

namespace HeimrichHannot\DcMultilingualUtilsBundle\QueryBuilder;

use Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderInterface;

class MultilingualQueryBuilderFactory extends \Terminal42\DcMultilingualBundle\QueryBuilder\MultilingualQueryBuilderFactory
{
    /**
     * Builds a MultilingualQueryBuilder.
     *
     * @param string $table
     * @param string $pidColumnName
     * @param string $langColumnName
     * @param array  $regularFields
     * @param array  $translatableFields
     *
     * @return MultilingualQueryBuilderInterface
     */
    public function build(
        $table,
        $pidColumnName,
        $langColumnName,
        array $regularFields,
        array $translatableFields
    ) {
        return new MultilingualQueryBuilder(
            $this->createQueryBuilder(),
            $table,
            $pidColumnName,
            $langColumnName,
            $regularFields,
            $translatableFields
        );
    }
}
