<?php
namespace x51\yii2\modules\tree\assets;

class Assets extends \yii\web\AssetBundle{
    public $sourcePath = __DIR__;
    public $js = [
        'js/script.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
} // end class
