<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2016/2/20
 * Time: 14:37
 */
class ValidateLoginStatusFilter extends CFilter {

    protected function preFilter($filterChain) {
        $request = Yii::app()->request;
        // 原址返回
        $returnUrl = trim($request->getParam('returnUrl'));
        // 登录成功的返回结果是一个array, 失败会返回false
        $loginData = Yii::app()->UzAuthApi->checkLoginStatus();
        // 登录失败
        if(!$loginData) {
            $backUrl = Yii::app()->UzAuthApi->backurl;
            // 原址返回不为空
            if ($returnUrl) {
                $backUrl = Yii::app()->UzAuthApi->getProcessedUrl($backUrl, array('returnUrl'=>$returnUrl),true);
            }
            // 重置登录成功后的回调地址
            Yii::app()->UzAuthApi->backurl = $backUrl;
            // 调用统一授权系统进行登录认证
            Yii::app()->UzAuthApi->login();
        }
        $filterChain->run();
    }
}