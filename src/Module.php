<?php

namespace vladdnepr\ycm\utils;

use Yii;
use janisto\ycm\Module as YcmModule;

/**
 * Main module class for yii2-ycm-utils.
 *
 * You can modify its configuration by adding an array to your application config under `modules`
 * as shown in the following example:
 *
 * 'modules' => [
 *     ...
 *     'ycm-utils' => [
 *         'class' => 'vladdnepr\ycm-utils\Module',
 *     ],
 *     ...
 * ]
 *
 * @author Vladislav Lyshenko <vladdnepr1989@gmail.com>
 * @license public domain (http://unlicense.org)
 * @link https://github.com/vladdnepr/yii2-ycm-utils
 */
class Module extends \yii\base\Module
{
    /** @inheritdoc */
    public $controllerNamespace = 'vladdnepr\ycm\utils\controllers';

    /** @var array The default URL rules to be used in module. */
    public $urlRules = [
        '' => 'default/index',
        'util/<action:\w+>/<name:\w+>/<pk:\d+>' => 'util/<action>',
        'util/<action:\w+>/<name:\w+>' => 'util/<action>',
        'util/<action:\w+>' => 'util/<action>'
    ];

    /**
     * @var YcmModule
     */
    public $ycm;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->module->hasModule('ycm')
            || !($this->ycm = $this->module->getModule('ycm')) instanceof YcmModule
        ) {
            throw new \LogicException('Please set in config YCM module');
        }
    }
}
