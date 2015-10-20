<?php
namespace vladdnepr\ycm\utils\models;

use vladdnepr\ycm\utils\helpers\ModelHelper;
use vladdnepr\ycm\utils\helpers\RelationHelper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

trait YcmModelUtilTrait
{
    public $ajax_enable_threshold = 20;
    public $method_postfix_relation_choices = 'IdsChoices';
    public $method_postfix_relation_ids = 'Ids';
    public $relations_delimeter = '.';

    protected static $relations_cache;

    protected $relationsIds = [];
    protected $relationsHasManyValues = [];

    /**
     * Check and get relation name if $name contain $postfix
     * @param $name
     * @param $postfix
     * @return null|string
     */
    protected function getAttributeNameWithoutPostfix($name, $postfix)
    {
        $result = null;
        $pos = strpos($name, $postfix);

        if ($pos !== null) {
            $result = substr($name, 0, $pos);
        }

        return $result;
    }

    /**
     * Get available relation ids
     * @param $relation_name
     * @return mixed
     */
    public function getRelationIds($relation_name)
    {
        /* @var ActiveRecord|static $this */
        if (!isset($this->relationsIds[$relation_name])) {
            $this->relationsIds[$relation_name] = [];

            $relation = $this->getRelation($relation_name);

            if ($relation) {
                $relationModel = new $relation->modelClass;

                $this->relationsIds[$relation_name] = ArrayHelper::map(
                    parent::__get($relation_name),
                    ModelHelper::getPkColumnName($relationModel),
                    ModelHelper::getPkColumnName($relationModel)
                );

            }
        }

        return $this->relationsIds[$relation_name];
    }

    /**
     * Handle some magic properties of Date, DateTime
     * @inheritdoc
     * @param $name
     * @return array|mixed|null
     */
    public function __get($name)
    {
        $result = null;

        if (strpos($name, $this->relations_delimeter) !== false) {
            // Relation access via dot
            $result = ArrayHelper::getValue(
                $this,
                $name,
                isset(self::$relations_cache[$name]) ? self::$relations_cache[$name] : null
            );
        } /*elseif (($relation_name = $this->getAttributeNameWithoutPostfix(
            $name,
            $this->method_postfix_relation_choices
        ))) {
            // Handle relation Choices
            $r=1;
            //$result = RelationHelper::getSelectChoices($this, $relation_name);
        }*/ /*elseif ($relation_name = $this->getAttributeNameWithoutPostfix($name, $this->method_postfix_relation_ids)) {
            // Handle relation Ids
            $result = $this->getRelationIds($relation_name) ?: null;
        }*/ else {
            $result = parent::__get($name);

            if (!$result && isset(self::$relations_cache[$name])) {
                $result = self::$relations_cache[$name];
            }
        }

        return $result;
    }

    /**
     * Relink ONE-MANY or MANY-MANY relations if it changed
     * @inheritdoc
     * @param $insert
     * @param $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            foreach ($this->relationsHasManyValues as $relation_name => $ids) {
                $this->unlinkAll($relation_name, false);
            }
        }

        foreach ($this->relationsHasManyValues as $relation_name => $ids) {
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
     * Handle relations via dot
     * Handle date field
     * Handle MANY relations if changed
     * @param $name
     * @param $value
     */
    public function onUnsafeAttribute($name, $value)
    {
        if (strpos($name, $this->relations_delimeter) !== false || $this->getRelation($name, false)) {
            self::$relations_cache[$name] = $value;
        } elseif (($relation_name = $this->getAttributeNameWithoutPostfix($name, $this->method_postfix_relation_ids))
            && $this->isRelationMultiple($relation_name)
        ) {
            $this->relationsHasManyValues[$relation_name] = $value;
        } else {
            parent::onUnsafeAttribute($name, $value);
        }
    }

    protected function isRelationMultiple($relation_name)
    {
        /* @var ActiveRecord|static $this */
        return $this->getRelation($relation_name)->multiple;
    }

    /**
     * Add access to nested relations via dot
     * @param $name
     * @param bool $throwException
     * @return mixed
     */
    public function getRelation($name, $throwException = true)
    {
        if (strpos($name, $this->relations_delimeter) !== false) {
            // Relation access via dot
            list($relation_name, $relation_other) = explode($this->relations_delimeter, $name, 2);
            /* @var ActiveQuery|null $relation */
            $relation = parent::getRelation($relation_name, $throwException);
            /* @var ActiveRecord|YcmModelUtilTrait $relationModel */
            $relationModel = new $relation->modelClass;
            $result = $relationModel->getRelation(trim($relation_other, $this->relations_delimeter), $throwException);
        } else {
            $result = parent::getRelation($name, $throwException);
        }

        return $result;
    }

    /**
     * Add attribute is active if it relation
     * @param $attribute
     * @return bool
     */
    public function isAttributeActive($attribute)
    {
        return $this->getRelation($attribute, false)
            || strpos($attribute, $this->relations_delimeter) !== false
            || parent::isAttributeActive($attribute);
    }

    function __toString()
    {
        /* @var ActiveRecord|static $this */
        return ModelHelper::getLabelColumnValue($this);
    }
}
