<?php

namespace vladdnepr\ycm\utils\helpers;

use kartik\select2\Select2;
use vladdnepr\ycm\utils\models\YcmModelUtilTrait;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class EditHelper
{
    public static function relation(ActiveRecord $model, $relation_name, $options = [])
    {
        /* @var ActiveRecord|YcmModelUtilTrait $model */
        $relation = $model->getRelation($relation_name);
        $relationField = $relation->multiple ? array_keys($relation->link)[0] : array_values($relation->link)[0];

        $config = [
            $relationField,
            'widget',
            'widgetClass' => Select2::className(),
            'data' => $model->getRelationChoices($relation_name),
            'hideSearch' => false,
            'options' => [
                'multiple' => $relation->multiple,
                'placeholder' => 'Select...'
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ]
        ];

        return ArrayHelper::merge(
            $config,
            $options
        );
    }
}