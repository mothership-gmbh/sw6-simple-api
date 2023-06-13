<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service;


use MothershipSimpleApi\MessageQueue\Message\SimpleApiPayloadNotification;
use Symfony\Component\Messenger\MessageBusInterface;

class SimpleProductSender
{
    public function __construct(protected MessageBusInterface $messageBus) {}

    public function sendMessage(array $payload, string $simpleApiPayloadId): void
    {
        $this->messageBus->dispatch(new SimpleApiPayloadNotification($payload, $simpleApiPayloadId));
    }
}
