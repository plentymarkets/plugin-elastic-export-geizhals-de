<?php

namespace ElasticExportGeizhalsDE\Generator;

use ElasticExport\Helper\ElasticExportCoreHelper;
use ElasticExport\Helper\ElasticExportPriceHelper;
use ElasticExport\Helper\ElasticExportShippingHelper;
use ElasticExport\Helper\ElasticExportStockHelper;
use ElasticExport\Services\FiltrationService;
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
	 * @var ElasticExportShippingHelper
	 */
    private $elasticExportShippingHelper;

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
     * @var FiltrationService
     */
    private $filtrationService;

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
        $this->elasticExportShippingHelper = pluginApp(ElasticExportShippingHelper::class);

        $settings = $this->arrayHelper->buildMapFromObjectList($formatSettings, 'key', 'value');
		$this->filtrationService = pluginApp(FiltrationService::class, ['settings' => $settings, 'filterSettings' => $filter]);

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
                        if ($this->filtrationService->filter($variation))
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
     * @param KeyValue $settings 
     * @return array
     */
    private function head($settings):array
    {
        $header = array(
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
        );
        
        $shippingHeaderList = $this->elasticExportShippingHelper->shippingHeader($settings, $this->elasticExportCoreHelper);
        
        foreach($shippingHeaderList as $shippingHeader)
        {
        	$header[] = $shippingHeader;
        }
        
        return array_unique($header);
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
            
            $shippingData = $this->elasticExportShippingHelper->getPaymentMethodCosts($variation, $priceList['price'], $this->elasticExportCoreHelper, $settings);
            
            foreach($shippingData as $paymentMethod => $costs)
            {
            	$data[$paymentMethod] = $costs;
            }

            $this->addCSVContent(array_values($data));
        }
    }
}
