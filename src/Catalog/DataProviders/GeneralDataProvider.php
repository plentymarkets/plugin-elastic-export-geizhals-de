<?php

namespace ElasticExportGeizhalsDE\Catalog\DataProviders;

use Plenty\Modules\Catalog\Contracts\TemplateContract;
use Plenty\Modules\Catalog\DataProviders\BaseDataProvider;

/**
 * Class GeneralDataProvider
 *
 * @package ElasticExportGeizhalsDE\Catalog\DataProviders
 */
class GeneralDataProvider extends BaseDataProvider
{
    public function getRows(): array
    {
        return [
            //required
            [
                'key' => 'producer',
                'label' => 'Herstellername',
                'required' => false
            ],
            [
                'key' => 'variation_id',
                'label' => 'Produktcode',
                'required' => false
            ],
            [
                'key' => 'variation_name',
                'label' => 'Produktbezeichnung',
                'required' => false
            ],
            [
                'key' => 'price',
                'label' => 'Preis',
                'required' => false
            ],
            [
                'key' => 'deeplink',
                'label' => 'Deeplink',
                'required' => false,
                'hidden' => true
            ],
            [
                'key' => 'availability',
                'label' => 'Verfügbarkeit',
                'required' => false
            ],
            [
                'key' => 'manufacturer_number',
                'label' => 'Herstellernummer',
                'required' => false
            ],
            [
                'key' => 'ean',
                'label' => 'EAN',
                'required' => false
            ],
            [
                'key' => 'category',
                'label' => 'Kategorie',
                'required' => false,
                'hidden' => true
            ],
            [
                'key' => 'unit_price',
                'label' => 'Grundpreis',
                'required' => false,
                'hidden' => true
            ],
            [
                'key' => 'description',
                'label' => 'Beschreibung',
                'required' => false
            ],
        ];
    }


    public function setTemplate(TemplateContract $template) {}

    public function setMapping(array $mapping) {}
}