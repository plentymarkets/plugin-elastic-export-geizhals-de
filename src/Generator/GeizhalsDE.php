<?php

namespace ElasticExportGeizhalsDE\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use ElasticExport\Helper\ElasticExportStockHelper;
use ElasticExportGeizhalsDE\Helper\PriceHelper;
use Plenty\Modules\DataExchange\Contracts\CSVPluginGenerator;
use Plenty\Modules\Helper\Services\ArrayHelper;
use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\Search\Contracts\VariationElasticSearchScrollRepositoryContract;
use Plenty\Modules\Order\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Log\Loggable;

/**
 * Class GeizhalsDE
 * @package ElasticExportGeizhalsDE\Generator
 */
class GeizhalsDE extends CSVPluginGenerator
{
    use Loggable;

    const DELIMITER = ";";

    /**
     * @var ElasticExportCoreHelper
     */
    private $elasticExportCoreHelper;

    /**
     * @var ElasticExportStockHelper $elasticExportStockHelper
     */
    private $elasticExportStockHelper;

    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

	/**
	 * @var array
	 */
	private $paymentInAdvanceCache;

	/**
	 * @var array
	 */
	private $cashOnDeliveryCache;

	/**
	 * @var array
	 */
	private $manufacturerCache;

	/**
	 * GeizhalsDE constructor.
     *
	 * @param ArrayHelper $arrayHelper
	 * @param PriceHelper $priceHelper
	 */
    public function __construct(
        ArrayHelper $arrayHelper,
        PriceHelper $priceHelper
	)
    {
        $this->arrayHelper = $arrayHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Generates and populates the data into the CSV file.
     *
     * @param VariationElasticSearchScrollRepositoryContract $elasticSearch
     * @param array $formatSettings
     * @param array $filter
     */
    protected function generatePluginContent($elasticSearch, array $formatSettings = [], array $filter = [])
    {
    	$this->elasticExportStockHelper = pluginApp(ElasticExportStockHelper::class);
        $this->elasticExportCoreHelper = pluginApp(ElasticExportCoreHelper::class);

        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');

        $this->setDelimiter(self::DELIMITER);

        $this->addCSVContent($this->head());

        $startTime = microtime(true);

        if($elasticSearch instanceof VariationElasticSearchScrollRepositoryContract)
        {
            // Initiate the counter for the variations limit
            $limitReached = false;
            $limit = 0;

            do
            {
                $this->getLogger(__METHOD__)->debug('ElasticExportGeizhalsDE::logs.writtenLines', [
                    'Lines written' => $limit,
                ]);

                if($limitReached === true)
                {
                    break;
                }

                $esStartTime = microtime(true);

                // Get the data from Elastic Search
                $resultList = $elasticSearch->execute();

                $this->getLogger(__METHOD__)->debug('ElasticExportGeizhalsDE::logs.esDuration', [
                    'Elastic Search duration' => microtime(true) - $esStartTime,
                ]);

                if(count($resultList['error']) > 0)
                {
                    $this->getLogger(__METHOD__)->error('ElasticExportGeizhalsDE::logs.occurredElasticSearchErrors', [
                        'Error message' => $resultList['error'],
                    ]);

                    break;
                }

                $buildRowStartTime = microtime(true);

                if(is_array($resultList['documents']) && count($resultList['documents']) > 0)
                {
                    foreach($resultList['documents'] as $variation)
                    {
                        // Stop and set the flag if limit is reached
                        if($limit == $filter['limit'])
                        {
                            $limitReached = true;
                            break;
                        }

                        // If filtered by stock is set and stock is negative, then skip the variation
                        if ($this->elasticExportStockHelper->isFilteredByStock($variation, $filter) === true)
                        {
                            $this->getLogger(__METHOD__)->info('ElasticExportGeizhalsDE::logs.variationNotPartOfExportStock', [
                                'VariationId' => $variation['id']
                            ]);

                            continue;
                        }

                        try
                        {
                            $this->buildRow($variation, $settings);
                        }
                        catch(\Throwable $throwable)
                        {
                            $this->getLogger(__METHOD__)->error('ElasticExportGeizhalsDE::logs.fillRowError', [
                                'Error message ' => $throwable->getMessage(),
                                'Error line'     => $throwable->getLine(),
                                'VariationId'    => $variation['id']
                            ]);
                        }

                        // New line was added
                        $limit++;
                    }

                    $this->getLogger(__METHOD__)->debug('ElasticExportGeizhalsDE::logs.buildRowDuration', [
                        'Build rows duration' => microtime(true) - $buildRowStartTime,
                    ]);
                }

            } while ($elasticSearch->hasNext());
        }

        $this->getLogger(__METHOD__)->debug('ElasticExportGeizhalsDE::logs.fileGenerationDuration', [
            'Whole file generation duration' => microtime(true) - $startTime,
        ]);
    }

    /**
     * Creates the header of the CSV file.
     *
     * @return array
     */
    private function head():array
    {
        return array(
            'Hersteller',
            'Produktcode',
            'Bezeichnung',
            'Preis',
            'Deeplink',
            'Vorkasse',
            'Nachname',
            'Verfügbarkeit',
            'Herstellercode',
            'EAN',
            'Kategorie',
            'Grundpreis',
        );
    }

    /**
     * Creates the variation row and prints it into the CSV file.
     *
     * @param $variation
     * @param KeyValue $settings
     */
    private function buildRow($variation, KeyValue $settings)
    {
        // get the price
        $price = $this->priceHelper->getPrice($variation, $settings);

        // only variations with the Retail Price greater than zero will be handled
        if(!is_null($price['variationRetailPrice.price']) && $price['variationRetailPrice.price'] > 0)
        {
            $variationName = $this->elasticExportCoreHelper->getAttributeValueSetShortFrontendName($variation, $settings);

            if(array_key_exists($variation['data']['item']['id'], $this->paymentInAdvanceCache))
            {
                $paymentInAdvance = $this->paymentInAdvanceCache[$variation['data']['item']['id']];
            }
            else
            {
                $paymentInAdvance = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 0);
                $this->paymentInAdvanceCache[$variation['data']['item']['id']] = $paymentInAdvance;
            }

            if(array_key_exists($variation['data']['item']['id'], $this->cashOnDeliveryCache))
            {
                $cashOnDelivery = $this->cashOnDeliveryCache[$variation['data']['item']['id']];
            }
            else
            {
                $cashOnDelivery = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 1);
                $this->cashOnDeliveryCache[$variation['data']['item']['id']] = $cashOnDelivery;
            }

            if(array_key_exists($variation['data']['item']['id'], $this->manufacturerCache))
            {
                $manufacturer = $this->manufacturerCache[$variation['data']['item']['id']];
            }
            else
            {
                $manufacturer = $this->elasticExportCoreHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']);
                $this->manufacturerCache[$variation['data']['item']['id']] = $manufacturer;
            }

            if(!is_null($paymentInAdvance))
            {
                $paymentInAdvance = number_format((float)$paymentInAdvance + $this->getPaymentShippingExtraCharge($price['variationRetailPrice.price'], $settings, 0), 2, '.', '');
            }
            else
            {
                $paymentInAdvance = '';
            }

            if(!is_null($cashOnDelivery))
            {
                $cashOnDelivery = number_format((float)$cashOnDelivery + $this->getPaymentShippingExtraCharge($price['variationRetailPrice.price'], $settings, 1), 2, '.', '');
            }
            else
            {
                $cashOnDelivery = '';
            }

            $data = [
                'Hersteller' 		=> $manufacturer,
                'Produktcode' 		=> $variation['id'],
                'Bezeichnung' 		=> $this->elasticExportCoreHelper->getMutatedName($variation, $settings) . (strlen($variationName) ? ' ' . $variationName : ''),
                'Preis' 			=> number_format((float)$price['variationRetailPrice.price'], 2, '.', ''),
                'Deeplink' 			=> $this->elasticExportCoreHelper->getMutatedUrl($variation, $settings, true, false),
                'Vorkasse' 			=> $paymentInAdvance,
                'Nachname' 		    => $cashOnDelivery,
                'Verfügbarkeit' 	=> $this->elasticExportCoreHelper->getAvailability($variation, $settings),
                'Herstellercode' 	=> $variation['data']['variation']['model'],
                'EAN' 				=> $this->elasticExportCoreHelper->getBarcodeByType($variation, $settings->get('barcode')),
                'Kategorie' 		=> $this->elasticExportCoreHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
                'Grundpreis' 		=> $this->elasticExportCoreHelper->getBasePrice($variation, $price, $settings->get('lang')),
            ];

            $this->addCSVContent(array_values($data));
        }
        else
        {
            $this->getLogger(__METHOD__)->info('ElasticExportGeizhalsDE::logs.variationNotPartOfExportPrice', [
                'VariationId' => $variation['id']
            ]);
        }
    }

    /**
     * Get payement extra charge.
     *
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
}
