<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\DcMultilingualUtilsBundle;

use HeimrichHannot\DcMultilingualUtilsBundle\DependencyInjection\DcMultilingualUtilsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoDcMultilingualUtilsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new DcMultilingualUtilsExtension();
    }
}
