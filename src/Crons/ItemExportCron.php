<?php

namespace PandaBlackDev\Crons;

use PandaBlackDev\Controllers\ContentController;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

class ItemExportCron extends Cron
{
    public function __construct(ContentController $contentController)
    {
        $contentController->sendProductDetails();
    }
}