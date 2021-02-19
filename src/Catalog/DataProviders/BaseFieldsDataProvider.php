<?php

namespace ElasticExportGeizhalsDE\Catalog\DataProviders;

/**
 * Class BaseFieldsDataProvider
 *
 * @package ElasticExportGeizhalsDE\Catalog\DataProviders
 */
class BaseFieldsDataProvider
{
    /**
     * @return array
     */
    public function get():array
    {
        return [
            [
                'key' => 'producer',
                'label' => 'Herstellername',
                'required' => false,
                'default' => 'item-manufacturerExternalName',
                'type' => 'item',
                'fieldKey' => 'manufacturer.externalName',
                'isMapping' => false,
                'id' => null
//                'fallback' => [
//                    'default' => 'item-manufactureName',
//                    'type' => 'item',
//                    'fieldKey' => 'manufacturer.name',
//                    'isMapping' => false,
//                    'id' => null
//                ]
            ],
            [
                'key' => 'variation_id',
                'label' => 'Produktcode',
                'required' => false,
                'default' => 'variation-id',
                'type' => 'variation',
                'fieldKey' => 'id',
                'isMapping' => false,
                'id' => null,

            ],
            [
                'key' => 'variation_name',
                'label' => 'Produktbezeichnung',
                'required' => false,
                'default' => 'itemText-name1',
                'type' => 'text',
                'fieldKey' => 'name1',
                'isMapping' => false,
                'id' => null
            ],
            [
                'key' => 'price',
                'label' => 'Preis',
                'required' => false,
                'default' => 'salesPrice-2',
                'type' => 'sales-price',
                'fieldKey' => 'price',
                'isMapping' => false,
                'id' => null,
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
                'required' => false,
                'default' => 'variation-availability',
                'type' => 'variation',
                'fieldKey' => 'availability',
                'isMapping' => false,
                'id' => null
            ],
            [
                'key' => 'manufacturer_number',
                'label' => 'Herstellernummer',
                'required' => false,
                'default' => 'variation-model',
                'type' => 'variation',
                'fieldKey' => 'model',
                'isMapping' => false,
                'id' => null
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
                'required' => false,
                'default' => 'itemText-description',
                'type' => 'text',
                'fieldKey' => 'description',
                'isMapping' => false,
                'id' => null
            ],

        ];
    }
}
