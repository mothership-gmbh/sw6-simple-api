<?php declare(strict_types=1);

namespace Shopware\Core;

require __DIR__ . '/../vendor/shopware/core/TestBootstrapper.php';

(new TestBootstrapper())
    ->setPlatformEmbedded(false)
    ->addActivePlugins('MothershipSimpleApi')
    // Nur beim ersten Ausführen der Tests benötigt damit Plugin auch in Test-Instanz installiert wird.
    //->setForceInstallPlugins(true)
    ->bootstrap();
