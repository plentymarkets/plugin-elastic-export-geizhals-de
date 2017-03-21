<?php

namespace ElasticExportGeizhalsDE\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use Plenty\Modules\DataExchange\Contracts\CSVPluginGenerator;
use Plenty\Modules\Helper\Services\ArrayHelper;
use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\DataLayer\Models\Record;
use Plenty\Modules\Item\DataLayer\Models\RecordList;
use Plenty\Modules\Order\Payment\Method\Models\PaymentMethod;


/**
 * Class GeizhalsDE
 * @package ElasticExportGeizhalsDE\Generator
 */
class GeizhalsDE extends CSVPluginGenerator
{
    /**
     * @var ElasticExportCoreHelper
     */
    private $elasticExportCoreHelper;

    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

    /**
     * @var array $idlVariations
     */
    private $idlVariations = array();

    /**
     * GeizhalsDE constructor.
     * @param ArrayHelper $arrayHelper
     */
    public function __construct(ArrayHelper $arrayHelper)
    {
        $this->arrayHelper = $arrayHelper;
    }

    /**
     * Generates and populates the data into the CSV file.
     * @param array $resultData
     * @param array $formatSettings
     * @param array $filter
     */
    protected function generatePluginContent($resultData, array $formatSettings = [], array $filter = [])
    {
        $this->elasticExportCoreHelper = pluginApp(ElasticExportCoreHelper::class);

        if(is_array($resultData) && count($resultData['documents']) > 0)
        {
            $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');

            $this->setDelimiter(";");

            $this->addCSVContent([
                'Hersteller',
                'Produktcode',
                'Bezeichnung',
                'Preis',
                'Deeplink',
                'Vorkasse',
                'Nachnahme',
                'Verfügbarkeit',
                'Herstellercode',
                'EAN',
                'Kategorie',
                'Grundpreis',
            ]);

            // Create a List of all VariationIds
            $variationIdList = array();
            foreach($resultData['documents'] as $variation)
            {
                $variationIdList[] = $variation['id'];
            }

            // Get the ElasticSearch missing fields from IDL(ItemDataLayer)
            if(is_array($variationIdList) && count($variationIdList) > 0)
            {
                /**
                 * @var \ElasticExportGeizhalsDE\IDL_ResultList\GeizhalsDE $idlResultList
                 */
                $idlResultList = pluginApp(\ElasticExportGeizhalsDE\IDL_ResultList\GeizhalsDE::class);
                $idlResultList = $idlResultList->getResultList($variationIdList, $settings, $filter);
            }

            //Creates an array with the variationId as key to surpass the sorting problem
            if(isset($idlResultList) && $idlResultList instanceof RecordList)
            {
                $this->createIdlArray($idlResultList);
            }

            foreach($resultData['documents'] as $variation)
            {
                $variationName = $this->elasticExportCoreHelper->getAttributeValueSetShortFrontendName($variation, $settings);
                $paymentInAdvance = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 0);
                $cashOnDelivery = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 1);

                if(!is_null($paymentInAdvance))
                {
                    $paymentInAdvance = number_format((float)$paymentInAdvance + $this->getPaymentShippingExtraCharge($this->idlVariations[$variation['id']]['variationRetailPrice.price'], $settings, 0), 2, '.', '');
                }
                else
                {
                    $paymentInAdvance = '';
                }

                if(!is_null($cashOnDelivery))
                {
                    $cashOnDelivery = number_format((float)$cashOnDelivery + $this->getPaymentShippingExtraCharge($this->idlVariations[$variation['id']]['variationRetailPrice.price'], $settings, 1), 2, '.', '');
                }
                else
                {
                    $cashOnDelivery = '';
                }

                $data = [
                    'Hersteller' 		=> $this->elasticExportCoreHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']),
                    'Produktcode' 		=> $variation['id'],
                    'Bezeichnung' 		=> $this->elasticExportCoreHelper->getName($variation, $settings) . (strlen($variationName) ? ' ' . $variationName : ''),
                    'Preis' 			=> number_format((float)$this->idlVariations[$variation['id']]['variationRetailPrice.price'], 2, '.', ''),
                    'Deeplink' 			=> $this->elasticExportCoreHelper->getUrl($variation, $settings, true, false),
                    'Vorkasse' 			=> $paymentInAdvance,
                    'Nachnahme' 		=> $cashOnDelivery,
                    'Verfügbarkeit' 	=> $this->elasticExportCoreHelper->getAvailability($variation, $settings),
                    'Herstellercode' 	=> $variation['data']['variation']['model'],
                    'EAN' 				=> $this->elasticExportCoreHelper->getBarcodeByType($variation, $settings->get('barcode')),
                    'Kategorie' 		=> $this->elasticExportCoreHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
                    'Grundpreis' 		=> $this->elasticExportCoreHelper->getBasePrice($variation, $this->idlVariations[$variation['id']], $settings->get('lang')),
                ];

                $this->addCSVContent(array_values($data));
            }
        }
    }

    /**
     * Get payement extra charge.
     * @param  array    $price
     * @param  KeyValue $settings
     * @param  int      $paymentMethodId
     * @return float
     */
    private function getPaymentShippingExtraCharge($price, KeyValue $settings, int $paymentMethodId):float
    {
        $paymentMethods = $this->elasticExportCoreHelper->getPaymentMethods($settings);

        if(count($paymentMethods) > 0)
        {
            if(array_key_exists($paymentMethodId, $paymentMethods) && $paymentMethods[$paymentMethodId] instanceof PaymentMethod)
            {
                if($paymentMethods[$paymentMethodId]->feeForeignPercentageWebstore)
                {
                    return ((float) $paymentMethods[$paymentMethodId]->feeForeignPercentageWebstore / 100) * $price;
                }
                else
                {
                    return (float) $paymentMethods[$paymentMethodId]->feeForeignFlatRateWebstore;
                }
            }
        }

        return 0.0;
    }

    /**
     * Creates an array with the rest of data needed from the ItemDataLayer.
     * @param RecordList $idlResultList
     */
    private function createIdlArray($idlResultList)
    {
        if($idlResultList instanceof RecordList)
        {
            foreach($idlResultList as $idlVariation)
            {
                if($idlVariation instanceof Record)
                {
                    $this->idlVariations[$idlVariation->variationBase->id] = [
                        'itemBase.id' => $idlVariation->itemBase->id,
                        'variationBase.id' => $idlVariation->variationBase->id,
                        'variationStock.stockNet' => $idlVariation->variationStock->stockNet,
                        'variationRetailPrice.price' => $idlVariation->variationRetailPrice->price,
                    ];
                }
            }
        }
    }
}
