<?php

namespace vladdnepr\ycm\utils\helpers;

use yii\db\ActiveRecord;

class RelationHelper
{
    protected static $relationsChoices = [];
    protected static $relationsIds = [];

    /**
     * Get available relation choices
     * @param $relation_name
     * @return mixed
     */
    public static function getSelectChoices(ActiveRecord $model, $relation_name)
    {
        $class = $model->className();

        if (!isset(self::$relationsChoices[$class][$relation_name])) {
            self::$relationsChoices[$class][$relation_name] = [];

            $relation = $model->getRelation($relation_name, false);

            if ($relation) {
                self::$relationsChoices[$class][$relation_name] =
                    ModelHelper::getSelectChoices(new $relation->modelClass);
            }
        }

        return self::$relationsChoices[$class][$relation_name];
    }
}
