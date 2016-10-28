<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2016/2/20
 * Time: 11:48
 */
class LoginFilter extends CFilter {
    /**
     * 用户登录状态属性
     * 每个系统检测登录态的方式不同，所以通过该属性兼容
     * @var bool
     */
    public $loginStatus = false;

    protected function preFilter($filterChain) {
        // 已经登录的用户
        if ($this->loginStatus) {
            // 执行后续的过滤器
            $filterChain->run();
        } else {
            $request = Yii::app()->request;
            $path = $request->getUrl();
            $returnUrl = base64_encode($path);
            // 如果是ajax请求 就获取referrer的地址
            if($request->getIsAjaxRequest()){
                // 取ajax请求的页面Url
                $returnUrl = base64_encode($request->getUrlReferrer());
            }
            $backUrl = Yii::app()->UzAuthApi->getProcessedUrl(Yii::app()->UzAuthApi->backurl, array('returnUrl'=>$returnUrl),true);
            // 重置登录成功后的回调地址
            Yii::app()->UzAuthApi->backurl = $backUrl;
            //调用统一授权系统进行登录认证
            Yii::app()->UzAuthApi->login();
        }

    }
}