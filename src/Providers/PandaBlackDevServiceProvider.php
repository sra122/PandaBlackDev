<?php // strict

namespace PandaBlackDev\Providers;

use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Cron\Services\CronContainer;
use PandaBlackDev\Crons\ItemExportCron;


class PandaBlackDevServiceProvider extends ServiceProvider
{
    /**
     * Register the core functions
     */
    public function register()
    {
        $this->getApplication()->register(PandaBlackRouteServiceProvider::class);
    }

    /**
     * @param CronContainer $container
     */
    public function boot(CronContainer $container)
    {
        $container->add(CronContainer::HOURLY, ItemExportCron::class);
    }
}
