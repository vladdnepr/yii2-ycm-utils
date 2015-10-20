<?php


namespace vladdnepr\ycm\utils\behavior;

use yii\db\ActiveRecord;

class RelationsSetterBehavior extends BaseBehavior
{
    /**
     * Saved multiple relations data
     * @var array
     */
    protected $relations_multiple = [];

    /**
     * Map relation name to DB fields.
     * If relation not multiple - value is DB field name
     * @var array
     */
    protected $relations_to_fields_map = [];

    /**
     * Model relations info (getRelation method)
     * @var array
     */
    protected $relations_info = [];

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        if (method_exists($owner, 'attributeWidgets')) {
            foreach ($owner->attributeWidgets() as $attributeWidget) {
                if (isset($attributeWidget[1])
                    && $attributeWidget[1] == 'relation'
                    && $relation = $owner->getRelation($attributeWidget[0], false)
                ) {
                    $relation_name = $attributeWidget[0];

                    $this->relations_info[$relation_name] = $relation;
                    $this->rules[] = [[$relation_name], 'safe', 'on' => 'ycm-search'];
                    $this->rules[] = [[$relation_name], 'safe', 'on' => 'default'];

                    if (!$relation->multiple) {
                        $this->relations_to_fields_map[$relation_name] = reset($relation->link);
                    }
                }
            }
        }

        parent::attach($owner);
    }


    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, array_keys($this->relations_info));
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, array_keys($this->relations_info));
    }

    public function __set($name, $value)
    {
        if ($this->canSetProperty($name)) {
            if ($this->relations_info[$name]->multiple) {
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('For multiple relation pass array of ids');
                }
                $this->relations_multiple[$name] = $value;
            } else {
                $this->bindModelById($name, $value);
            }
        }
    }

    public function __get($name)
    {
        $result = null;

        if ($this->canGetProperty($name)
            && ($relation = $this->owner->getRelation($name))
            && !$relation->multiple
        ) {
            $result = $this->owner->{$this->relations_to_fields_map[$name]};
        }

        return $result;
    }

    public function __unset($name)
    {

    }

    public function __isset($name)
    {
        return isset($this->owner->$name);
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => [$this->owner, 'afterUpdate']
        ];
    }

    public function afterUpdate()
    {
        foreach ($this->relations_multiple as $relation_name => $relation_data) {
            foreach ($relation_data as $id) {
                $this->bindModelById($relation_name, $id);
            }
        }
    }

    protected function bindModelById($relation_name, $id)
    {
        /** @var $model \yii\db\ActiveRecord */
        /** @var $relation_class \yii\db\ActiveRecord */
        $relation_class = $this->owner->getRelation($relation_name, false)->modelClass;
        $model = $relation_class::findOne($id);
        $this->owner->link($relation_name, $model);
    }
}
