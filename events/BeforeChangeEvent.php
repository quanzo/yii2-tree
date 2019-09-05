<?php
namespace x51\yii2\modules\tree\events;
use \x51\yii2\modules\tree\Module;
use x51\yii2\modules\tree\events\Event;

class BeforeChangeEvent extends Event
{
    public $parentId;   

    public function __construct(Module $module, $treeName, array $treeConfig, array $id, $parentId)
    {
        $this->parentId = $parentId;        
        parent::__construct($module, $treeName, $treeConfig, $id, 'change');
    }
} // end class
