<?php

$dca = &$GLOBALS['TL_DCA']['tl_content'];

// set current localized record before custom content CustomContentElements::getDcaFieldValue runs
array_insert($dca['config']['onload_callback'], 0,
    [['huh.dc_multilingual_utils.event_listener.data_container.content_listener', 'loadCurrentLanguageRecord']]);

// make custom content element pseudo widgets translatable in order to render them inside DC_Multilingual
$dca['config']['onload_callback'][] = [
    'huh.dc_multilingual_utils.event_listener.data_container.content_listener',
    'setTranslateAbleCustomContentElementFields'
];