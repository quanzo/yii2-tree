<?php
/* @var $this yii\web\View */
/* @var $id string */
/* @var $tree string */
/* @var $treeName string */
/* @var $module_id string */
/* @var $url string */
?>
<div id="<?=$id?>" class="tree">
<?php
if ($findme) {
?>
<div class="findme"><input type="text" id="<?=$id?>_findme">
<button id="<?=$id?>_findme_btn"><?=Yii::t('module/tree', 'Find & check')?></button>
<button id="<?=$id?>_unfindme_btn"><?=Yii::t('module/tree', 'Find & uncheck')?></button>
<button id="<?=$id?>_uncheck_btn"><?=Yii::t('module/tree', 'Uncheck')?></button>
</div>
<?php
}
?>
<?=$tree?>
<?php
if ($urlDelete) {?>
    <button class="delete"><?=Yii::t('module/tree', 'Delete')?></button>
<?php }
if ($urlMove) {?>
    <button class="move"><?=Yii::t('module/tree', 'Move')?></button>
<?php } ?>
</div>

<?php
\x51\yii2\modules\tree\assets\Assets::register($this);
\x51\yii2\modules\tree\assets\AssetsCss::register($this);

$js = '(function () {';
$js .= 'var tree = new classTree({
    url: "' . $url . '",'
    . ($urlDelete ? 'urlDelete: "'.$urlDelete.'",' : '')
    . ($urlMove ? 'urlMove: "'.$urlMove.'",' : '')
    . ($urlBranch ? 'urlBranch: "'.$urlBranch.'",' : '') .'
    selector: "#'.$id.'",
    itemSelector: ".item",
    subitemSelector: ".subitems",
    timeout: 5,
    treeName: "'.$treeName.'",
	allowSelect: '.(isset($allowSelect) ? ($allowSelect ? 'true' : 'false') : 'true').',
});';
if ($findme) {
    $js .= '$("#'.$id.'_findme_btn").on("click", function () {
        let txt = document.getElementById("'.$id.'_findme").value;
            if (txt) {
                console.log(tree);
                tree.findme(txt);
            }
    });';
    $js .= '$("#' . $id . '_unfindme_btn").on("click", function () {
        let txt = document.getElementById("' . $id . '_findme").value;
            if (txt) {
                tree.unfindme(txt);
            }
    });';
    $js .= '$("#' . $id . '_uncheck_btn").on("click", function () {
        tree.unchecked();
    });';
}
// добавляем кнопки
// кнопка Delete
if ($urlDelete) {
    $js.='$("#'.$id.' button.delete").click(function (e) {tree.delete();});';
}
// кнопка Move
if ($urlMove) {
    $js.='$("#'.$id.' button.move").click(function (e) {tree.move();});';
}
// активный элемент
if (!empty($activeID)) {
    $js.='tree.choose('.$activeID.');';
}
// отмеченный элемент
if (!empty($checkedID)) {
    $js .= 'tree.checkIt(';
    if (is_array($checkedID)) {
        $js .= '['.implode(',', $checkedID).']';
    } else {
        $js .= '['.$checkedID.']';
    }
    $js .= ');';
}
$js .= '})();';

$this->registerJs($js);
