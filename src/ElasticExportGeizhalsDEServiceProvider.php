<?php

namespace ElasticExportGeizhalsDE;

use ElasticExportGeizhalsDE\Catalog\Providers\CatalogBootServiceProvider;
use ElasticExportGeizhalsDE\Crons\ExportCron;
use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\DataExchange\Services\ExportPresetContainer;
use Plenty\Plugin\ServiceProvider;

/**
 * Class ElasticExportGeizhalsDEServiceProvider
 *
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
        $this->getApplication()->register(CatalogBootServiceProvider::class);
    }

    /**
     * Adds the export format to the export container.
     *
     * @param ExportPresetContainer $container
     */
//    public function boot(ExportPresetContainer $container)
//    {
//        $container->add(
//            'GeizhalsDE-Plugin',
//            'ElasticExportGeizhalsDE\ResultField\GeizhalsDE',
//            'ElasticExportGeizhalsDE\Generator\GeizhalsDE',
//            '',
//            true,
//            true,
//        );
//    }

    public function boot(CronContainer $cronContainer)
    {
        // register crons
        $cronContainer->add(CronContainer::EVERY_FIFTEEN_MINUTES, ExportCron::class);
    }
}