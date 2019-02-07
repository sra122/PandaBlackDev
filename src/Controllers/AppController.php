<?php

namespace PandaBlackDev\Controllers;

use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Controller;
use Plenty\Modules\Market\Settings\Contracts\SettingsRepositoryContract;
class AppController extends Controller
{
    public function authenticate($apiCall, $params = null, $productDetails = null)
    {
        $libCall = pluginApp(LibraryCallContract::class);

        $propertiesRepo = pluginApp(SettingsRepositoryContract::class);
        $properties = $propertiesRepo->find('PandaBlackDev', 'property');

        foreach($properties as $key => $property)
        {
            if(isset($property->settings['pbToken'])) {

                if($property->settings['pbToken']['expires_in'] > time()) {

                    $response = $libCall->call(
                        'PandaBlackDev::'. $apiCall,
                        [
                            'token' => $property->settings['pbToken']['token'],
                            'category_id' => $params,
                            'product_details' => $productDetails
                        ]
                    );
                    $apiResponse = $response['Response'];
                } else if($property->settings['pbToken']['refresh_token_expires_in'] > time()) {

                    $response = $libCall->call(
                        'PandaBlackDev::pandaBlack_categories',
                        [
                            'token' => $property->settings['pbToken']['refresh_token'],
                            'category_id' => $params,
                            'product_details' => $productDetails
                        ]
                    );
                    $apiResponse = $response['Response'];
                }

                break;
            }
        }

        if(isset($apiResponse)) {
            return $apiResponse;
        }
    }
}