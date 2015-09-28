<?php
namespace vladdnepr\ycm\utils\models;

use VladDnepr\TraitUtils\TraitUtils;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

trait YcmModelUtilTrait
{
    public $ajax_enable_threshold = 20;
    public $method_postfix_relation_choices = 'IdsChoices';
    public $method_postfix_relation_ids = 'Ids';
    public $relations_delimeter = '.';

    protected static $select_choices_cache;
    protected static $label_columns_cache;

    protected static $label_column_default = ['title', 'name', 'id'];

    protected static $dates_to_cache;

    protected static $relations_cache;

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

    /**
     ****************************************************
     * Below functionality about Editable Relations
     ****************************************************
     */

    protected $relationsIds = [];
    protected $relationsChoices = [];
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
     * Get available relation choices
     * @param $relation_name
     * @return mixed
     */
    public function getRelationChoices($relation_name)
    {
        /* @var ActiveRecord|static $this */
        if (!isset($this->relationsChoices[$relation_name])) {
            $this->relationsChoices[$relation_name] = [];

            $relation = $this->getRelation($relation_name, false);

            if ($relation) {
                $relation_class = $relation->modelClass;

                if (TraitUtils::contain($relation_class, 'vladdnepr\ycm\utils\models\YcmModelUtilTrait')) {
                    $this->relationsChoices[$relation_name] = $relation_class::getSelectChoices();
                }
            }
        }

        return $this->relationsChoices[$relation_name];
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

            $relation = $this->getRelation($relation_name, false);

            if ($relation) {
                $relation_class = $relation->modelClass;

                if (TraitUtils::contain($relation_class, 'vladdnepr\ycm\utils\models\YcmModelUtilTrait')) {
                    $this->relationsIds[$relation_name] = ArrayHelper::map(
                        parent::__get($relation_name),
                        $relation_class::getPkColumnName(),
                        $relation_class::getPkColumnName()
                    );
                }
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

        if ($attrubute = $this->getAttributeNameWithoutPostfix($name, '_to')) {
            //Date and DateTime handle
            $result = isset(self::$dates_to_cache[$attrubute]) ? self::$dates_to_cache[$attrubute] : null;
        } elseif (strpos($name, $this->relations_delimeter) !== false) {
            // Relation access via dot
            $result = ArrayHelper::getValue(
                $this,
                $name,
                isset(self::$relations_cache[$name]) ? self::$relations_cache[$name] : null
            );
        } elseif (($relation_name = $this->getAttributeNameWithoutPostfix(
            $name,
            $this->method_postfix_relation_choices
        )) || ($relation_name = $this->getAttributeNameWithoutPostfix($name, 'Choices'))
        ) {
            // Handle relation Choices
            $result = $this->getRelationChoices($relation_name);
        } elseif ($relation_name = $this->getAttributeNameWithoutPostfix($name, $this->method_postfix_relation_ids)) {
            // Handle relation Ids
            $result = $this->getRelationIds($relation_name) ?: null;
        } else {
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
        } elseif ($attrubute = $this->getAttributeNameWithoutPostfix($name, '_to')) {
            self::$dates_to_cache[$attrubute] = $value;
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
        return $this->getLabelColumnValue();
    }
}
