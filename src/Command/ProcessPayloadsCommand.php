<?php

declare(strict_types=1);

/*
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MothershipSimpleApi\Command;

use JsonException;
use MothershipSimpleApi\Content\Entity\SimpleApiPayloadEntity;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\ImportExport\Exception\ProcessingException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessPayloadsCommand extends Command
{
    protected static $defaultName = 'simple-api:process-payloads';

    public function __construct(
        protected SimpleProductCreator $simpleProductCreator,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected EntityRepository $simpleApiPayloadRepository
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Arbeitet die Payloads aus der Tabelle ms_simple_api_payload ab');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', 'new'));

        $payloads = $this->simpleApiPayloadRepository->search($criteria, Context::createDefaultContext());

        /* @var SimpleApiPayloadEntity $payload */
        foreach ($payloads as $payload) {
            try {
                $this->updateStatus($payload, 'processing');

                $this->simpleProductCreator->createEntity($payload->getPayload(), Context::createDefaultContext());


            } catch (\Exception $e) {
                $this->logError($payload, $e->getMessage());
                throw new ProcessingException($e->getMessage());
            }

            $this->updateStatus($payload, 'completed');

        }
        return 0;
    }

    protected function updateStatus(SimpleApiPayloadEntity $entity, string $status): void
    {
        $this->simpleApiPayloadRepository->update([[
            'id'     => $entity->getId(),
            'status' => $status
        ]], Context::createDefaultContext());
    }

    protected function logError(SimpleApiPayloadEntity $entity, string $error): void
    {
        $this->simpleApiPayloadRepository->update([[
            'id'     => $entity->getId(),
            'status' => 'error',
            'error'  => $error
        ]], Context::createDefaultContext());
    }
}
