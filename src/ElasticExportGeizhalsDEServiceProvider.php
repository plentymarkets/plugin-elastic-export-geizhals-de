<?php

namespace ElasticExportGeizhalsDE;

use Plenty\Modules\DataExchange\Services\ExportPresetContainer;
use Plenty\Plugin\DataExchangeServiceProvider;

/**
 * Class ElasticExportGeizhalsDEServiceProvider
 * @package ElasticExportGeizhalsDE
 */
class ElasticExportGeizhalsDEServiceProvider extends DataExchangeServiceProvider
{
    /**
     * Abstract function for registering the service provider.
     */
    public function register()
    {

    }

    /**
     * Adds the export format to the export container.
     * @param ExportPresetContainer $container
     */
    public function exports(ExportPresetContainer $container)
    {
        $container->add(
            'GeizhalsDE-Plugin',
            'ElasticExportGeizhalsDE\ResultField\GeizhalsDE',
            'ElasticExportGeizhalsDE\Generator\GeizhalsDE',
            'ElasticExportGeizhalsDE\Filter\GeizhalsDE',
            true
        );
    }
}