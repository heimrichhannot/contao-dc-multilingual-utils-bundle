<?php

$GLOBALS['TL_HOOKS']['isVisibleElement']['prepareRsceData'] = ['huh.dc_multilingual_utils.event_listener.data_container.content_listener', 'prepareRsceData'];
$GLOBALS['TL_HOOKS']['replaceInsertTags']['dcMultilingualUtils'] = [\HeimrichHannot\DcMultilingualUtilsBundle\EventListener\InsertTagListener::class, '__invoke'];
