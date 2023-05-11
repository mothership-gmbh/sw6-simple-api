<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;

class TranslationProcessor
{
    public function process(array &$data, Product $request): void
    {
        $translatableFields = [
            'name'             => 'name',
            'description'      => 'description',
            'keywords'         => 'keywords',
            'meta_title'       => 'metaTitle',
            'meta_description' => 'metaDescription',
        ];

        foreach ($translatableFields as $translatableField => $translatableFieldShopware) {
            $translatable = $request->{'get' . ucfirst($translatableField)}();
            if (null !== $translatable) {
                foreach ($translatable as $isoCode => $value) {
                    $data['translations'][$isoCode][$translatableFieldShopware] = $value;
                }
            }
        }
    }
}
