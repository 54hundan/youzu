<?php

namespace cp\vendor\youzu\UAuthComponent;

use Yii;
use yii\base\Component;
/**
 * 统一授权系统登录api
 * @author $Author: lvzw@uuzu.com $
 * @package common.components
 */
class UAuthComponent extends Component
{
	// private $loginUrl = 'http://localhost/yiiweb/login.uuzu.com/index.php/access/index';
	// private $getdataUrl = 'http://localhost/yiiweb/login.uuzu.com/index.php/access/getdata';
	private $loginUrl;
    private $logoutUrl;
	private $getDataUrl;
    // 记住登录状态
    private $loginStatus = null;
	public $appid;//授权站点id
	public $appkey;//授权站点key
	public $backurl;//登录认证通过后后回掉的地址

	public function init(){
		parent::init();
        $appId = urlencode(base64_encode($this->appid));
        $this->loginUrl = 'http://login.uuzu.com/access/index?appid='.$appId;
        $this->logoutUrl = 'http://login.uuzu.com/access/logout?appid='.$appId;
        $this->getDataUrl = 'http://login.uuzu.com/access/getData?appid='.$appId;
	}

	/**
	 * 加载登录验证
	 */
	public function login(){
        $backUrl = urlencode(base64_encode($this->backurl));
        yii\web\Controller::redirect($this->loginUrl . "&backurl=".$backUrl);
	}

    /**
     * 退出登录
     */
    public function logout() {
        $backUrl = urlencode(base64_encode(Yii::$app->request->referrer));
        //echo $backUrl;exit;
        yii\web\Controller::redirect($this->logoutUrl . "&backurl=".$backUrl);
    }

    /**
     * 检测用户是否登录成功
     * @return array | false
     */
    public function checkLoginStatus() {
        if($this->loginStatus === null) {
            $uc = Yii::$app->request->get('uc');
            //调用统一授权系统进行登录后数据获取
            $loginData = $this->getUserInfo($uc);
            $this->loginStatus = false;
            if($loginData['e'] == 100) {
                $this->loginStatus = $loginData['data'];
            }
        }
        return $this->loginStatus;
    }

    /**
     * 获取用户登录成功后返回的数据
     * @return array|false
     */
    public function getLoginData() {
        return $this->checkLoginStatus();
    }

    /**
	 * 处理认证后的返回信息
     * 由于兼容以前的系统调用，所以此方法没有改成private，请后续子系统不要直接调用这个方法判断是否登录. add by pandy at 2016-02-19
	 */
	public function getUserInfo($uc){
		$uc = trim($uc);
		$rtArr = array();
		if(!$uc){
			return array('e'=>-101,'msg'=>'传值错误','data'=>null);
		}

		$info = base64_decode($uc);
		if(strlen($info) < 32){
			return array('e'=>-101,'msg'=>'传值错误','data'=>null);
		}

		$keyStr = substr($info, 0,32);
		$datajson = substr($info, 32);
		if(strlen($datajson) < 2){
			return array('e'=>-101,'msg'=>'传值错误','data'=>null);
		}

		$data = json_decode($datajson);
		if(!$data || empty($data) || !isset($data->time) || !isset($data->PHPSESSID)){
			return array('e'=>-102,'msg'=>'非正确的uckey','data'=>null);
		}

		$checkKeyStr = md5($this->appkey.$data->time.$data->PHPSESSID);
		if($checkKeyStr != $keyStr){
			return array('e'=>-102,'msg'=>'非正确的uckey','data'=>null);
		}

		$putKeyStr = md5($this->appkey.$checkKeyStr);

		//获得用户信息
		$userDataUrl = $this->getDataUrl . "&uc=" . $putKeyStr;

		$userData = $this->curl_json_post($userDataUrl,'');
		$userData = json_decode($userData);
        // 记录日志
        //Yii::log("用户是否登录Url: ".$userDataUrl . "; 查询结果: ".var_export($userData,true), CLogger::LEVEL_INFO, __METHOD__);
		if(!isset($userData->result) || $userData->result !=1 || !isset($userData->data) || !isset($userData->data->username)){
			return array('e'=>-103,'msg'=>'获取用户信息失败','data'=>null);
		}
		return array('e'=>100,'msg'=>'','data'=>$userData);
	}

	/**
     * 进行json数据的post
     * @param string $data_url
     * @param string $data_string
     * @return string
     */
    public function curl_json_post($data_url,$data_string){
        $ch = curl_init($data_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)));
        $result = curl_exec($ch);
        return $result;
    }

    /**
     * 返回解析后的url
     * @param $url
     * @param array $data
     * @param bool $showHost 是否显示完整的Url，默认为false
     * @return string
     */
    public function getProcessedUrl($url, $data=array(), $showHost=false) {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path'])) {
            $parsed['path']='/';
        }
        $parsed['port'] = isset($parsed['port'])?':'.$parsed['port']:'';

        $result = $parsed['path'].$parsed['query'];
        if($showHost)
            $result = $parsed['scheme'].'://'.$parsed['host'].$parsed['port'].$parsed['path'].$parsed['query'];

        return $result;
    }
}