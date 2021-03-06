<?php

declare(strict_types=1);

namespace atk4\ui\Table\Column\FilterModel;

use atk4\ui\Table\Column;

class TypeBoolean extends Column\FilterModel
{
    public $noValueField = true;

    public function init(): void
    {
        parent::init();

        $this->op->values = ['true' => 'Is Yes', 'false' => 'Is No'];
        $this->op->default = 'true';
    }

    public function setConditionForModel($model)
    {
        $filter = $this->recallData();
        if (isset($filter['id'])) {
            $model->addCondition($filter['name'], $filter['op'] === 'true');
        }

        return $model;
    }
}
