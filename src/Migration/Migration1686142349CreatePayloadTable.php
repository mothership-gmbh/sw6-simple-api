<?php declare(strict_types=1);

namespace MothershipSimpleApi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1686142349CreatePayloadTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1686142349;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `ms_simple_api_payload` (
            `id` BINARY(16) NOT NULL,
            `payload` JSON NOT NULL,
            `status` VARCHAR(255) NOT NULL,
            `error` VARCHAR(255) NULL,
            `source` VARCHAR(255) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `json.simple_api.payload` CHECK (JSON_VALID(`payload`))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
