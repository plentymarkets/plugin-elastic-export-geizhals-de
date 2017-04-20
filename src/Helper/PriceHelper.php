<?php

namespace ElasticExportGeizhalsDE\Helper;

use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchRequest;
use Plenty\Legacy\Repositories\Item\SalesPrice\SalesPriceSearchRepository;

class PriceHelper
{
    /**
     * @var SalesPriceSearchRepository
     */
    private $salesPriceSearchRepository;

    /**
     * PriceHelper constructor.
     * @param SalesPriceSearchRepository $salesPriceSearchRepository
     */
    public function __construct(SalesPriceSearchRepository $salesPriceSearchRepository)
    {
        $this->salesPriceSearchRepository = $salesPriceSearchRepository;
    }

    /**
     * Get a List of price, reduced price and the reference for the reduced price.
     * @param array $variation
     * @param KeyValue $settings
     * @return array
     */
    public function getPrice($variation, KeyValue $settings):array
    {
        $variationPrice = 0.00;
        //getting the retail price
        /**
         * SalesPriceSearchRequest $salesPriceSearchRequest
         */
        $salesPriceSearchRequest = pluginApp(SalesPriceSearchRequest::class);
        if($salesPriceSearchRequest instanceof SalesPriceSearchRequest)
        {
            $salesPriceSearchRequest->variationId = $variation['id'];
            $salesPriceSearchRequest->referrerId = $settings->get('referrerId');
            $salesPriceSearch  = $this->salesPriceSearchRepository->search($salesPriceSearchRequest);
            $variationPrice = $salesPriceSearch->price;
        }

        return array(
            'variationRetailPrice.price'                     =>  $variationPrice,
        );
    }
}