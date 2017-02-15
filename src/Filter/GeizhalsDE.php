<?php

namespace ElasticExportGeizhalsDE\Filter;

use Plenty\Modules\DataExchange\Contracts\FiltersForElasticSearchContract;
use Plenty\Plugin\Application;


/**
 * Class GeizhalsDE
 * @package ElasticExportGeizhalsDE\Filter
 */
class GeizhalsDE extends FiltersForElasticSearchContract
{
    /**
     * @var Application $app
     */
    private $app;

    /**
     * GeizhalsDE constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Adds an empty array with the filters,
     * because they are not needed anymore.
     * @return array
     */
    public function generateElasticSearchFilter():array
    {
        $searchFilter = array();

        return $searchFilter;
    }
}