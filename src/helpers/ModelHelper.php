<?php

namespace vladdnepr\ycm\utils\helpers;

use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ModelHelper
{
    protected static $select_choices_cache;
    protected static $label_columns_cache;

    protected static $label_column_default = ['title', 'name', 'id'];

    /**
     * Get Choices
     * @param ActiveRecord $model
     * @return array Key - primary key value, value - label column value
     */
    public static function getSelectChoices(ActiveRecord $model)
    {
        $title_column_name = self::getLabelColumnName($model);
        $pk_column_name = self::getPkColumnName($model);

        $key = $model->className().$title_column_name.$pk_column_name;

        if (!isset(self::$select_choices_cache[$key])) {
            self::$select_choices_cache[$key] = ArrayHelper::map(
                $model->find()->orderBy($title_column_name . ' ASC')->all(),
                $pk_column_name,
                $title_column_name
            );
        }

        return self::$select_choices_cache[$key];
    }

    public static function getEnumChoices(ActiveRecord $model, $attribute)
    {
        $values = [];

        if (($columnSchema = $model->getTableSchema()->getColumn($attribute)) && $columnSchema->enumValues) {
            $values = array_combine(
                array_values($columnSchema->enumValues),
                array_map('ucfirst', $columnSchema->enumValues)
            );
        }

        return $values;
    }

    public static function getBooleanChoices(ActiveRecord $model, $attribute)
    {
        $values = [];

        if (($columnSchema = $model->getTableSchema()->getColumn($attribute))
            && strpos($columnSchema->dbType, 'tinyint(1)') !== false
        ) {
            $values = [
                0 => 'No',
                1 => 'Yes'
            ];
        }

        return $values;
    }

    /**
     * Get label column name
     * @param ActiveRecord $model
     * @return mixed
     */
    public static function getLabelColumnName(ActiveRecord $model)
    {
        $class = $model->className();

        if (!isset(self::$label_columns_cache[$class])) {
            $schema = $model->getTableSchema();
            $available_names = array_intersect(static::$label_column_default, $schema->getColumnNames());

            self::$label_columns_cache[$class] = reset($available_names);
        }

        return self::$label_columns_cache[$class];
    }

    /**
     * Get label column value
     * @param ActiveRecord $model
     * @return array|null
     */
    public static function getLabelColumnValue(ActiveRecord $model)
    {
        return $model->{self::getLabelColumnName($model)};
    }

    /**
     * Get PK column name
     * @param ActiveRecord $model
     * @return mixed
     */
    public static function getPkColumnName(ActiveRecord $model)
    {
        return $model->getTableSchema()->primaryKey[0];
    }

    /**
     * Get PK column value
     * @param ActiveRecord $model
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function getPkColumnValue(ActiveRecord $model)
    {
        return $model->{self::getPkColumnName($model)};
    }

    /**
     * Find model by label value
     * @param ActiveRecord $model
     * @param string $label
     * @return null|static
     */
    public static function findByLabel(ActiveRecord $model, $label)
    {
        /* @var ActiveRecord|static $this */
        return $model->findOne([self::getLabelColumnName($model) => $label]);
    }

    /**
     * Find choices by label value
     * @param ActiveRecord $model
     * @param string $label
     * @param int $limit
     * @return array
     */
    public static function findChoicesByLabel(ActiveRecord $model, $label, $limit = 20)
    {
        /* @var ActiveRecord|static $this */
        $pk_column = self::getPkColumnName($model);
        $label_column = self::getLabelColumnName($model);

        $query = new Query();
        $query->select($pk_column . ' as id, ' . $label_column .' AS text')
            ->from($model->tableName())
            ->where($label_column . ' LIKE "%' . $label .'%"')
            ->limit($limit);

        $command = $query->createCommand();

        return array_values($command->queryAll());
    }
}
