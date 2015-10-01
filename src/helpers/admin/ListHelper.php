<?php

namespace vladdnepr\ycm\utils\helpers\admin;

use kartik\date\DatePicker;
use kartik\editable\Editable;
use kartik\grid\CheckboxColumn;
use kartik\grid\EditableColumn;
use kartik\select2\Select2;
use vladdnepr\ycm\utils\helpers\ModelHelper;
use vladdnepr\ycm\utils\helpers\RelationHelper;
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
        Editable::INPUT_CHECKBOX => 'boolean',
    ];

    public static function date(ActiveRecord $model, $attribute, $options = [])
    {
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

    public static function boolean(ActiveRecord $model, $attribute, $options = [])
    {
        $config = ArrayHelper::merge(
            [
                'class' => \kartik\grid\BooleanColumn::className(),
                'attribute' => $attribute,
                'trueLabel' => 'Yes',
                'falseLabel' => 'No',
            ],
            self::selectWidgetFilterConfig([0 => 'No', 1 => 'Yes'])
        );

        return ArrayHelper::merge(
            $config,
            $options
        );
    }

    public static function enumerate(ActiveRecord $model, $attribute, $options = [])
    {
        $choices = ModelHelper::getEnumChoices($model, $attribute);

        $config = ArrayHelper::merge(
            [
                'attribute' => $attribute,
            ],
            self::selectWidgetFilterConfig($choices)
        );

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

        $config = ArrayHelper::merge(
            [
                'label' => $label,
                'attribute' => $relation_name,
            ],
            self::selectWidgetFilterConfig(RelationHelper::getSelectChoices($model, $relation_name))
        );

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
        /* @var Module $module */
        $module = \Yii::$app->getModule('ycm-utils');
        // Init config
        $config = [
            'attribute' => $attribute,
            'class' => EditableColumn::className(),
            'editableOptions' => [
                'inputType' => $editable_type,
                'ajaxSettings'=>[
                    'url'=> Url::to([
                        '/ycm/model/editable',
                        'name' => $module->ycm->getModelName($model)
                    ]),
                ],
            ]
        ];

        // Add inner widget config if available
        if (isset(self::$widgetEditableTypeMap[$editable_type])) {
            $typeMethod = self::$widgetEditableTypeMap[$editable_type];
            $config['editableOptions']['options'] = self::$typeMethod($model, $attribute);
        }

        // Add relation config if available
        if ($model->getRelation($attribute, false)) {
            $config = ArrayHelper::merge(
                $config,
                self::relation($model, $attribute),
                self::editableRelationConfig($model, $attribute)
            );
        }


        // Check from db selectable type
        if ($choices = ModelHelper::getBooleanChoices($model, $attribute)) {
            $config = ArrayHelper::merge(
                $config,
                [
                    'editableOptions' => [
                        'options' => [
                            'data' => $choices,
                        ],
                        'displayValueConfig' => $choices,
                    ],
                ],
                self::selectWidgetFilterConfig($choices)
            );
        } elseif ($choices = ModelHelper::getEnumChoices($model, $attribute)) {
            $config = ArrayHelper::merge(
                $config,
                [
                    'editableOptions' => [
                        'inputType' => Editable::INPUT_SELECT2,
                        'options' => [
                            'data' => $choices,
                        ],
                        'displayValueConfig' => $choices,
                    ],
                ],
                self::selectWidgetFilterConfig($choices)
            );
        }

        return ArrayHelper::merge(
            $config,
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

        $modelChoices = ModelHelper::getSelectChoices($relationModel);

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
                                    '/ycm/model/choices',
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

    protected static function selectWidgetFilterConfig($choices)
    {
        return [
            'filterWidgetOptions' => [
                'data' => $choices,
                'pluginOptions' => [
                    'allowClear' => true,
                    'placeholder' => '✍'
                ],
            ],
            'filterType' => Select2::className()
        ];
    }
}
