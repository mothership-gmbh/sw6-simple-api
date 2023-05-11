<?php declare(strict_types=1);

namespace Shopware\Core;

require __DIR__ . '/../../../../../vendor/shopware/core/TestBootstrap.php';

// https://stackoverflow.com/questions/70169265/custom-service-not-found-by-unit-test-of-shopware-6-plugin
(new TestBootstrapper())
    # ->setForceInstallPlugins(true)
    # ->addActivePlugins('MothershipSimpleApi', 'SwagCustomizedProducts')
    ->bootstrap();
