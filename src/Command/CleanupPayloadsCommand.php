<?php

declare(strict_types=1);

/*
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MothershipSimpleApi\Command;

use Faker\Provider\cs_CZ\DateTime;
use JsonException;
use MothershipSimpleApi\Content\Entity\SimpleApiPayloadEntity;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\ImportExport\Exception\ProcessingException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupPayloadsCommand extends Command
{
    protected static $defaultName = 'simple-api:cleanup-payloads';

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
        $this->setDescription('Prüft Payloads in der Tabelle ms_simple_api_payload und löscht diese,
        wenn sie älter als eine Woche sind.');
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_REQUIRED,
        'Payloads, älter als x Tage werden entfernt.',
            7
        );
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
        $dateTime = new \DateTime();
        $dateTime->modify('-1 ' . $input->getOption('days') . ' days');

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('createdAt', ['lt' => $dateTime->format('Y-m-d H:i:s')]));

        $payloads = $this->simpleApiPayloadRepository->search($criteria, Context::createDefaultContext());

        /* @var SimpleApiPayloadEntity $payload */
        foreach ($payloads as $payload) {
            $this->simpleApiPayloadRepository->delete([['id' => $payload->getId()]], Context::createDefaultContext());
        }
        return 0;
    }
}
