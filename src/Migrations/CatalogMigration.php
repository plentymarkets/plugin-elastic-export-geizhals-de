<?php

namespace ElasticExportGeizhalsDE\Migrations;

use ElasticExportGeizhalsDE\Catalog\DataProviders\BaseFieldsDataProvider;
use ElasticExportGeizhalsDE\Catalog\Providers\CatalogTemplateProvider;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Catalog\Contracts\CatalogContentRepositoryContract;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Modules\Catalog\Contracts\TemplateContract;
use Plenty\Modules\DataExchange\Contracts\ExportRepositoryContract;
use Plenty\Modules\Item\Barcode\Contracts\BarcodeRepositoryContract;

/**
 * Class CatalogMigration
 *
 * @package ElasticExportGeizhalsDE\Migrations
 */
class CatalogMigration
{

    /** @var TemplateContainerContract */
    private $templateContainer;

    /** @var ExportRepositoryContract */
    private $exportRepository;

    /** @var CatalogContentRepositoryContract */
    private $catalogContentRepository;

    /** @var CatalogRepositoryContract */
    private $catalogRepositoryContract;

    public function __construct(
        TemplateContainerContract $templateContainer,
        ExportRepositoryContract $exportRepository,
        CatalogContentRepositoryContract $catalogContentRepository,
        CatalogRepositoryContract $catalogRepositoryContract
    )
    {
        $this->templateContainer = $templateContainer;
        $this->exportRepository = $exportRepository;
        $this->catalogContentRepository = $catalogContentRepository;
        $this->catalogRepositoryContract = $catalogRepositoryContract;
    }

    public function run()
    {
        $elasticExportFormats = $this->exportRepository->search(['formatKey' => 'GeizhalsDE-Plugin']);

        foreach($elasticExportFormats->getResult() as $format)
        {
            try
            {
                $this->updateCatalogData($format->name);
            }
            catch( \Exception $e )
            {
            }
        }
    }

    /**
     * @param $name
     *
     * @return bool
     * @throws \Exception
     */
    public function updateCatalogData($name)
    {
        // register the template
        $template = $this->registerTemplate();

        // create a new catalog
        $catalog = $this->create($name, $template->getIdentifier())->toArray();

        $data = [];
        $values = pluginApp(BaseFieldsDataProvider::class)->get();
        foreach($values as $value) {
            $dataProviderKey = utf8_encode($this->getDataProviderByIdentifier($value['key']));
            $data['mappings'][$dataProviderKey]['fields'][] = [
                'key' => utf8_encode($value['key']),
                'sources' => [
                    [
                        'fieldId' => utf8_encode($value['default']),
                        'key' => $value['fieldKey'],
                        'lang' => 'de',
                        'type' => $value['type'],
                        'id' => $value['id'],
                        //Fallback to be added
                    ]
                ]
            ];
        }

        // update created catalog data
        try
        {
            $this->catalogContentRepository->update($catalog['id'], $data);
        }
        catch(\Exception $e)
        {
        }

        return true;
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getDataProviderByIdentifier(string $identifier)
    {
        if (preg_match('/productDescription.attributes./', $identifier)) {
            return 'secondaryGeneral';
        }

        if(preg_match('/bulletPoints/', $identifier)) {
            return 'bulletPoints';
        }

        return 'general';
    }

    /**
     * @return TemplateContract
     */
    private function registerTemplate()
    {
        return $this->templateContainer->register(
            'ElasticExportGeizhalsDE',
            'ElasticExportGeizhalsDE',
            CatalogTemplateProvider::class
        );
    }

    /**
     * @param $name
     * @param $templateIdentifier
     *
     * @return mixed
     * @throws ValidationException
     */
    public function create($name ,$templateIdentifier)
    {
        return $this->catalogRepositoryContract->create(['name' => $name, 'template' => $templateIdentifier]);
    }

    /**
     *
     */
    public function barcode()
    {
        /** @var ExportRepositoryContract $testValue */
        $exportRepository = pluginApp(ExportRepositoryContract::class);
        $exportFormatSettings = $exportRepository->search(['formatKey' => 'GeizhalsDE-Plugin'], ['formatSettings']);

        /** @var BarcodeRepositoryContract $type */
        $barcodeRepository = pluginApp(BarcodeRepositoryContract::class);

        foreach($exportFormatSettings->getResult() as $exportFormatSetting) {
            $orderReferrerId = $exportFormatSetting->formatSettings->where('key', 'referrerId')->first()->value;
            $formatSettingsBarcode = $exportFormatSetting->formatSettings->where('key', 'barcode')->first()->value;

            if($orderReferrerId == '-1') {
                $barcodes = $barcodeRepository->allBarcodes()->getResult();
            } else {
                $barcodes = $barcodeRepository->findBarcodesByReferrerRelation($orderReferrerId);
            }
//            if($formatSettingsBarcode == 'FirstBarcode') {
//                $barcodeId = $barcodes->sortBy('id')->first()->id;
//            } else {
//                $barcodeId = $barcodes->where('plenty_item_barcode_type', $formatSettingsBarcode)->sortBy('id')->first()->id;
//            }
        }
    }
}