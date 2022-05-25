<?php

namespace HeimrichHannot\DcMultilingualUtilsBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Database;
use Contao\Model;
use MadeYourDay\RockSolidCustomElements\CustomElements;

/**
 * @Hook("isVisibleElement")
 */
class IsVisibleElementListener
{
    public function __invoke(Model $element, bool $isVisible): bool
    {
        if (!class_exists(CustomElements::class)) {
            return $isVisible;
        }

        if ($GLOBALS['TL_DCA']['tl_content']['config']['fallbackLang'] === $GLOBALS['TL_LANGUAGE']) {
            return $isVisible;
        }

        $langPid = $GLOBALS['TL_DCA']['tl_content']['config']['langPid'] ?? 'langPid';

        $rsceConfig = CustomElements::getConfigByType($element->type);

        if (!is_array($rsceConfig) || empty($rsceConfig)) {
            return $isVisible;
        }

        $translatedElement = Database::getInstance()
            ->prepare('SELECT rsce_data FROM tl_content WHERE tl_content.' . $langPid . '=? AND tl_content.language=?')
            ->execute($element->id, $GLOBALS['TL_LANGUAGE']);

        if ($translatedElement->numRows < 1) {
            return $isVisible;
        }

        $originalElement = Database::getInstance()
            ->prepare('SELECT rsce_data FROM tl_content WHERE tl_content.id=?')
            ->execute($element->id);

        if ($originalElement->numRows < 1) {
            return $isVisible;
        }

        $originalElement->next();

        $rsceData = \json_decode($originalElement->rsce_data, true);
        $translatedRsceData = \json_decode($element->rsce_data, true);

        foreach ($translatedRsceData as $field => $value) {
            $rsceData[$field] = $value;
        }

        $element->rsce_data = \json_encode($rsceData);

        return $isVisible;
    }
}