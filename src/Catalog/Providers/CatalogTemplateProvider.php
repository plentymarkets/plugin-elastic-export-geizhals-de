<?php

namespace ElasticExportGeizhalsDE\Catalog\Providers;

use ElasticExportGeizhalsDE\Catalog\DataProviders\GeneralDataProvider;
use Plenty\Modules\Catalog\Templates\BaseTemplateProvider;

/**
 * Class CatalogTemplateProvider
 *
 * @package ElasticExportGeizhalsDE\Catalog\Providers
 */
class CatalogTemplateProvider extends BaseTemplateProvider
{
    /**
     * @return array
     */
    public function getMappings(): array
    {
        return [
            [
                'identifier' => 'general',
                'label' => 'General',
                'isMapping' => false,
                'provider' => GeneralDataProvider::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return [
//            [
//                'name' => 'variationMarket.isVisibleForMarket',
//                'params' => [
//                      [
//                          'name' => 'marketId',
//                          'value' => 9.00
//                      ]
//                ]
//            ]
        ];
    }

    /**
     * @return callable[]
     */
    public function getPreMutators(): array
    {
        return [];
    }

    /**
     * @return callable[]
     */
    public function getPostMutators(): array
    {
        return [
            function($variation) {
                $variation['deeplink'] = 'https://google.com/';

                return $variation;
            }
        ];
    }

    /**
     * @return callable
     */
    public function getSkuCallback(): callable
    {
        return function ($value, $item) {
            return $value;
        };
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getMetaInfo(): array
    {
        return [];
    }
}