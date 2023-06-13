<?php

declare(strict_types=1);

namespace MothershipSimpleApi\MessageQueue\Message;

/**
 * @deprecated tag:v6.5.0 - Wird durch ein eigenes Interface erweitert.
 * @link https://developer.shopware.com/docs/guides/plugins/plugins/framework/message-queue/add-message-to-queue
 */
class SimpleApiPayloadNotification
{
    private array $payload;

    private string $id;

    public function __construct(array $payload, string $id)
    {
        $this->payload = $payload;
        $this->id = $id;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getId(): string
    {
        return $this->id;
    }
}