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

    /**
     * @var ElasticExportCoreHelper
     */
    private $elasticExportCoreHelper;

    /**
     * @var ArrayHelper
     */
    private $arrayHelper;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

	/**
	 * @var ElasticExportStockHelper $elasticExportStockHelper
	 */
	private $elasticExportStockHelper;

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
	 * @param ArrayHelper $arrayHelper
	 * @param PriceHelper $priceHelper
	 * @param ElasticExportStockHelper $elasticExportStockHelper
	 */
    public function __construct(
        ArrayHelper $arrayHelper,
        PriceHelper $priceHelper,
		ElasticExportStockHelper $elasticExportStockHelper
	)
    {
        $this->arrayHelper = $arrayHelper;
        $this->priceHelper = $priceHelper;
		$this->elasticExportStockHelper = $elasticExportStockHelper;
    }

    /**
     * Generates and populates the data into the CSV file.
     * @param VariationElasticSearchScrollRepositoryContract $elasticSearch
     * @param array $formatSettings
     * @param array $filter
     */
    protected function generatePluginContent($elasticSearch, array $formatSettings = [], array $filter = [])
    {
        $this->elasticExportCoreHelper = pluginApp(ElasticExportCoreHelper::class);

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

        if($elasticSearch instanceof VariationElasticSearchScrollRepositoryContract)
        {
            $limitReached = false;
            $lines = 0;
            do
            {
                if($limitReached === true)
                {
                    break;
                }

                $resultList = $elasticSearch->execute();

                foreach($resultList['documents'] as $variation)
                {
                    if($lines == $filter['limit'])
                    {
                        $limitReached = true;
                        break;
                    }

                    if(is_array($resultList['documents']) && count($resultList['documents']) > 0)
                    {
                        if($this->elasticExportStockHelper->isFilteredByStock($variation, $filter) === true)
                        {
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
                                'Error line'    => $throwable->getLine(),
                                'VariationId'   => $variation['id']
                            ]);
                        }
                        $lines = $lines +1;
                    }
                }
            }while ($elasticSearch->hasNext());
        }
    }

	/**
	 * @param $variation
	 * @param $settings
	 */
    private function buildRow($variation, $settings)
    {
        $price = $this->priceHelper->getPrice($variation, $settings);
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

        if(!is_null($paymentInAdvance) && !is_null($price['variationRetailPrice.price']))
        {
            $paymentInAdvance = number_format((float)$paymentInAdvance + $this->getPaymentShippingExtraCharge($price['variationRetailPrice.price'], $settings, 0), 2, '.', '');
        }
        else
        {
            $paymentInAdvance = '';
        }

        if(!is_null($cashOnDelivery) && !is_null($price['variationRetailPrice.price']))
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
            'Bezeichnung' 		=> $this->elasticExportCoreHelper->getName($variation, $settings) . (strlen($variationName) ? ' ' . $variationName : ''),
            'Preis' 			=> $price != null ? $price['variationRetailPrice.price'] : number_format((float)$price['variationRetailPrice.price'], 2, '.', ''),
            'Deeplink' 			=> $this->elasticExportCoreHelper->getUrl($variation, $settings, true, false),
            'Vorkasse' 			=> $paymentInAdvance,
            'Nachnahme' 		=> $cashOnDelivery,
            'Verfügbarkeit' 	=> $this->elasticExportCoreHelper->getAvailability($variation, $settings),
            'Herstellercode' 	=> $variation['data']['variation']['model'],
            'EAN' 				=> $this->elasticExportCoreHelper->getBarcodeByType($variation, $settings->get('barcode')),
            'Kategorie' 		=> $this->elasticExportCoreHelper->getCategory((int)$variation['data']['defaultCategories'][0]['id'], $settings->get('lang'), $settings->get('plentyId')),
            'Grundpreis' 		=> $this->elasticExportCoreHelper->getBasePrice($variation, $price, $settings->get('lang')),
        ];

        $this->addCSVContent(array_values($data));
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
}
