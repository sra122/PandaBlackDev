<?php
namespace PandaBlackDev\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;
use Plenty\Plugin\Http\Request;
class ContentController extends Controller
{
    /**
     * @return array
     */
    public function productDetails()
    {
        $itemRepository = pluginApp(VariationSearchRepositoryContract::class);

        $itemRepository->setSearchParams([
            'with' => [
                'item' => null,
                'lang' => 'de',
                'variationSalesPrices' => true,
                'variationCategories' => true,
                'variationClients' => true,
                'variationAttributeValues' => true,
                'variationSkus' => true,
                'variationMarkets' => true,
                'variationSuppliers' => true,
                'variationWarehouses' => true,
                'variationDefaultCategory' => true,
                'unit' => true,
                'variationStock' => [
                    'params' => [
                        'type' => 'virtual'
                    ],
                    'fields' => [
                        'stockNet'
                    ]
                ],
                'stock' => true,
                'images' => true,
            ]
        ]);

        $orderReferrerRepo = pluginApp(OrderReferrerRepositoryContract::class);
        $orderReferrerLists = $orderReferrerRepo->getList(['name', 'id']);

        $pandaBlackReferrerID = [];

        foreach($orderReferrerLists as $key => $orderReferrerList)
        {
            if(trim($orderReferrerList->name) === 'PandaBlack' && count($pandaBlackReferrerID) === 0) {
                array_push($pandaBlackReferrerID, $orderReferrerList);
            }
        }

        foreach($pandaBlackReferrerID as $pandaBlackId) {
            $itemRepository->setFilters([
                'referrerId' => (int)$pandaBlackId['id']
            ]);
        }


        $resultItems = $itemRepository->search();

        $items = [];
        $completeData = [];

        $settingsRepositoryContract = pluginApp(SettingsRepositoryContract::class);
        $categoryMapping = $settingsRepositoryContract->search(['marketplaceId' => 'PandaBlackDev', 'type' => 'category'], 1, 100)->toArray();

        $categoryId = [];

        foreach($categoryMapping['entries'] as $category) {
            $categoryId[$category->settings[0]['category'][0]['id']] = $category->settings;
        }

        $crons = $settingsRepositoryContract->search(['marketplaceId' => 'PandaBlackDev', 'type' => 'property'], 1, 100)->toArray();


        foreach($resultItems->getResult() as $key => $variation) {

            // Update only if products are updated in last 1 hour.
            if((time() - strtotime($variation['updatedAt'])) < 604800 && isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                if(isset($categoryId[$variation['variationCategories'][0]['categoryId']])) {

                    $variationStock = pluginApp(VariationStockRepositoryContract::class);
                    $stockData = $variationStock->listStockByWarehouse($variation['id']);

                    $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
                    $manufacturer = $manufacturerRepository->findById($variation['item']['manufacturerId'], ['*'])->toArray();

                    $textArray = $variation['item']->texts;
                    $variation['texts'] = $textArray->toArray();

                    $categoryMappingInfo = $categoryId[$variation['variationCategories'][0]['categoryId']];
                    $items[$key] = [$variation, $categoryId[$variation['variationCategories'][0]['categoryId']], $manufacturer];

                    $completeData[$key] = array(
                        'parent_product_id' => $variation['mainVariationId'],
                        'product_id' => $variation['id'],
                        'item_id' => $variation['itemId'],
                        'name' => $variation['item']['texts'][0]['name1'],
                        'price' => $variation['variationSalesPrices'][0]['price'],
                        'currency' => 'Euro',
                        'category' => $categoryMappingInfo[0]['vendorCategory'][0]['id'],
                        'short_description' => $variation['item']['texts'][0]['description'],
                        'image_url' => $variation['images'][0]['url'],
                        'color' => '',
                        'size' => '',
                        'content_supplier' => $manufacturer['name'],
                        'product_type' => '',
                        'quantity' => $stockData[0]['netStock'],
                        'store_name' => '',
                        'status' => $variation['isActive'],
                        'brand' => $manufacturer['name'],
                        'last_update_at' => $variation['updatedAt'],
                        'asin' => 'B07BB7GVK2'
                    );

                    $attributeSets = [];
                    foreach($variation['variationAttributeValues'] as $attribute) {

                        $attributeId = array_reverse(explode('-', $attribute['attribute']['backendName']))[0];
                        $attributeValue = array_reverse(explode('-', $attribute['attributeValue']['backendName']))[0];
                        $attributeSets[(int)$attributeId] = (int)$attributeValue;
                    }

                    $completeData[$key]['attributes'] = $attributeSets;
                }
            }
        }

        $templateData = array(
            'exportData' => $completeData,
            'completeData' => $items
        );
        return $templateData;
    }


    /**
     * @param SettingsRepositoryContract $settingRepo
     * @param LibraryCallContract $libCall
     * @return mixed
     */
    public function sendProductDetails()
    {
        $app = pluginApp(AppController::class);
        $productDetails = $this->productDetails();

        if(!empty($productDetails['exportData'])) {
            $app->authenticate('products_to_pandaBlack', null, $productDetails);
        }
    }

    /**
     * @return mixed
     */
    public function saveCronTime()
    {
        $settingRepo = pluginApp(SettingsRepositoryContract::class);

        $crons = $settingRepo->search(['marketplaceId' => 'PandaBlackDev', 'type' => 'property'], 1, 100)->toArray();

        foreach($crons as $key => $cron) {
            if(isset($crons['entries']['pbItemCron'])) {
                $cronData = [
                    'pbItemCron' => [
                        'pastCronTime' => $crons['entries']['pbItemCron']['presentCronTime'],
                        'presentCronTime' => time()
                    ]
                ];
                $response = $settingRepo->update($cronData, $key);
                return $response;
            }
        }

        $cronData = [
            'pbItemCron' => [
                'pastCronTime' => null,
                'presentCronTime' => time()
            ]
        ];

        $response = $settingRepo->create('PandaBlackDev', 'property', $cronData);

        return $response;
    }
}