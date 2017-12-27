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
use Plenty\Modules\Order\Shipping\Models\DefaultShipping;
use Plenty\Plugin\Log\Loggable;

/**
 * Class GeizhalsDE
 * @package ElasticExportGeizhalsDE\Generator
 */
class GeizhalsDE extends CSVPluginGenerator
{
    use Loggable;

    const DELIMITER = ";";

    const DEFAULT_PAYMENT_METHOD = 'vorkasse';

    const SHIPPING_COST_TYPE_FLAT = 'flat';
    const SHIPPING_COST_TYPE_CONFIGURATION = 'configuration';

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
    private $usedPaymentMethods = [];

    /**
     * @var array
     */
    private $defaultShippingList = [];

	/**
	 * GeizhalsDE constructor.
     *
	 * @param ArrayHelper $arrayHelper
	 */
    public function __construct(ArrayHelper $arrayHelper)
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

        $this->addCSVContent($this->head($settings));

        if($elasticSearch instanceof VariationElasticSearchScrollRepositoryContract)
        {
        	
        	$elasticSearch->setNumberOfDocumentsPerShard(250);
        	
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
    private function head(KeyValue $settings):array
    {
        $data = [
            'Herstellername',
            'Produktcode',
            'Produktbezeichnung',
            'Preis',
            'Deeplink',
            'Verfügbarkeit',
            'Herstellernummer',
            'EAN',
            'Kategorie',
            'Grundpreis',
            'Beschreibung',
        ];

        /**
         * If the shipping cost type is configuration, all payment methods will be taken as available payment methods from the chosen
         * default shipping configuration.
         */
        if($settings->get('shippingCostType') == self::SHIPPING_COST_TYPE_CONFIGURATION)
        {
            /**
             * @var PaymentMethod[] $paymentMethods
             */
            $paymentMethods = $this->elasticExportCoreHelper->getPaymentMethods($settings);
            $defaultShipping = $this->elasticExportCoreHelper->getDefaultShipping($settings);

            if($defaultShipping instanceof DefaultShipping)
            {
                foreach([$defaultShipping->paymentMethod2, $defaultShipping->paymentMethod3] as $paymentMethodId)
                {
                    if(array_key_exists($paymentMethodId, $paymentMethods))
                    {
                        $usedPaymentMethod = $this->usedPaymentMethods[$defaultShipping->id][0];

                        /**
                         * Three cases:
                         */
                        if(	(count($this->usedPaymentMethods) == 0) ||

                            ((count($this->usedPaymentMethods) == 1 || count($this->usedPaymentMethods) == 2)
                                && $usedPaymentMethod instanceof PaymentMethod
                                && isset($usedPaymentMethod->id)
                                && $usedPaymentMethod->id != $paymentMethodId)
                        )
                        {
                            $paymentMethod = $paymentMethods[$paymentMethodId];

                            if($paymentMethod instanceof PaymentMethod)
                            {
                                if(isset($paymentMethod->name) && strlen($paymentMethod->name))
                                {
                                    $data[] = $paymentMethod->name;
                                    $this->usedPaymentMethods[$defaultShipping->id][] = $paymentMethod;
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * If nothing is checked at the elastic export settings regarding the shipping cost type,
         * all payment methods within both default shipping configurations will be taken as available payment methods.
         */
        elseif($settings->get('shippingCostType') == '')
        {
            /**
             * @var PaymentMethod[] $paymentMethods
             */
            $paymentMethods = $this->elasticExportCoreHelper->getPaymentMethods($settings);
            $this->defaultShippingList = $this->elasticExportCoreHelper->getDefaultShippingList();

            foreach($this->defaultShippingList as $defaultShipping)
            {
                if($defaultShipping instanceof DefaultShipping)
                {
                    foreach([$defaultShipping->paymentMethod2, $defaultShipping->paymentMethod3] as $paymentMethodId)
                    {
                        if(!array_key_exists($paymentMethodId, $paymentMethods) ||
                            !($paymentMethods[$paymentMethodId] instanceof PaymentMethod))
                        {
                            continue;
                        }

                        $paymentMethod = $paymentMethods[$paymentMethodId];

                        if($paymentMethod instanceof PaymentMethod)
                        {
                            if((count($this->usedPaymentMethods) == 0) ||
                                (count($this->usedPaymentMethods) >= 1 && $this->usedPaymentMethods[$defaultShipping->id][0]->id != $paymentMethodId)
                            )
                            {
                                $data[] = $paymentMethod->name;
                                $this->usedPaymentMethods[$defaultShipping->id][] = $paymentMethod;
                            }
                        }
                    }
                }
            }
        }

        if(count($this->usedPaymentMethods) <= 0 || $settings->get('shippingCostType') == self::SHIPPING_COST_TYPE_FLAT)
        {
            $data[] = self::DEFAULT_PAYMENT_METHOD;
        }

        return $data;
    }

    /**
     * Creates the variation row and prints it into the CSV file.
     *
     * @param $variation
     * @param KeyValue $settings
     */
    private function buildRow($variation, KeyValue $settings)
    {
        $priceList = $this->elasticExportPriceHelper->getPriceList($variation, $settings, 2, ',');

        $variationName = $this->elasticExportCoreHelper->getAttributeValueSetShortFrontendName($variation, $settings);

        $data = [
            'Herstellername'        => $this->elasticExportCoreHelper->getExternalManufacturerName((int)$variation['data']['item']['manufacturer']['id']),
            'Produktcode'           => $variation['id'],
            'Produktbezeichnung'    => $this->elasticExportCoreHelper->getMutatedName($variation, $settings) . (strlen($variationName) ? ' ' . $variationName : ''),
            'Preis'                 => $priceList['price'],
            'Deeplink'              => $this->elasticExportCoreHelper->getMutatedUrl($variation, $settings, true, false),
            'Verfügbarkeit'         => $this->elasticExportCoreHelper->getAvailability($variation, $settings),
            'Herstellernummer'      => $variation['data']['variation']['model'],
            'EAN'                   => $this->elasticExportCoreHelper->getBarcodeByType($variation, $settings->get('barcode')),
            'Kategorie'             => $this->elasticExportCoreHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
            'Grundpreis'            => $this->elasticExportPriceHelper->getBasePrice($variation, $priceList['price'], $settings->get('lang'), '/', false, true),
            'Beschreibung'          => $this->elasticExportCoreHelper->getMutatedDescription($variation, $settings),
        ];

        /**
         * Add the payment methods and their costs
         */
        if(count($this->usedPaymentMethods) == 1)
        {
            foreach($this->usedPaymentMethods as $paymentMethod)
            {
                foreach($paymentMethod as $method)
                {
                    if($method instanceof PaymentMethod)
                    {
                        if(isset($method->name))
                        {
                            $cost = $this->elasticExportCoreHelper->getShippingCost($variation['data']['item']['id'], $settings, $method->id);
                            $data[$method->name] = number_format((float)$cost, 2, '.', '');
                        }
                    }
                    else
                    {
                        $this->getLogger(__METHOD__)->error('ElasticExportGeizhalsDE::item.loadInstanceError', 'PaymentMethod');
                    }
                }
            }
        }
        elseif(count($this->usedPaymentMethods) > 1)
        {
            foreach($this->usedPaymentMethods as $defaultShipping => $paymentMethod)
            {
                foreach ($paymentMethod as $method)
                {
                    if($method instanceof PaymentMethod)
                    {
                        if(isset($method->name) && !isset($data[$method->name]))
                        {
                            $cost = $this->elasticExportCoreHelper->calculateShippingCost(
                                $variation['data']['item']['id'],
                                $this->defaultShippingList[$defaultShipping]->shippingDestinationId,
                                $this->defaultShippingList[$defaultShipping]->referrerId,
                                $method->id);
                            $data[$method->name] = number_format((float)$cost, 2, '.', '');
                        }
                    }
                    else
                    {
                        $this->getLogger(__METHOD__)->error('ElasticExportGeizhalsDE::item.loadInstanceError', 'PaymentMethod');
                    }
                }
            }
        }
        elseif(count($this->usedPaymentMethods) <= 0 && $settings->get('shippingCostType') == self::SHIPPING_COST_TYPE_FLAT)
        {
            $data[self::DEFAULT_PAYMENT_METHOD] = $settings->get('shippingCostFlat');
        }
        else
        {
            $data[self::DEFAULT_PAYMENT_METHOD] = 0.00;
        }

        $this->addCSVContent(array_values($data));
    }
}
