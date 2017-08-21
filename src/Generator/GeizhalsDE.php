<?php

namespace ElasticExportGeizhalsDE\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use ElasticExport\Helper\ElasticExportPriceHelper;
use ElasticExport\Helper\ElasticExportStockHelper;
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
     * @var ElasticExportStockHelper
     */
    private $elasticExportStockHelper;

    /**
     * @var ElasticExportPriceHelper
     */
    private $elasticExportPriceHelper;

    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

	/**
	 * @var array
	 */
	private $paymentInAdvanceCache;

	/**
	 * @var array
	 */
	private $cashOnDeliveryCache;

	/**
	 * GeizhalsDE constructor.
     *
	 * @param ArrayHelper $arrayHelper
	 */
    public function __construct(
        ArrayHelper $arrayHelper
	)
    {
        $this->arrayHelper = $arrayHelper;
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

        $this->elasticExportPriceHelper = pluginApp(ElasticExportPriceHelper::class);

        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');

        $this->setDelimiter(self::DELIMITER);

        $this->addCSVContent($this->head());

        if($elasticSearch instanceof VariationElasticSearchScrollRepositoryContract)
        {
            // Initiate the counter for the variations limit
            $limitReached = false;
            $limit = 0;

            do
            {
                if($limitReached === true)
                {
                    break;
                }

                // Get the data from Elastic Search
                $resultList = $elasticSearch->execute();

                if(!is_null($resultList['error']) && count($resultList['error']) > 0)
                {
                    $this->getLogger(__METHOD__)->error('ElasticExportGeizhalsDE::logs.occurredElasticSearchErrors', [
                        'Error message' => $resultList['error'],
                    ]);

                    break;
                }

                if(is_array($resultList['documents']) && count($resultList['documents']) > 0)
                {
                    $previousItemId = null;

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
                            continue;
                        }

                        try
                        {
                            // Set the caches if we have the first variation or when we have the first variation of an item
                            if($previousItemId === null || $previousItemId != $variation['data']['item']['id'])
                            {
                                $previousItemId = $variation['data']['item']['id'];
                                unset($this->paymentInAdvanceCache, $this->cashOnDeliveryCache);

                                // Build the caches arrays
                                $this->buildCaches($variation, $settings);
                            }

                            // Build the new row for printing in the CSV file
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
                }

            } while ($elasticSearch->hasNext());
        }
    }

    /**
     * Creates the header of the CSV file.
     *
     * @return array
     */
    private function head():array
    {
        return array(
            'Produktbezeichnung',
            'Herstellername',
            'Preis',
            'Deeplink',
            'Herstellernummer',
            'Beschreibung',
            'Verfügbarkeit',
            'Versand Vorkasse',
            'Versand Nachnahme',
            'EAN',
            'Produktcode',
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
        // Get the price list
        $priceList = $this->elasticExportPriceHelper->getPriceList($variation, $settings, 2, ',');

        // Only variations with the Retail Price greater than zero will be handled
        if(!is_null($priceList['price']) && $priceList['price'] > 0)
        {
            $variationName = $this->elasticExportCoreHelper->getAttributeValueSetShortFrontendName($variation, $settings);

            $paymentInAdvance = $this->getPaymentInAdvance($variation, $priceList['price'], $settings);

            $cashOnDelivery = $this->getCashOnDelivery($variation, $priceList['price'], $settings);

            $data = [
                'Produktbezeichnung'    => $this->elasticExportCoreHelper->getMutatedName($variation, $settings) . (strlen($variationName) ? ' ' . $variationName : ''),
                'Herstellername'        => $this->elasticExportCoreHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']),
                'Preis'                 => $priceList['price'],
                'Deeplink'              => $this->elasticExportCoreHelper->getMutatedUrl($variation, $settings, true, false),
                'Herstellernummer'      => $variation['data']['variation']['model'],
                'Beschreibung'          => $this->elasticExportCoreHelper->getMutatedDescription($variation, $settings),
                'Verfügbarkeit'         => $this->elasticExportCoreHelper->getAvailability($variation, $settings),
                'Versand Vorkasse'      => $paymentInAdvance,
                'Versand Nachnahme'     => $cashOnDelivery,
                'EAN'                   => $this->elasticExportCoreHelper->getBarcodeByType($variation, $settings->get('barcode')),
                'Produktcode'           => $variation['id'],
                'Kategorie'             => $this->elasticExportCoreHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
                'Grundpreis'            => $this->elasticExportPriceHelper->getBasePrice($variation, $priceList['price'], $settings->get('lang'), '/', false, true),
            ];

            $this->addCSVContent(array_values($data));
        }
    }

    /**
     * Get payment extra charge.
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

                return (float) $paymentMethods[$paymentMethodId]->feeForeignFlatRateWebstore;
            }
        }

        return 0.0;
    }

    /**
     * Build the cache arrays for the item variation.
     *
     * @param $variation
     * @param $settings
     */
    private function buildCaches($variation, $settings)
    {
        if(!is_null($variation) && !is_null($variation['data']['item']['id']))
        {
            $shippingCost = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 0);
            $this->paymentInAdvanceCache[$variation['data']['item']['id']] = (float)$shippingCost;

            $cashOnDelivery = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, 1);
            $this->cashOnDeliveryCache[$variation['data']['item']['id']] = (float)$cashOnDelivery;
        }
    }

    /**
     * Get the payment in advance.
     *
     * @param $variation
     * @param $price
     * @param $settings
     * @return mixed|null|string
     */
    private function getPaymentInAdvance($variation, $price, $settings)
    {
        $paymentInAdvance = null;
        if(isset($this->paymentInAdvanceCache) && array_key_exists($variation['data']['item']['id'], $this->paymentInAdvanceCache))
        {
            $paymentInAdvance = $this->paymentInAdvanceCache[$variation['data']['item']['id']];
        }

        if(!is_null($paymentInAdvance))
        {
            $paymentInAdvance = number_format((float)$paymentInAdvance + $this->getPaymentShippingExtraCharge($price, $settings, 0), 2, '.', '');
            return $paymentInAdvance;
        }

        return '';
    }

    /**
     * Get the cash on delivery.
     *
     * @param $variation
     * @param $price
     * @param $settings
     * @return mixed|null|string
     */
    private function getCashOnDelivery($variation, $price, $settings)
    {
        $cashOnDelivery = null;
        if(isset($this->cashOnDeliveryCache) && array_key_exists($variation['data']['item']['id'], $this->cashOnDeliveryCache))
        {
            $cashOnDelivery = $this->cashOnDeliveryCache[$variation['data']['item']['id']];
        }

        if(!is_null($cashOnDelivery))
        {
            $cashOnDelivery = number_format((float)$cashOnDelivery + $this->getPaymentShippingExtraCharge($price, $settings, 1), 2, '.', '');
            return $cashOnDelivery;
        }

        return '';
    }
}
