<?php

namespace x51\yii2\modules\tree\controllers;

use yii\web\Controller;
use \Yii;
use \yii\web\Response;

class AjaxController extends Controller
{
    /*public function behaviors()
    {
        return [
            'bootstrap' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/html' => \yii\web\Response::FORMAT_HTML,
                ],
            ],
        ];
    }*/

    public function actionIndex()
    {}

    public function actionTreeLevel($treeName, $parentId)
    {
        $result = $this->module->getItems($treeName, $parentId, $this->module->forceStopSubitems);
        return $result;
    }

    public function actionDelete($treeName, array $id)
    {
        $result = $this->module->delete($treeName, $id);
        return $result;
    }

    public function actionMove($treeName, array $id, $parentId)
    {
        $result = $this->module->changeParent($treeName, $id, $parentId);
        return $result;
    }

    public function actionBranch($treeName, $id) {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = $this->module->getBranch($treeName, $id, false, true);
        return $result;
    }

} // end class
