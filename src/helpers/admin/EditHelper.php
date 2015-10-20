<?php

namespace vladdnepr\ycm\utils\helpers\admin;

use kartik\select2\Select2;
use vladdnepr\ycm\utils\helpers\ModelHelper;
use vladdnepr\ycm\utils\helpers\RelationHelper;
use vladdnepr\ycm\utils\models\YcmModelUtilTrait;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class EditHelper
{
    public static function relation(ActiveRecord $model, $relation_name, $options = [])
    {
        /* @var ActiveRecord|YcmModelUtilTrait $model */
        $relation = $model->getRelation($relation_name);

        $config = [
            $relation_name,
            'widget',
            'widgetClass' => Select2::className(),
            'data' => RelationHelper::getSelectChoices($model, $relation_name),
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

    public static function boolean(ActiveRecord $model, $attribute, $options = [])
    {
        $config = [$attribute, 'checkbox'];

        return ArrayHelper::merge(
            $config,
            $options
        );
    }

    public static function enumerate(ActiveRecord $model, $attribute, $options = [])
    {
        $choices = ModelHelper::getEnumChoices($model, $attribute);

        $config = [
            $attribute,
            'widget',
            'widgetClass' => Select2::className(),
            'data' => $choices,
            'options' => [
                'placeholder' => 'Select...',
            ]
        ];

        return ArrayHelper::merge(
            $config,
            $options
        );
    }
}
