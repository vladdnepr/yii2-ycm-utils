<?php

namespace vladdnepr\ycm\utils;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\web\GroupUrlRule;
use janisto\ycm\Module as YcmModule;

class Bootstrap implements BootstrapInterface
{
    /** @inheritdoc */
    public function bootstrap($app)
    {
        /** @var $module Module */
        /** @var $ycm YcmModule */
        if ($app->hasModule('ycm')
            && ($ycm = $app->getModule('ycm')) instanceof YcmModule
            && $app->hasModule('ycm-utils')
            && ($module = $app->getModule('ycm-utils')) instanceof Module
        ) {
            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'vladdnepr\ycm\utils\commands';
            } else {
                $configUrlRule = [
                    'prefix' => $ycm->urlPrefix,
                    'rules' => $module->urlRules,
                ];

                if ($ycm->urlPrefix != 'ycm-utils') {
                    $configUrlRule['routePrefix'] = 'ycm-utils';
                }

                $app->urlManager->addRules([new GroupUrlRule($configUrlRule)]);
            }
        }
    }
}
