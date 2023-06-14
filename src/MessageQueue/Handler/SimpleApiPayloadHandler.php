<?php

declare(strict_types=1);

namespace MothershipSimpleApi\MessageQueue\Handler;

use MothershipSimpleApi\MessageQueue\Message\SimpleApiPayloadNotification;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\ImportExport\Exception\ProcessingException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

/**
 * Verarbeitet alle SimpleApiPayloadNotification Nachrichten.
 */
class SimpleApiPayloadHandler extends AbstractMessageHandler
{

    /**
     * Implementierung der Context-Factory angelehnt an die Klasse ProductExportPartialGenerationHandler.
     *
     * @param SimpleProductCreator               $simpleProductCreator
     * @param AbstractSalesChannelContextFactory $salesChannelContextFactory
     * @param EntityRepository                   $simpleApiPayloadRepository
     *
     * @link \Shopware\Core\Content\ProductExport\ScheduledTask\ProductExportPartialGenerationHandler
     */
    public function __construct(
        protected SimpleProductCreator $simpleProductCreator,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected EntityRepository $simpleApiPayloadRepository
    )
    {
    }

    public function handle($message): void
    {
        try {
            $payload = $message->getPayload();
            $this->updateStatus($message, 'processing');

            $this->simpleProductCreator->createEntity($payload, Context::createDefaultContext());


        } catch (\Exception $e) {
            $this->logError($message, $e->getMessage());
            throw new ProcessingException($e->getMessage());
        }

        $this->updateStatus($message, 'completed');
    }

    protected function updateStatus(SimpleApiPayloadNotification $message, string $status): void
    {
        $this->simpleApiPayloadRepository->update([[
            'id'   => $message->getId(),
            'status' => $status
        ]], Context::createDefaultContext());
    }

    protected function logError(SimpleApiPayloadNotification $message, string $error): void
    {
        $this->simpleApiPayloadRepository->update([[
            'id'     => $message->getId(),
            'status' => 'error',
            'error'  => $error
        ]], Context::createDefaultContext());
    }

    public static function getHandledMessages(): iterable
    {
        return [SimpleApiPayloadNotification::class];
    }
}