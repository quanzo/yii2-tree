<?php
namespace x51\yii2\modules\tree\events;

use \yii\base\Event as BaseEvent;
use \x51\yii2\modules\tree\Module;

class Event extends BaseEvent
{
    public $module;
    public $treeName;
    public $treeConfig;
    public $id;
    public $operation;
    public $isValid = true;

    public function __construct(Module $module, $treeName, array $treeConfig, array $id, $operation) {
        $this->module = $module;
        $this->treeName = $treeName;
        $this->treeConfig = $treeConfig;
        $this->id = $id;
        $this->operation = $operation;
        parent::__construct();
    }
} // end class
