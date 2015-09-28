<?php

namespace vladdnepr\ycm\utils\controllers;

use vladdnepr\ycm\utils\helpers\ModelHelper;
use vladdnepr\ycm\utils\models\YcmModelUtilTrait;
use vladdnepr\ycm\utils\Module;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use VladDnepr\TraitUtils\TraitUtils;

/**
 * Class ModelController
 * @property Module $module
 * @package vladdnepr\ycm\utils\controllers
 */
class UtilController extends Controller
{
    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['choices'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return in_array(Yii::$app->user->identity->username, $this->module->ycm->admins);
                        }
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [],
            ],
        ];
    }

    public function actionChoices($name, $q = null, $id = null)
    {
        $out = ['results' => ['id' => '', 'text' => '']];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        /* @var YcmModelUtilTrait|ActiveRecord $model */
        $model = $this->module->ycm->loadModel($name);

        if (!TraitUtils::contain($model, 'vladdnepr\ycm\utils\models\YcmModelUtilTrait')) {
            throw new NotSupportedException('Model must implement YcmModelUtilTrait');
        }

        if (!is_null($q)) {
            $out['results'] = ModelHelper::findChoicesByLabel($model, $q);
        } elseif ($id > 0) {
            $out['results'] = [
                ModelHelper::getPkColumnName($model) => $id,
                'text' => ModelHelper::getLabelColumnName($model->findOne($id))
            ];
        }
        return $out;
    }

    protected function usedTrait($object, $expected_trait_class)
    {
        $traits = [];

        do {
            $traits = array_merge($traits, class_uses($object));
        } while ($object = get_parent_class($object));

        return isset($traits[$expected_trait_class]);
    }
}
