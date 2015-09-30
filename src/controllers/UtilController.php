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
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;

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

    public function actionEditable($name)
    {
        /** @var $model \yii\db\ActiveRecord */
        $model = $this->module->ycm->loadModel($name);

        if (!\Yii::$app->request->post('hasEditable')) {
            throw new BadRequestHttpException;
        }

        $output = '';
        $message = '';

        $model = $model->findOne(\Yii::$app->request->post('editableKey'));

        $modelShortName = $model->formName();
        $post = \Yii::$app->request->post($modelShortName);
        $modelAttributes = current($post);

        if ($model->load([$modelShortName => $modelAttributes])) {
            if (!$model->save()) {
                $message = Html::errorSummary($model);
            }
        }

        return Json::encode(['output' => $output, 'message' => $message]);
    }
}
