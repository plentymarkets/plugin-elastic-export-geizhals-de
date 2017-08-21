<?php

namespace ElasticExportGeizhalsDE;

use Plenty\Modules\DataExchange\Services\ExportPresetContainer;
use Plenty\Plugin\ServiceProvider;

/**
 * Class ElasticExportGeizhalsDEServiceProvider
 * @package ElasticExportGeizhalsDE
 */
class ElasticExportGeizhalsDEServiceProvider extends ServiceProvider
{
    /**
     * Function for registering the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Adds the export format to the export container.
     *
     * @param ExportPresetContainer $container
     */
    public function boot(ExportPresetContainer $container)
    {
        $container->add(
            'GeizhalsDE-Plugin',
            'ElasticExportGeizhalsDE\ResultField\GeizhalsDE',
            'ElasticExportGeizhalsDE\Generator\GeizhalsDE',
            '',
            true,
            true
        );
    }
}