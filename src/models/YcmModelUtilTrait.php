<?php
namespace vladdnepr\ycm\utils\models;

use kartik\editable\Editable;
use kartik\grid\EditableColumn;
use VladDnepr\TraitUtils\TraitUtils;
use vladdnepr\ycm\utils\Module;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;

trait YcmModelUtilTrait
{
    public $ajax_enable_threshold = 20;
    public $method_postfix_relation_choices = 'ChoicesIds';
    public $method_postfix_relation_ids = 'Ids';

    protected static $select_choices_cache;
    protected static $label_columns_cache;

    protected static $label_column_default = ['title', 'name', 'id'];

    /**
     * Get Choices
     * @return array Key - primary key value, value - label column value
     */
    public static function getSelectChoices()
    {
        $title_column_name = self::getLabelColumnName();
        $pk_column_name = self::getPkColumnName();
        /* @var ActiveRecord|static $this */
        $key = self::className().$title_column_name.$pk_column_name;

        if (!isset(self::$select_choices_cache[$key])) {
            self::$select_choices_cache[$key] = ArrayHelper::map(
                self::find()->orderBy($title_column_name . ' ASC')->all(),
                $pk_column_name,
                $title_column_name
            );
        }

        return self::$select_choices_cache[$key];
    }

    /**
     * Get label column name
     * @return mixed
     */
    public static function getLabelColumnName()
    {
        $class = get_called_class();

        if (!isset(self::$label_columns_cache[$class])) {
            /* @var TableSchema $schema */
            $schema = static::getTableSchema();
            $available_names = array_intersect(static::$label_column_default, $schema->getColumnNames());

            self::$label_columns_cache[$class] = reset($available_names);
        }

        return self::$label_columns_cache[$class];
    }

    /**
     * Get label column value
     * @return array|null
     */
    public function getLabelColumnValue()
    {
        return $this->{self::getLabelColumnName()};
    }

    /**
     * Get PK column name
     * @return mixed
     */
    public static function getPkColumnName()
    {
        /* @var ActiveRecord|static $this */
        return static::getTableSchema()->primaryKey[0];
    }

    /**
     * Find model by label value
     * @param $label
     * @return null|static
     */
    public function findByLabel($label)
    {
        /* @var ActiveRecord|static $this */
        return $this->findOne([self::getLabelColumnName() => $label]);
    }

    /**
     * Find choices by label value
     * @param $label
     * @param int $limit
     * @return array
     */
    public function findChoicesByLabel($label, $limit = 20)
    {
        /* @var ActiveRecord|static $this */
        $pk_column = self::getPkColumnName();
        $label_column = self::getLabelColumnName();

        $query = new Query();
        $query->select($pk_column . ' as id, ' . $label_column .' AS text')
            ->from($this->tableName())
            ->where($label_column . ' LIKE "%' . $label .'%"')
            ->limit($limit);

        $command = $query->createCommand();

        return array_values($command->queryAll());
    }

    protected function editableRelationConfig($relation_name)
    {
        /* @var ActiveRecord|static $this */
        $relation = $this->getRelation($relation_name);

        /* @var Module $module */
        $module = \Yii::$app->getModule('ycm-utils');

        /* @var ActiveRecord|YcmModelUtilTrait */
        $model = \Yii::createObject($relation->modelClass);

        $modelChoices = $model->getSelectChoices();

        /**
         * @todo #1 implement fill ajax loading with ajax mapping
         * @fixme #2 Relation is multiple, after live edit fix JS error `Cannot read property '[object Array]' of null`
         */
        return [
            'attribute' => $relation->multiple ? $relation_name . $this->method_postfix_relation_ids  : reset($relation->link),
            'label' => ucfirst($relation_name),
            'filter' => $modelChoices,
            'value' => $relation->multiple ?
                function ($model) use ($relation_name) {
                    return implode(
                        ', ',
                        array_values($model->{$relation_name . $this->method_postfix_relation_choices})
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
                    'pluginOptions' => count($modelChoices) > $this->ajax_enable_threshold ?
                        [
                            'minimumInputLength' => 3,
                            'ajax' => [
                                'url' => Url::to([
                                    '/ycm-utils/util/choices',
                                    'name' => $module->ycm->getModelName($model)
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

    protected function editableColumn($attribute, $options = [])
    {
        $config = [
            'attribute' => $attribute,
            'class' => EditableColumn::className(),
        ];

        /* @var ActiveRecord|static $this */
        if ($this->getRelation($attribute, false)) {
            $config = ArrayHelper::merge($config, $this->editableRelationConfig($attribute));
        }

        return ArrayHelper::merge(
            $config,
            $options
        );
    }

    /**
     ****************************************************
     * Below functionality about Editable Relations
     ****************************************************
     */

    protected $relationsChoices = [];

    /**
     * Check and get relation name if $name contain $postfix
     * @param $name
     * @param $postfix
     * @return null|string
     */
    protected function getRelationNameWithoutPostfix($name, $postfix)
    {
        $result = null;
        $pos = strpos($name, $postfix);

        if ($pos !== null) {
            $result = substr($name, 0, $pos);
        }

        return $result;
    }

    /**
     * Get available relation choices
     * @param $relation_name
     * @return mixed
     */
    public function getRelationChoices($relation_name)
    {
        if (!isset($this->relationsChoices[$relation_name])) {
            $this->relationsChoices[$relation_name] = [];

            $relation = $this->getRelation($relation_name, false);

            if ($relation) {
                $relation_class = $relation->modelClass;

                if (TraitUtils::contain($relation_class, 'vladdnepr\ycm\utils\models\YcmModelUtilTrait')) {
                    $this->relationsChoices[$relation_name] = ArrayHelper::map(
                        parent::__get($relation_name),
                        $relation_class::getPkColumnName(),
                        $relation_class::getLabelColumnName()
                    );
                }
            }
        }

        return $this->relationsChoices[$relation_name];
    }

    /**
     * Handle some magic properties of Select2
     * @inheritdoc
     * @param $name
     * @return array|mixed|null
     */
    public function __get($name)
    {
        $result = null;

        if (($relation_name = $this->getRelationNameWithoutPostfix($name, $this->method_postfix_relation_choices))
            /*|| ($relation_name = $this->getRelationNameWithoutPostfix($name, 'Choices'))*/
        ) {
            $result = $this->getRelationChoices($relation_name);
        } elseif ($relation_name = $this->getRelationNameWithoutPostfix($name, $this->method_postfix_relation_ids)) {
            $result = array_combine(
                array_keys($this->getRelationChoices($relation_name)),
                array_keys($this->getRelationChoices($relation_name))
            );

            $result = $result ?: null;
        } else {
            $result = parent::__get($name);
        }

        return $result;
    }

    /**
     * Relink MANY relations if it changed
     * @inheritdoc
     * @param $insert
     * @param $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            foreach ($this->relationsChoices as $relation_name => $ids) {
                $this->unlinkAll($relation_name, false);
            }
        }

        foreach ($this->relationsChoices as $relation_name => $ids) {
            $relation = $this->getRelation($relation_name);
            $relation_class = $relation->modelClass;

            foreach ($ids as $id) {
                /** @var $model \yii\db\ActiveRecord */
                $model = $relation_class::findOne($id);
                $this->link($relation_name, $model);
            }

        }

        parent::afterSave($insert, $changedAttributes);
    }


    /**
     * Handle MANY relations if changed
     * @param $name
     * @param $value
     */
    public function onUnsafeAttribute($name, $value)
    {
        if ($relation_name = $this->getRelationNameWithoutPostfix($name, $this->method_postfix_relation_ids)) {
            $this->relationsChoices[$relation_name] = array_combine(
                $value,
                $value
            );
        } else {
            parent::onUnsafeAttribute($name, $value); // TODO: Change the autogenerated stub
        }
    }
}
