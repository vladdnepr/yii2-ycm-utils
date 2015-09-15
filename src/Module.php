<?php

namespace vladdnepr\ycm\utils;

use Yii;

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
        'model/<action:\w+>/<name:\w+>/<pk:\d+>' => 'model/<action>',
        'model/<action:\w+>/<name:\w+>' => 'model/<action>',
        'model/<action:\w+>' => 'model/<action>'
    ];

    public $ycm;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->hasModule('ycm') || !($this->ycm = $this->getModule('ycm')) instanceof Module) {
            throw new \LogicException('Please set in config YCM module');
        }
    }
}
