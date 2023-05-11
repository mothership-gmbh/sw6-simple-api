<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class CustomFieldProcessor
{
    protected EntityRepositoryInterface $customFieldRepository;

    public function __construct(EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldRepository = $customFieldRepository;
    }

    public function process(array &$data, Product $request, string $productUuid, Context $context) : void
    {
        $customFields = $request->getCustomFields();
        foreach ($customFields as $customFieldCode => $customFieldOptions) {

            $customFieldId = $this->generateCustomFieldId($customFieldCode);
            $customField   = $this->getCustomFieldById($customFieldId, $context);
            if (null === $customField) {
                $this->createCustomField($customFieldId, $customFieldCode, $customFieldOptions['type'], $context);
            }

            foreach ($customFieldOptions['values'] as $isoCode => $value) {
                $data['translations'][$isoCode]['customFields'][$customFieldCode] = $value;
            }
        }
    }


    /**
     * @param string  $customFieldId
     * @param string  $name
     * @param string  $type
     * @param Context $context
     *
     * @return void
     */
    private function createCustomField(string $customFieldId, string $name, string $type, Context $context) : void
    {
        $payload = $this->constructPayload($type);
        $payload['id']   = $customFieldId;
        $payload['name'] = $name;
        $payload['translations'] = [
            $context->getLanguageId() => [
                'name' => $name,
                'customFields' => ['code' => $name]
            ]
        ];

        $this->customFieldRepository->create([$payload], $context);
    }

    private function constructPayload(string $type) : array
    {
        $payload = [
            'type' => null,
            'config' => []
        ];
        switch ($type)
        {
            case 'text':
                $payload = [
                  'type' => 'text',
                  'config' => [
                      'type' => 'text',
                      'componentName' => 'sw-field',
                      'customFieldType' => 'text'
                  ]
                ];
                break;

            case 'text_area':
                $payload = [
                    'type' => 'html',
                    'config' => [
                        'type' => 'text',
                        'componentName' => 'sw-text-editor',
                        'customFieldType' => 'textEditor'
                    ]
                ];
                break;

            case 'int':
                $payload = [
                    'type' => 'int',
                    'config' => [
                        'numberType' => 'int',
                        'componentName' => 'sw-field',
                        'customFieldType' => 'number',
                        'min' => null,
                        'max' => null
                    ]
                ];
                break;

            case 'float':
                $payload = [
                    'type' => 'float',
                    'config' => [
                        'numberType' => 'float',
                        'componentName' => 'sw-field',
                        'customFieldType' => 'number',
                        'min' => null,
                        'max' => null
                    ]
                ];
                break;

            case 'bool':
                $payload = [
                    'type' => 'bool',
                    'config' => [
                        'type' => 'switch',
                        'componentName' => 'sw-field',
                        'customFieldType' => 'switch',
                    ]
                ];
                break;
        }

        return $payload;
    }

    private function generateCustomFieldId(string $customFieldCode)
    {
        return Uuid::fromStringToHex($customFieldCode);
    }

    protected function getCustomFieldById(string $customFieldId, Context $context): CustomFieldEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($customFieldId)]);
        return $this->customFieldRepository->search($criteria, $context)->first();
    }
}
