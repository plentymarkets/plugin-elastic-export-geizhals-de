<?php

namespace ElasticExportGeizhalsDE\Crons;

use ElasticExportGeizhalsDE\Migrations\CatalogMigration;

/**
 * Class ExportCron
 *
 * @package ElasticExportGeizhalsDE\Crons
 */
class ExportCron
{
    /**
     * @param CatalogMigration $exportService
     */
    public function handle(CatalogMigration $exportService)
    {

        $exportService->run();

    }
}
