<?php
namespace x51\yii2\modules\tree;

use \x51\yii2\modules\tree\events\BeforeChangeEvent;
use \x51\yii2\modules\tree\events\Event;
use \x51\yii2\modules\tree\events\GetConfigEvent;
use \x51\yii2\modules\tree\events\ItemsEvent;
use \Yii;
use \yii\db\Query;
use \yii\helpers\Url;

/**
 * Модуль для вывода и управления структурой типа дерево
 * Дерево выводится не все сразу, а постепенно, по мере открытия узлов дерева.
 *
 * В шаблоне $itemTemplate элемента доступны:
 * {name} - значение поля для вывода
 * {id} - значение ключевого поля
 * {tree} - имя дерева
 * {имя поля в конфигурации} - содержимое поля из конфигурации
 * #имя поля в бд# - содержимое полей таблицы БД
 * может быть callable: function ($treeName, $parentId, $arParams) -> treeName - имя дерева в конфигурации; $parentId - id узла, элементы которого выводятся; $arParams - массив с параметрами для шаблона. Ф-ция должна вернуть сформированное содержимое. В результате может быть {subitems}
 *
 * В шаблоне $itemsTemplate всего дерева:
 * {items} - тело дерева, сформированного по шаблону $itemTemplate
 * {id} - значение ключевого поля элемента с которого начинаем вывод
 * {tree} - имя дерева (из конфига)
 * {имя поля в конфигурации} - содержимое поля из конфигурации
 *
 * Настройки в конфигурации дерева
 * tableName - имя таблицы в БД, например {{%posts}}
 * keyField - ключевое поле таблицы, например id
 * parentField - поле в таблицы, по которму формируется дерево, например post_parent,
 * nameField - поле в таблице, которое будет выводится на экран, например post_title, {name}
 * urlDelete - если == false, то операция будет запрещена
 * urlMove - если == false, то операция будет запрещена
 * params - дополнительные параметры для шаблонов. ключ - имя переменной, значение может быть callable -> function (array $cfgTree, array $item).
 * module - модуль типа posts
 * activeID - значение/замыкание для определения активного элемента дерева. В замыкание передается конфигурация дерева. Ф-ция должна вернуть ID элемента дерева. Этот активный элемент будет раскрыт и все ветки до него будут загружены.
 * checkedID - похоже на activeID. Может быть массивом или одним значением. Эти элементы будут помечены checkbox (если заданы в шаблоне). 
 *
 */
class Module extends \yii\base\Module
{
    /**
     * Настройки деревьев для вызова виджета
     * tree => [
     *  tableName - обязательно
     *  keyField - обязательно
     *  parentField - обязательно
     *  nameField - обязательно
     *  name - функция для формирования имени, function ($arItem)
     *  where
     *  orderBy
     *  urlDelete = false - запрет на кноку "Удалить"
     *  urlMove = false - запрет на кнопку "Переместить"
     *
     * ]
     * или
     * tree => [
     *  module - имя модуля типа posts
     *  name - функция для формирования имени, function ($arItem)
     *  where
     *  orderBy
     * ]
     *
     *
     *
     */
    const EVENT_BEFORE_CHANGE = 'treeBeforeChange';
    const EVENT_BEFORE_DELETE = 'treeBeforeDelete';
    const EVENT_GET_CONFIG = 'treeGetConfig';
    const EVENT_ITEMS = 'treeGetItems';

    protected $_tree;
    protected $_db;

    public $itemTemplate = '<li><input type="checkbox" value="{id}" name="sel[]"><span class="expand" data-id="{id}" data-tree="{tree}"><span class="item" data-id="{id}" data-tree="{tree}">{name}</span></span><span class="subitems">{subitems}</span></li>';
    public $itemsTemplate = '<ul data-tree="{tree}" data-id="{id}">{items}</ul>';
    public $showAllSubitems = false; // разрешает вывод всего дерева
    public $params = [ // дополнительные параметры для шаблонов
        /* */
    ];

    /**
     * true - вывод одного уровня дерева, false - вывод уровня и элементов подъуровней;
     * $showAllSubitems==true && $forceStopSubitems == false => выведет все дерево
     * $showAllSubitems==false && $forceStopSubitems == false => выведет ветку и только следующие подъуровни
     *
     * @var boolean
     */
    public $forceStopSubitems = false;

    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'x51\yii2\modules\tree\controllers';

    public function behaviors()
    {
        return [];
        /*return [
    'access' => [
    'class' => AccessControl::className(),
    'rules' => [
    [
    'allow' => true,
    'roles' => ['blocks_manage'],
    ],
    [
    'allow' => false,
    'roles' => ['?'],
    ],
    ],
    ],
    ];*/
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->_db = \Yii::$app->db;
        if (!isset($this->module->i18n->translations['module/tree'])) {
            $this->i18n->translations['module/tree'] = [
                'class' => '\yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US',
                'fileMap' => [
                    'module/tree' => 'messages.php',
                ],
            ];
        }
    }

    public function setTree(array $tree)
    {
        $this->_tree = $tree;
    }

    /**
     * Вывести дерево
     *
     * @param string $treeName - имя в конфигурации
     * @param int $startId - начать вывод с этого элемента
     * @param array $options - опции
     * @return string
     */
    public function widget($treeName, $startId, array $options = [])
    {
        $treeCfg = $this->getTreeCfg($treeName);
        $id = 'tree' . $this->getUniqId();

        if (!empty($options['showElementId'])) {

        }

        $params = [
            'id' => 'tree' . $this->getUniqId(),
            'tree' => $this->getItems($treeName, $startId, $this->forceStopSubitems),
            'module_id' => $this->id,
            'url' => Url::toRoute(['/' . $this->id . '/ajax/tree-level'], true),
            'urlDelete' => Url::toRoute(['/' . $this->id . '/ajax/delete'], true),
            'urlMove' => Url::toRoute(['/' . $this->id . '/ajax/move'], true),
            'urlBranch' => Url::toRoute(['/' . $this->id . '/ajax/branch'], true),
            'treeName' => $treeName,
            'allowSelect' => true,
            'findme' => false,
        ];
        $params2 = array_merge($params, $treeCfg, $options);
        // некоторые опции из treeCfg не должны быть изменены
        $arNoChange = ['module', 'keyField', 'parentField', 'nameField'];
        foreach ($arNoChange as $opt) {
            if (isset($options[$opt])) {
                if (isset($treeCfg[$opt])) {
                    $params2[$opt] = $treeCfg[$opt];
                } else {
                    unset($params2[$opt]);
                }
            }
        }

        // activeID
        if (!empty($params2['activeID'])) {
            if (is_callable($params2['activeID'])) {
                $f = $params2['activeID'];
                $params2['activeID'] = $f($treeCfg);
            }
        }

        // checkedID
        if (!empty($params2['checkedID'])) {
            if (is_callable($params2['checkedID'])) {
                $f = $params2['checkedID'];
                $params2['checkedID'] = $f($treeCfg);
            }
        }

        return \Yii::$app->view->renderFile($this->getViewPath() . '/tree.php', $params2);
    } // end widget

    public function getTreeCfg($treeName)
    {
        if (!empty($this->_tree[$treeName]) && is_array($this->_tree[$treeName])) {
            $cfg = $this->_tree[$treeName];
            if (!$cfg || isset($cfg['module'])) {
                $cfg['module'] = empty($cfg['module']) ? $treeName : $cfg['module'];
                $cfg['keyField'] = empty($cfg['keyField']) ? 'id' : $cfg['keyField'];
                $cfg['parentField'] = empty($cfg['parentField']) ? 'post_parent' : $cfg['parentField'];
                $cfg['nameField'] = empty($cfg['nameField']) ? 'post_title' : $cfg['nameField'];

                $module_class = Yii::$app->getModule($cfg['module']);
                if ($module_class && $module_class instanceof \x51\yii2\modules\posts\Module) {
                    $cfg['module_class'] = $module_class;
                } else {
                    throw new \Exception('Not found module ' . $cfg['module'] . ' or invalid class type');
                }
            } else {
                $required = ['tableName', 'keyField', 'parentField', 'nameField'];
                foreach ($required as $p) {
                    if (empty($cfg[$p])) {
                        throw new \Exception('Missing required parameter ' . $p . ' in tree ' . $treeName);
                    }
                }
            }

            $event = new GetConfigEvent($this, $treeName, $cfg);
            $this->trigger(self::EVENT_GET_CONFIG, $event);
            return $event->treeConfig;

        } else {
            throw new \Exception('Missing config in tree ' . $treeName);
        }
    } // end getTreeCfg

    /**
     * Список элементов уровня
     *
     * @param string $treeName - имя дерева из конфигурации модуля
     * @param int $parentId - начальный узел дерева
     * @param boolean $forceStopSubitems - отключить обработку {subitems} в последующих выводов
     * @return void
     */
    public function getItems($treeName, $parentId, $forceStopSubitems = true)
    {
        $cfgTree = $this->getTreeCfg($treeName);

        $arItems = $this->getItemsData($treeName, $parentId);

        if ($arItems) {
            $items = '';
            $arBaseParams = [];
            foreach ($cfgTree as $n => $v) {
                $arBaseParams['{' . $n . '}'] = $v;
            }

            // дополнительные параметры из конфига
            $ifCallableParams = false;
            if (!empty($this->params)) {
                foreach ($this->params as $n => $v) {
                    if (!is_callable($v)) {
                        $arBaseParams['{' . $n . '}'] = $v;
                    } else {
                        $ifCallableParams = true;
                    }
                }
            }

            $itemTemplate = $this->itemTemplate;
            if (is_callable($itemTemplate)) {
                $funcItemTemplate = $itemTemplate;
            }

            $ifCalcName = !empty($cfgTree['name']) && is_callable($cfgTree['name']);
            $funcCalcName = $ifCalcName ? $cfgTree['name'] : null;

            foreach ($arItems as $item) {
                $arParams = $arBaseParams;
                if ($ifCalcName) {
                    $arParams['{name}'] = $funcCalcName($item);
                } else {
                    $arParams['{name}'] = $item[$cfgTree['nameField']];
                }
                $arParams['{id}'] = $item[$cfgTree['keyField']];
                $arParams['{tree}'] = $treeName;
                // дополнительные параметры из конфига
                if ($ifCallableParams) {
                    foreach ($this->params as $n => $v) {
                        if (is_callable($v)) {
                            $arParams['{' . $n . '}'] = $v($cfgTree, $item);
                        }
                    }
                }

                foreach ($item as $n => $v) {
                    $arParams['#' . $n . '#'] = $v;
                }
                if (isset($funcItemTemplate)) {
                    $textItem = $funcItemTemplate($treeName, $parentId, $arParams);
                } else {
                    $textItem = strtr($itemTemplate, $arParams);
                }

                if (strpos($textItem, '{subitems}') !== false) {
                    if (!$forceStopSubitems) {
                        $textItem = strtr($textItem, [
                            '{subitems}' => $this->getItems($treeName, $arParams['{id}'], !$this->showAllSubitems),
                        ]);
                    } else {
                        $textItem = strtr($textItem, [
                            '{subitems}' => '',
                        ]);
                    }
                }

                $items .= $textItem;
            }
            $arParams = $arBaseParams;
            $arParams['{items}'] = $items;
            $arParams['{id}'] = $parentId;
            $arParams['{tree}'] = $treeName;

            $itemsTemplate = $this->itemsTemplate;
            if (is_callable($itemsTemplate)) {
                return $itemsTemplate($treeName, $parentId, $arParams);
            } else {
                return strtr($itemsTemplate, $arParams);
            }
        }
        return '';
    } // end getItems

    /**
     * Список элементов уровня (данные)
     *
     * @param string $treeName - имя дерева из конфигурации модуля
     * @param int $parentId - начальный узел дерева
     * @return array
     */
    public function getItemsData($treeName, $parentId)
    {
        $cfgTree = $this->getTreeCfg($treeName);
        if (!empty($cfgTree['module_class'])) {
            $arParams = [
                'asArray' => true,
                'blendMeta' => false,
                'onlyName' => false,
                'checkPermission' => ['read', 'readAll'],
                'where' => [],
                'orderBy' => [$cfgTree['keyField'] => 'ASC'],
            ];
            if (!empty($cfgTree['orderBy'])) {
                $arParams['orderBy'] = $cfgTree['orderBy'];
            }
            if (!empty($cfgTree['where'])) {
                $arParams['where'] = $cfgTree['where'];
            }
            $arParams['where'][$cfgTree['parentField']] = $parentId;

            if (
                (!isset($cfgTree['urlDelete']) || !$cfgTree['urlDelete']) ||
                (!isset($cfgTree['urlMove']) || !$cfgTree['urlMove'])
            ) {
                $arParams['cacheTime'] = 0;
            }
            $arItems = $cfgTree['module_class']->getList($arParams);
        } else {
            $query = new \yii\db\Query();
            $query->where([$cfgTree['parentField'] => $parentId])->from($cfgTree['tableName']);
            if (!empty($cfgTree['where'])) {
                $query->andWhere($cfgTree['where']);
            }
            if (!empty($cfgTree['orderBy'])) {
                $query->orderBy($cfgTree['orderBy']);
            } else {
                $query->orderBy([$cfgTree['nameField'] => SORT_ASC]);
            }
            $arItems = $query->all();
        }
        $event = new ItemsEvent($this, $treeName, $cfgTree, $parentId, $arItems);
        $this->trigger(self::EVENT_ITEMS, $event);
        return $event->items;
    } // end getItemsData

    /**
     * Сменить родительский элемент
     *
     * @param string $treeName - имя дерева из конфигурации
     * @param array $id - id элементов дерева
     * @param int|mixed $parentId - родительский элемент, который будет установлен для $id
     * @return boolean|int - кол-во измененных
     */
    public function changeParent($treeName, array $id, $parentId)
    {
        $cfgTree = $this->getTreeCfg($treeName);
        // urlDelete погашен
        if (isset($cfgTree['urlMove']) && !$cfgTree['urlMove']) {
            return 0;
        }

        if (!empty($cfgTree['module_class'])) {
            $cfgTree['tableName'] = '{{%' . $cfgTree['module_class']->getBaseTableName() . '}}';
        }

        $arId = array_filter($id, function ($v) use ($parentId) {
            return $v != $parentId;
        });

        if ($arId) {

            $changeEvent = new BeforeChangeEvent($this, $treeName, $cfgTree, $arId, $parentId);
            $this->trigger(self::EVENT_BEFORE_CHANGE, $changeEvent);

            if ($changeEvent->isValid && $changeEvent->id) {
                $transaction = $this->_db->beginTransaction();
                try {
                    $c = $this->_db->createCommand('UPDATE ' . $cfgTree['tableName'] . ' SET [[' . $cfgTree['parentField'] . ']] = :parentId WHERE [[' . $cfgTree['keyField'] . ']] IN (' . implode(',', $changeEvent->id) . ')');
                    $c->bindValues([
                        ':parentId' => $changeEvent->parentId,
                    ]);
                    $result = $c->execute();
                    $transaction->commit();
                    return $result;
                } catch (\Exception $e) {
                    $transaction->rollback();
                    throw $e;
                }
            }
        }
        return false;
    } // end changeParent

    /**
     * Удалить элементы
     *
     * @param string $treeName
     * @param array $arId
     * @return int|boolean
     */
    public function delete($treeName, array $arId)
    {
        $cfgTree = $this->getTreeCfg($treeName);

        // urlDelete погашен
        if (isset($cfgTree['urlDelete']) && !$cfgTree['urlDelete']) {
            return 0;
        }

        if (!empty($cfgTree['module_class'])) {
            $cfgTree['tableName'] = '{{%' . $cfgTree['module_class']->getBaseTableName() . '}}';
        }

        if ($arId) {

            $deleteEvent = new Event($this, $treeName, $cfgTree, $arId, 'delete');
            $this->trigger(self::EVENT_BEFORE_DELETE, $deleteEvent);

            if ($deleteEvent->isValid && $deleteEvent->id) {
                $transaction = $this->_db->beginTransaction();
                try {
                    // получим все удаляемые записи
                    //$this->_db->createCommand('SELECT '. $cfgTree['keyField'].', '.$cfgTree['parentField'].' FROM '.$cfgTree['tableName'].' WHERE [['. $cfgTree['keyField'].']] IN ('.implode(',', $arId).')');
                    $q = new Query();
                    $arDelRecord = $q->select([$cfgTree['keyField'], $cfgTree['parentField']])
                        ->from($cfgTree['tableName'])
                        ->where([$cfgTree['keyField'] => $arId])
                        ->indexBy($cfgTree['keyField'])
                        ->all();
                    // подготовим запрос для изменения родительских элементов у записей, которые являются подчиненными для удаляемых
                    $cUpd = $this->_db->createCommand('UPDATE ' . $cfgTree['tableName'] . ' SET [[' . $cfgTree['parentField'] . ']] = :parent WHERE [[' . $cfgTree['parentField'] . ']] = :oldparent');

                    foreach ($arDelRecord as $id => $row) {
                        // определим новый parent
                        $fid = $id;
                        while ($fid !== false) {
                            //var_dump($cfgTree['parentField'], $fid, $arDelRecord);die;

                            if (isset($arDelRecord[$fid])) {
                                $parent_id = $arDelRecord[$fid][$cfgTree['parentField']];
                                if (isset($arDelRecord[$parent_id])) {
                                    $fid = $arDelRecord[$parent_id][$cfgTree['parentField']];
                                } else {
                                    $fid = false;
                                }
                            } else {
                                $parent_id = $fid;
                                $fid = false;
                            }
                        }

                        $cUpd->bindValue(':parent', $parent_id)
                            ->bindValue(':oldparent', $id)
                            ->execute();
                    }
                    // удалим записи
                    $c = $this->_db->createCommand('DELETE FROM ' . $cfgTree['tableName'] . ' WHERE [[' . $cfgTree['keyField'] . ']] IN (' . implode(',', $arId) . ')');
                    $result = $c->execute();
                    $transaction->commit();
                    return $result;
                } catch (\Exception $e) {
                    $transaction->rollback();
                    throw $e;
                }
            }
        }
        return false;
    } // end changeParent

    /**
     * Возвращает ветку (путь) дерева от корня до $Id
     *
     * @param string $treeName
     * @param mixed $Id
     * @param boolean $endId
     * @param boolean $returnOnlyId - вернуть только значение id записей
     * @param boolean $includeId - включить $Id в результат
     * @return array
     */
    public function getBranch($treeName, $Id, $endId = false, $returnOnlyId = true, $includeId = true)
    {
        $arRes = [];

        $cfgTree = $this->getTreeCfg($treeName);
        if (!empty($cfgTree['module_class'])) {
            $keyField = $cfgTree['keyField'];
            $post = $cfgTree['module_class']->getPostModel($Id);
            $branchModels = $post->branch;
            if ($includeId) {
                $branchModels[] = $post;
            }
            if ($branchModels) {
                if ($endId !== false) {
                    $findEndId = false;
                    foreach ($branchModels as $i => $branchModel) {
                        if ($branchModel[$keyField] == $endId) {
                            $findEndId = $i;
                            break;
                        }
                    }
                    if ($findEndId !== false) {
                        $branchModels = array_slice($branchModels, $findEndId);
                    }
                }

                foreach ($branchModels as $branchModel) {
                    $id = $branchModel[$keyField];
                    if ($returnOnlyId) {
                        $arRes[] = $id;
                    } else {
                        $arRes[] = $branchModel->attributes;
                    }
                }
            }

        } else {

            $query = new \yii\db\Query();
            $query->from($cfgTree['tableName'])->limit(1);
            if (!empty($cfgTree['orderBy'])) {
                $query->orderBy($cfgTree['orderBy']);
            } else {
                $query->orderBy([$cfgTree['nameField'] => SORT_ASC]);
            }

            $parentId = $Id;

            do {
                $query->where([$cfgTree['keyField'] => $parentId]);
                if (!empty($cfgTree['where'])) {
                    $query->andWhere($cfgTree['where']);
                }
                $parent = $query->one();

                if ($parent) {
                    if ($returnOnlyId) {
                        $arRes[] = $parent[$cfgTree['keyField']];
                    } else {
                        $arRes[] = $parent;
                    }
                    $parentId = $parent[$cfgTree['parentField']];
                    if ($endId !== false && $parentId == $endId) {
                        $parent = false;
                    }
                }
            } while ($parent);
            // перевернуть
            $total = sizeof($arRes);
            $half = floor($total / 2);
            for ($i = 0; $i < $half; $i++) {
                $b = $arRes[$i];
                $arRes[$i] = $arRes[$total - $i - 1];
                $arRes[$total - $i - 1] = $b;
            }
        }

        return $arRes;
    } // end getBranch

    /**
     * Вернуть все элементы
     *
     * @param string $treeName
     * @param int $Id
     * @param boolean|int $endId
     * @return void
     */
    public function getTreeCluster($treeName, $Id, $endId = false)
    {

    } // end getTreeCluster

    protected function getUniqId()
    {
        static $uid = 0;
        return ++$uid;
    }

} // end class
