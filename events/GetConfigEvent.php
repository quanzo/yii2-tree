<?php
namespace x51\yii2\modules\tree\events;

use \x51\yii2\modules\tree\Module;
use \yii\base\Event;

class GetConfigEvent extends Event
{
    public $module;
    public $treeName;
    public $treeConfig;

    public function __construct(Module $module, $treeName, array $treeConfig)
    {
        $this->module = $module;
        $this->treeName = $treeName;
        $this->treeConfig = $treeConfig;
        parent::__construct();
    }
} // end class
