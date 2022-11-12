<?php

namespace App\Updaters;

use App\Models\ProductFields\ShopifyProductFieldRepository;
use App\Models\Store\ShopifyStore;

class UpdaterFor13535SeoTitle implements Updater
{
    const NAME = '13535SeoTitle';

    protected $shopifyProductFieldRepository;

    public function __construct(ShopifyProductFieldRepository $shopifyProductFieldRepository)
    {
        $this->shopifyProductFieldRepository = $shopifyProductFieldRepository;
    }

    public function run()
    {
        $seoField = $this
            ->shopifyProductFieldRepository
            ->createOrUpdate('metafields_global_title_tag', 'SEO Title');

        $this->shopifyProductFieldRepository->createOrUpdateMapping(
            null,
            $seoField->id,
            'Description'
        );

        $titleField = $this->shopifyProductFieldRepository->get('title');

        if (!isset($titleField)) {
            $titleField = $this->shopifyProductFieldRepository->createOrUpdate('title', 'Product Title');
        }

        foreach(ShopifyStore::all() as $shopifyStore) {
            $titleMapping = $this->shopifyProductFieldRepository->getMapping($shopifyStore->id, $titleField->id);
            $seoMapping = $this->shopifyProductFieldRepository->getMapping($shopifyStore->id, $seoField->id);
            if (!isset($seoMapping)) {
                $rexFieldName = isset($titleMapping) ? $titleMapping->rex_product_field_name : 'Description';
                $this->shopifyProductFieldRepository->createOrUpdateMapping(
                    $shopifyStore->id,
                    $seoField->id,
                    $rexFieldName
                );
            }
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}