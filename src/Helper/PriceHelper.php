<?php

namespace ElasticExportGeizhalsDE\Helper;

use Plenty\Modules\Helper\Models\KeyValue;
use Plenty\Modules\Item\SalesPrice\Contracts\SalesPriceSearchRepositoryContract;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchRequest;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchResponse;

class PriceHelper
{
    /**
     * @var SalesPriceSearchRepositoryContract
     */
    private $salesPriceSearchRepository;

    /**
     * @var SalesPriceSearchRequest
     */
    private $salesPriceSearchRequest;

    /**
     * PriceHelper constructor.
     * @param SalesPriceSearchRepositoryContract $salesPriceSearchRepositoryContract
     * @param SalesPriceSearchRequest $salesPriceSearchRequest
     */
    public function __construct(
        SalesPriceSearchRepositoryContract $salesPriceSearchRepositoryContract,
        SalesPriceSearchRequest $salesPriceSearchRequest)
    {
        $this->salesPriceSearchRepository = $salesPriceSearchRepositoryContract;
        $this->salesPriceSearchRequest = $salesPriceSearchRequest;
    }

    /**
     * Gets the price.
     * @param array $variation
     * @param KeyValue $settings
     * @return array
     */
    public function getPrice($variation, KeyValue $settings):array
    {
        $variationPrice = 0.00;

        if($this->salesPriceSearchRequest instanceof SalesPriceSearchRequest)
        {
            $this->salesPriceSearchRequest->variationId = $variation['id'];
            $this->salesPriceSearchRequest->referrerId = $settings->get('referrerId');
            $this->salesPriceSearchRequest->type = 'default';
        }

        // getting the retail price
        $salesPriceSearch = $this->salesPriceSearchRepository->search($this->salesPriceSearchRequest);
        if($salesPriceSearch instanceof SalesPriceSearchResponse)
        {
            $variationPrice = (float)$salesPriceSearch->price;
        }

        return array(
            'variationRetailPrice.price' => $variationPrice,
        );
    }
}