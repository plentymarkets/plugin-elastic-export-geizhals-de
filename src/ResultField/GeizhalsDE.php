<?php

namespace ElasticExportGeizhalsDE\ResultField;

use Plenty\Modules\Cloud\ElasticSearch\Lib\ElasticSearch;
use Plenty\Modules\DataExchange\Contracts\ResultFields;
use Plenty\Modules\Helper\Services\ArrayHelper;
use Plenty\Modules\Item\Search\Mutators\DefaultCategoryMutator;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Source\Mutator\BuiltIn\LanguageMutator;
use Plenty\Modules\Item\Search\Mutators\KeyMutator;

/**
 * Class GeizhalsDE
 * @package ElasticExportGeizhalsDE\ResultField
 */
class GeizhalsDE extends ResultFields
{
    /**
	 * @var ArrayHelper
	 */
    private $arrayHelper;

    /**
     * GeizhalsDE constructor.
     * @param ArrayHelper $arrayHelper
     */
    public function __construct(ArrayHelper $arrayHelper)
    {
        $this->arrayHelper = $arrayHelper;
    }

    /**
     * Creates the fields set to be retrieved from ElasticSearch.
     * @param  array $formatSettings = []
     * @return array
     */
    public function generateResultFields(array $formatSettings = []):array
    {
        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');

        $this->setOrderByList(['item.id', ElasticSearch::SORTING_ORDER_ASC]);

        $itemDescriptionFields = ['texts.urlPath', 'texts.lang'];

        $itemDescriptionFields[] = ($settings->get('nameId')) ? 'texts.name' . $settings->get('nameId') : 'texts.name1';

        if($settings->get('descriptionType') == 'itemShortDescription'
            || $settings->get('previewTextType') == 'itemShortDescription')
        {
            $itemDescriptionFields[] = 'texts.shortDescription';
        }

        if($settings->get('descriptionType') == 'itemDescription'
            || $settings->get('descriptionType') == 'itemDescriptionAndTechnicalData'
            || $settings->get('previewTextType') == 'itemDescription'
            || $settings->get('previewTextType') == 'itemDescriptionAndTechnicalData')
        {
            $itemDescriptionFields[] = 'texts.description';
        }

        if($settings->get('descriptionType') == 'technicalData'
            || $settings->get('descriptionType') == 'itemDescriptionAndTechnicalData'
            || $settings->get('previewTextType') == 'technicalData'
            || $settings->get('previewTextType') == 'itemDescriptionAndTechnicalData')
        {
            $itemDescriptionFields[] = 'texts.technicalData';
        }

        //Mutator
        /**
         * @var KeyMutator
         */
        $keyMutator = pluginApp(KeyMutator::class);

        if($keyMutator instanceof KeyMutator)
        {
            $keyMutator->setKeyList($this->getKeyList());
            $keyMutator->setNestedKeyList($this->getNestedKeyList());
        }

        /**
         * @var LanguageMutator $languageMutator
         */
        $languageMutator = pluginApp(LanguageMutator::class, [[$settings->get('lang')]]);

        /**
         * @var DefaultCategoryMutator $defaultCategoryMutator
         */
        $defaultCategoryMutator = pluginApp(DefaultCategoryMutator::class);

        if($defaultCategoryMutator instanceof DefaultCategoryMutator)
        {
            $defaultCategoryMutator->setPlentyId($settings->get('plentyId'));
        }

        //Fields
        $fields = [
            [
                //item
                'item.id',
                'item.manufacturer.id',

                //variation
                'id',
                'variation.availability.id',
                'variation.model',
                'variation.stockLimitation',

                //unit
                'unit.content',
                'unit.id',

                //defaultCategories
                'defaultCategories.id',

                //barcodes
                'barcodes.code',
                'barcodes.type',

                //attributes
                'attributes.attributeValueSetId',
                'attributes.attributeId',
                'attributes.valueId',

            ],

            [
                $languageMutator,
                $defaultCategoryMutator
            ],
        ];

        foreach($itemDescriptionFields as $itemDescriptionField)
        {
            //texts
            $fields[0][] = $itemDescriptionField;
        }

        return $fields;
    }

    /**
     * Returns the list of keys.
     * @return array
     */
    private function getKeyList()
    {
        return [
            // Item
            'item.id',
            'item.manufacturer.id',

            // Variation
            'variation.availability.id',
            'variation.model',
            'variation.stockLimitation',

            // Unit
            'unit.content',
            'unit.id',
        ];
    }

    /**
     * Returns the list of nested keys.
     * @return array
     */
    private function getNestedKeyList()
    {
        return [
            'keys' => [
                // Attributes
                'attributes',

                // Barcodes
                'barcodes',

                // Default categories
                'defaultCategories',

                // Texts
                'texts',
            ],

            'nestedKeys' => [
                // Attributes
                'attributes' => [
                    'attributeValueSetId',
                    'attributeId',
                    'valueId'
                ],

                // Barcodes
                'barcodes' => [
                    'code',
                    'type'
                ],

                // Default categories
                'defaultCategories' => [
                    'id'
                ],

                // Texts
                'texts' => [
                    'urlPath',
                    'lang',
                    'name1',
                    'name2',
                    'name3',
                    'shortDescription',
                    'description',
                    'technicalData',
                ],
            ]
        ];
    }
}
