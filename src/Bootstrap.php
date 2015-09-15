<?php

namespace vladdnepr\ycm\utils;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\web\GroupUrlRule;

class Bootstrap implements BootstrapInterface
{
    /** @inheritdoc */
    public function bootstrap($app)
    {
        /** @var $module Module */
        if ($app->hasModule('ycm-utils') && ($module = $app->getModule('ycm-utils')) instanceof Module) {
            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'vladdnepr\ycm\utils\commands';
            } else {
                $configUrlRule = [
                    'prefix' => $module->urlPrefix,
                    'rules' => $module->urlRules,
                ];

                if ($module->urlPrefix != 'ycm-utils') {
                    $configUrlRule['routePrefix'] = 'ycm-utils';
                }

                $app->urlManager->addRules([new GroupUrlRule($configUrlRule)], false);
            }
        }
    }
}
