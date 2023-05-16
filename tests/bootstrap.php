<?php declare(strict_types=1);

namespace Shopware\Core;

require __DIR__ . '/../vendor/shopware/core/TestBootstrapper.php';

(new TestBootstrapper())
    ->setPlatformEmbedded(false)
    ->addActivePlugins('MothershipSimpleApi')
    ->bootstrap();
