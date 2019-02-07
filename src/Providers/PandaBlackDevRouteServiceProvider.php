<?php

namespace PandaBlackDev\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;

class PandaBlackDevRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     * @param ApiRouter $api
     */
    public function map(Router $router, ApiRouter $api)
    {
        //Authentication route
        $router->get('markets/panda-black/auth/authentication', 'PandaBlackDev\Controllers\AuthController@getAuthentication');

        $api->version(['v1'], ['middleware' => ['oauth']], function ($router) {
            $router->get('markets/panda-black/login-url', 'PandaBlackDev\Controllers\AuthController@getLoginUrl');
            $router->post('markets/panda-black/session', 'PandaBlackDev\Controllers\AuthController@sessionCreation');
            $router->get('markets/panda-black/expire-time', 'PandaBlackDev\Controllers\AuthController@tokenExpireTime');

            //Category Actions
            $router->get('markets/panda-black/parent-categories', 'PandaBlackDev\Controllers\CategoryController@all');
            $router->get('markets/panda-black/parent-categories/{id}', 'PandaBlackDev\Controllers\CategoryController@get');
            $router->get('markets/panda-black/vendor-categories', 'PandaBlackDev\Controllers\CategoryController@getPBCategories');
            $router->get('markets/panda-black/correlations', 'PandaBlackDev\Controllers\CategoryController@getCorrelations');
            $router->post('markets/panda-black/edit-correlations', 'PandaBlackDev\Controllers\CategoryController@updateCorrelation');
            $router->post('markets/panda-black/create-correlation', 'PandaBlackDev\Controllers\CategoryController@saveCorrelation');
            $router->delete('markets/panda-black/correlations/delete', 'PandaBlackDev\Controllers\CategoryController@deleteAllCorrelations');
            $router->delete('markets/panda-black/correlation/delete/{id}', 'PandaBlackDev\Controllers\CategoryController@deleteCorrelation');

            //Attribute Actions
            $router->post('markets/panda-black/create-attribute/{id}', 'PandaBlackDev\Controllers\AttributeController@createPBAttributes');
            $router->get('markets/panda-black/vendor-attribute/{categoryId}', 'PandaBlackDev\Controllers\AttributeController@getPBAttributes');

            //Sending Content Actions
            $router->post('markets/panda-black/products-data', 'PandaBlackDev\Controllers\ContentController@sendProductDetails');

            //Delete Properties
            $router->post('markets/panda-black/delete-properties', 'PandaBlackDev\Controllers\AttributeController@deletePBProperties');
        });
    }
}