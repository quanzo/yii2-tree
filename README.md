Ajax output Adjacency List tree - module for Yii2
===========================

Install
-------

Install via Composer:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
composer require quanzo/yii2-tree
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

or add

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
"quanzo/yii2-tree" : "*"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

to the `require` section of your `composer.json` file.


Config
------

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
$config = [
...
    'modules' => [
...
        'tree' => [
            'class' => 'x51\yii2\modules\tree\Module',
            'tree' => [ // tree configs
                'category' => [
                    'tableName' => '{{%category}}',
                    'keyField' => 'id',
                    'parentField' => 'post_parent',
                    'nameField' => 'post_name',
                ],
                'articles' => [
                    'module' => 'articles',
                    //'where' => ['post_type'=>'category'],
                    'name' => function ($arItem) {
                        return '[' . $arItem['post_type'] . '] ' . $arItem['post_title'];
                    },
                ],
                'category' => [
                    'module' => 'articles',
                    'where' => ['post_type' => 'category'],
                ],
            ],
        ],
        // another definition
        'rubricator' => [
            'class' => '\x51\yii2\modules\tree\Module',
            'itemTemplate' => '<li><span class="expand" data-id="{id}" data-tree="{tree}">⊕</span><a href="{url}" class="item" data-id="{id}" data-tree="{tree}">{name}</a><span class="subitems">{subitems}</span></li>',
            'params' => [ // Extra options. see itemTemplate
                'url' => function ($cfg, $item) {
                    if (!empty($cfg['module'])) {
                        return yii\helpers\Url::to(['/articles/post/view', 'id' => $item['id']]);
                    } else {
                        return '##';
                    }
                },
            ],
            'tree' => [
                'articles' => [
                    'module' => 'articles',
                    'where' => ['post_type' => 'category'],
                    'urlDelete' => false,
                    'urlMove' => false,
                    'allowSelect' => false,
                    'activeID' => function ($cfg) {
                        if (!empty($_GET['id'])) {
                            return intval($_GET['id']);
                        }
                        return false;
                    },
                ],
            ],
        ],
...
    ],
...
];
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using
-----

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
echo \Yii::$app->getModule('tree')->widget('category');
// or
echo \Yii::$app->getModule('tree')->widget('category', $arAdvParams);
/* reassign parameters
$arAdvParams = [
    ...
    'urlDelete' => false,
    'urlMove' => false,
    'urlBranch' => false,
    ...
];
*/
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

 
