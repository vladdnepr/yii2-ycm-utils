<?php

namespace vladdnepr\ycm\utils\helpers;

use kartik\date\DatePicker;
use kartik\editable\Editable;
use kartik\grid\EditableColumn;
use kartik\select2\Select2;
use vladdnepr\ycm\utils\models\YcmModelUtilTrait;
use vladdnepr\ycm\utils\Module;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;

class ListHelper
{
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    static private $widgetEditableTypeMap = [
        Editable::INPUT_DATE => 'date',
        Editable::INPUT_DATETIME => 'date',
    ];

    public static function date(ActiveRecord $model, $attribute, $options = [])
    {
        /* @var ActiveRecord|YcmModelUtilTrait $model */

        $config = [
            'attribute' => $attribute,
            'format' => ['datetime', 'php:' . self::MYSQL_DATETIME_FORMAT],
            'options' => ['style' => 'width:240px'],
            'filterWidgetOptions' => [
                'type' => DatePicker::TYPE_RANGE,
                'attribute' => $attribute . '_from',
                'attribute2' => $attribute . '_to',
            ],
            'filterType' => DatePicker::className()
        ];

        return ArrayHelper::merge(
            $config,
            $options
        );
    }

    public static function relation(ActiveRecord $model, $relation_name, $options = [])
    {
        /* @var ActiveRecord|YcmModelUtilTrait $model */
        $label = strpos($relation_name, $model->relations_delimeter) !== false ?
            substr(
                $relation_name,
                strrpos($relation_name, $model->relations_delimeter) + strlen($model->relations_delimeter)
            ) :
            $relation_name;

        $config = [
            'label' => $label,
            'attribute' => $relation_name,
            'filterWidgetOptions' => [
                'data' => $model->getRelationChoices($relation_name),
                'pluginOptions' => [
                    'allowClear' => true,
                    'placeholder' => 'Select...'
                ],
            ],
            'filterType' => Select2::className()
        ];

        return ArrayHelper::merge(
            $config,
            $options
        );
    }

    public static function editable(
        ActiveRecord $model,
        $attribute,
        $editable_type = Editable::INPUT_TEXT,
        $options = []
    ) {
        $config = [
            'attribute' => $attribute,
            'class' => EditableColumn::className(),
            'editableOptions' => [
                'inputType' => $editable_type,
                /*'placement' => PopoverX::ALIGN_LEFT*/
            ]
        ];

        if (isset(self::$widgetEditableTypeMap[$editable_type])) {
            $typeMethod = self::$widgetEditableTypeMap[$editable_type];
            $config = ArrayHelper::merge(
                $config,
                self::$typeMethod($model, $attribute)
            );
        }

        /* @var ActiveRecord|YcmModelUtilTrait $model */
        if ($model->getRelation($attribute, false)) {
            $config = ArrayHelper::merge(
                $config,
                self::editableRelationConfig($model, $attribute)
            );
        }

        $editable_type_config = [];

        switch ($editable_type) {
            case Editable::INPUT_RADIO_LIST:
                $choices = $model->{$attribute . 'Choices'}();
                $editable_type_config = [
                    'editableOptions' => [
                        'options' => [
                            'itemOptions' => [
                                'class' => 'kv-editable-input'
                            ],
                            'data' => $choices,
                        ],
                        'data' => $choices,
                        'displayValueConfig' => $choices,
                    ],
                ];
                break;
        }

        return ArrayHelper::merge(
            $config,
            $editable_type_config,
            $options
        );
    }

    protected static function editableRelationConfig(ActiveRecord $model, $relation_name)
    {
        /* @var ActiveRecord|YcmModelUtilTrait $model */
        $relation = $model->getRelation($relation_name);

        /* @var Module $module */
        $module = \Yii::$app->getModule('ycm-utils');

        /* @var ActiveRecord|YcmModelUtilTrait $relationModel */
        $relationModel = \Yii::createObject($relation->modelClass);

        $modelChoices = $relationModel->getSelectChoices();

        /**
         * @todo #1 implement fill ajax loading with ajax mapping
         * @fixme #2 Relation is multiple, after live edit fix JS error `Cannot read property '[object Array]' of null`
         */
        return [
            'attribute' => $relation->multiple ?
                $relation_name . $model->method_postfix_relation_ids :
                reset($relation->link),
            'label' => ucfirst($relation_name),
            'filter' => $modelChoices,
            'value' => $relation->multiple ?
                function ($m) use ($relation_name, $modelChoices, $model) {
                    return implode(
                        ', ',
                        array_map(
                            function ($relation_id) use ($modelChoices) {
                                return $modelChoices[$relation_id];
                            },
                            array_values((array)$m->{$relation_name . $model->method_postfix_relation_ids})
                        )
                    );
                } :
                null,
            'editableOptions' => [
                'inputType' => Editable::INPUT_SELECT2,
                'size' => 'lg',

                'options' => [
                    'options' => [
                        'multiple' => $relation->multiple,
                    ],
                    'data' => $modelChoices,
                    'pluginOptions' => count($modelChoices) > $model->ajax_enable_threshold ?
                        [
                            'minimumInputLength' => 3,
                            'ajax' => [
                                'url' => Url::to([
                                    '/ycm-utils/util/choices',
                                    'name' => $module->ycm->getModelName($relationModel)
                                ]),
                                'dataType' => 'json',
                                'processResults' => new JsExpression(
                                    'function (results) { return results; }'
                                )
                            ],
                        ] :
                        null,
                ],
                'displayValueConfig' => !$relation->multiple ? $modelChoices : null,
            ],
        ];
    }
}
