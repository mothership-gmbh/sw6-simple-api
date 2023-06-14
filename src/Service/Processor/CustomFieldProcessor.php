<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class CustomFieldProcessor
{
    public function __construct(
        protected readonly EntityRepositoryInterface $customFieldRepository,
        protected readonly EntityRepositoryInterface $customFieldSetRepository,
        protected readonly EntityRepositoryInterface $customFieldSetRelationRepository,
    ) {}

    public function process(array &$data, Product $request, Context $context): void
    {
        $customFields = $request->getCustomFields();
        foreach ($customFields as $customFieldCode => $customFieldOptions) {

            $customFieldId = $this->generateCustomFieldId($customFieldCode);
            $customField = $this->getExistingData($customFieldId, $customFieldCode, $context);
            if (null === $customField) {
                $this->createCustomField($customFieldId, $customFieldCode, $customFieldOptions, $context);
            }

            foreach ($customFieldOptions['values'] as $isoCode => $value) {
                $data['translations'][$isoCode]['customFields'][$customFieldCode] = $value;
            }
        }
    }

    /**
     * Prüft, ob ein customField bereits in Shopware existiert.
     * Zunächst anhand der erwarteten nachvollziehbaren UUID.
     * Bestehende customFields wurden jedoch mit randomisierten, nicht nachvollziehbaren, UUIDs angelegt.
     * Daher prüfen wir auch anhand des customField-Codes.
     */
    protected function getExistingData(string $customFieldId, string $customFieldCode, Context $context): CustomFieldEntity|null
    {
        $customField = $this->getCustomFieldById($customFieldId, $context);
        if (null === $customField) {
            $customField = $this->getCustomFieldByCode($customFieldCode, $context);
        }

        return $customField;
    }

    private function generateCustomFieldId(string $customFieldCode): string
    {
        return Uuid::fromStringToHex($customFieldCode);
    }

    protected function getCustomFieldById(string $customFieldId, Context $context): CustomFieldEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($customFieldId)]);
        return $this->customFieldRepository->search($criteria, $context)->first();
    }

    protected function getCustomFieldByCode(string $customFieldCode, Context $context): CustomFieldEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldCode));
        return $this->customFieldRepository->search($criteria, $context)->first();
    }

    protected function getCustomFieldSetById(string $customFieldSetId, Context $context): CustomFieldSetEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($customFieldSetId)]);
        return $this->customFieldSetRepository->search($criteria, $context)->first();
    }

    protected function getCustomFieldSetByName(string $customFieldSetName, Context $context): CustomFieldSetEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldSetName));
        return $this->customFieldSetRepository->search($criteria, $context)->first();
    }

    /**
     * In Shopware werden CustomFields über ein CustomFieldSet mit einem Produkt verbunden.
     * Daher müssen wir zunächst ein customFieldSet erstellen das mit der Produkt-Entität verbunden ist.
     * Im weiteren Verlauf können wir dann die neuen customFields dem customFieldSet hinzufügen.
     * Erst dann sind sie auch mit der Produkt-Entität verknüpft.
     *
     * @param string  $customFieldId
     * @param string  $name
     * @param array   $customFieldData
     * @param Context $context
     *
     * @return void
     */
    private function createCustomField(string $customFieldId, string $name, array $customFieldData, Context $context): void
    {
        $customFieldSetId = $this->getCustomFieldSetId($customFieldData, $context);

        $payload = $this->constructPayload($customFieldData, $name);
        $payload['id'] = $customFieldId;
        $payload['name'] = $name;
        $payload['translations'] = [
            $context->getLanguageId() => [
                'name'         => $name,
                'customFields' => ['code' => $name],
            ],
        ];
        $payload['customFieldSetId'] = $customFieldSetId;

        $this->customFieldRepository->create([$payload], $context);
    }

    /**
     * Generiert eine nachvollziehbare UUID für das CustomFieldSet.
     * Dann wird geprüft, ob es bereits ein CustomFieldSet mit dieser UUID in der Datenbank gibt.
     * Das CustomFieldSet sollte eigentlich nur durch SimpleAPI angelegt werden.
     * Als Fallback (und für Kompatibilität zwischen unterschiedlichen Versionen) wird auch noch anhand des Namens
     * geprüft, ob das CustomFieldSet schonmal erstellt wurde.
     * Sollte das CustomFieldSet schon vorhanden sein, wird dessen UUID zurückgegeben.
     */
    protected function getCustomFieldSetId(array $customFieldData, Context $context): string
    {
        $setName = 'product_details_simple_api';
        $setId = Uuid::fromStringToHex($setName);
        $customFieldSet = $this->getCustomFieldSetById($setId, $context);
        if (null === $customFieldSet) {
            $customFieldSet = $this->getCustomFieldSetByName($setName, $context);
            if (null === $customFieldSet) {
                $this->createCustomFieldSet($setId, $setName, $customFieldData, $context);
            } else {
                $setId = $customFieldSet->getId();
            }
        }
        return $setId;
    }

    protected function createCustomFieldSet(string $setId, string $setName, array $customFieldData, Context $context): void
    {
        $payload = [
            'id' => $setId,
            'name' => $setName,
            'config' => ['label' => []]
        ];
        // Für alle Sprachen, für die ein Wert für das CustomField übergeben wurde, soll auch das Label des CustomFieldSet gesetzt werden.
        foreach ($customFieldData['values'] as $isoCode => $customFieldValue) {
            $payload['config']['label'][$isoCode] = 'Details (Simple API)';
        }

        $this->customFieldSetRepository->create([$payload], $context);

        /*
        Wir müssen das neue CustomFieldSet noch mit der Produkt-Entität verknüpfen.
        Das geschieht über einen Eintrag in der custom_field_relation-Tabelle.
        Dabei generieren wir eine nachvollziehbare UUID für den Eintrag aus dem Wort 'product'
        (in custom_field_relation-Tabelle ist der entity_name als Wort hinterlegt)
        sowie der UUID des neuen CustomFieldSets.
        */
        $payload = [
            'id' => BitwiseOperations::xorHex(Uuid::fromStringToHex('product'), $setId),
            'customFieldSetId' => $setId,
            'entityName' => 'product'
        ];
        $this->customFieldSetRelationRepository->create([$payload], $context);
    }

    private function constructPayload(array $customFieldData, string $name): array
    {
        $payload = [
            'type'   => null,
            'config' => [],
        ];
        switch ($customFieldData['type']) {
            case 'text':
                $payload = [
                    'type'   => 'text',
                    'config' => [
                        'type'            => 'text',
                        'componentName'   => 'sw-field',
                        'customFieldType' => 'text',
                    ],
                ];
                break;

            case 'text_area':
                $payload = [
                    'type'   => 'html',
                    'config' => [
                        'type'            => 'text',
                        'componentName'   => 'sw-text-editor',
                        'customFieldType' => 'textEditor',
                    ],
                ];
                break;

            case 'int':
                $payload = [
                    'type'   => 'int',
                    'config' => [
                        'numberType'      => 'int',
                        'componentName'   => 'sw-field',
                        'customFieldType' => 'number',
                        'min'             => null,
                        'max'             => null,
                    ],
                ];
                break;

            case 'float':
                $payload = [
                    'type'   => 'float',
                    'config' => [
                        'numberType'      => 'float',
                        'componentName'   => 'sw-field',
                        'customFieldType' => 'number',
                        'min'             => null,
                        'max'             => null,
                    ],
                ];
                break;

            case 'bool':
                $payload = [
                    'type'   => 'bool',
                    'config' => [
                        'type'            => 'switch',
                        'componentName'   => 'sw-field',
                        'customFieldType' => 'switch',
                    ],
                ];
                break;
        }

        /*
        Im Payload der SimpleAPI kann direkt ein Label für ein customField mitgegeben werden.
        Das Label kann in unterschiedlichen Sprachen vorliegen.
        */
        if (array_key_exists('labels', $customFieldData)) {
            foreach ($customFieldData['labels'] as $isoCode => $label) {
                $payload['config']['label'][$isoCode] = $label;
            }
        /*
        Fallback: wurde kein Label explizit übergeben, wird der customField-Code als Label verwendet.
        Das ist wichtig, weil in der Shopware Administration ein customField ohne Label nicht so gut dargestellt werden kann.
        */
        } else {
            foreach ($customFieldData['values'] as $isoCode => $data) {
                $payload['config']['label'][$isoCode] = $name;
            }
        }

        return $payload;
    }
}
