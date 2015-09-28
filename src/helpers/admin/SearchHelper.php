<?php

namespace vladdnepr\ycm\utils\helpers\admin;

use vladdnepr\ycm\utils\helpers\ModelHelper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class SearchHelper
{
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function dateTime(ActiveQuery $query, ActiveRecord $model, $attribute)
    {
        self::range(
            $query,
            $model->tableName() . '.' . $attribute,
            $model->$attribute,
            $model->{$attribute . '_to'}
        );
    }

    public static function date(ActiveQuery $query, ActiveRecord $model, $attribute)
    {
        self::range(
            $query,
            $model->tableName() . '.' . $attribute,
            $model->$attribute ? (new \DateTime($model->$attribute))->format(self::MYSQL_DATETIME_FORMAT) : null,
            $model->{$attribute . '_to'} ? (new \DateTime($model->{$attribute . '_to'}))
                ->modify('+1 day')->format(self::MYSQL_DATETIME_FORMAT) : null
        );
    }

    public static function range(ActiveQuery $query, $attribute, $value_from, $value_to)
    {
        $query->andFilterWhere(['>=', $attribute, $value_from])
            ->andFilterWhere(['<=', $attribute, $value_to]);
    }

    public static function relation(ActiveQuery $query, ActiveRecord $model, $relation_name)
    {
        /* @var ActiveQuery $relation */
        $relation = $model->getRelation($relation_name);
        $relationClass = $relation->modelClass;
        $relationField = $relationClass::tableName() . '.' .
            ($relation->multiple ? array_values($relation->link)[0] : array_keys($relation->link)[0]);

        $relationValue = $model->$relation_name;

        if ($relationValue instanceof ActiveRecord) {
            $relationValue = ModelHelper::getPkColumnValue($relationValue);
        }

        $query->joinWith($relation_name)
            ->andFilterWhere([$relationField => $relationValue]);
    }

    public static function like(ActiveQuery $query, ActiveRecord $model, $attribute)
    {
        $query->andFilterWhere(['like', $model->tableName() .'.' . $attribute, $model->$attribute]);
    }

    public static function equal(ActiveQuery $query, ActiveRecord $model, $attribute)
    {
        $query->andFilterWhere([$attribute => $model->$attribute]);
    }
}
