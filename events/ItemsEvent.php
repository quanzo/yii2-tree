<?php
namespace x51\yii2\modules\tree\events;

use \x51\yii2\modules\tree\Module;
use \yii\base\Event;

class ItemsEvent extends Event
{
    public $module;
    public $treeName;
    public $treeConfig;
    public $parentId;
    public $items;

    public function __construct(Module $module, $treeName, array $treeConfig, $parentId, array $items)
    {
        $this->module = $module;
        $this->treeName = $treeName;
        $this->treeConfig = $treeConfig;
        $this->parentId = $parentId;
        $this->items = $items;
        parent::__construct();
    }
} // end class
