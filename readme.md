		//调用统一认证登录
        'UzAuthApi' => array(
            'class' => 'vendor.youzu.UAuthComponent',
            'appid' => '12',	//@@@@对应项目
		    'appkey'=>'AeP6DNooogXYineyPLYAQq9fvjRsgSTk',//@@@@对应项目KEY
		    'backurl'=>'http://task.uuzu.com/index.php/login/login',//@@@@生产环境需修改
        ),



        Controller类里增加
        public function filters() {
            return array(
                array(
                    'vendor.youzu.UAuthComponent.LoginFilter - login',
                    // 业务系统判断用户是否登录的依据
                    'loginStatus'=>Yii::app()->user->id,
                ),
                array('vendor.youzu.UAuthComponent.ValidateLoginStatusFilter + login')
            );
        }


        LoginController类中
        /**
         * 处理认证后的返回信息
         */
        public function actionLogin(){
            // 原址返回
            $returnUrl = trim(Yii::app()->request->getParam('returnUrl'));
            $loginData = Yii::app()->UzAuthApi->getLoginData();
            /**  此处填写登录成功逻辑Start */

            // 给用户设置本系统的登录态

            /**  此处填写登录成功逻辑End */

            // 登录成功后原址返回
            if ($returnUrl) {
                $returnUrl = base64_decode($returnUrl);
                // 只去path和query部分，不取host，防止SSRF
                $this->redirect($this->getProcessedUrl($returnUrl));
            }
            $this->redirect('/');
        }

        public function actionLogout() {
            // 登出业务系统，清除登录态
            Yii::app()->user->logout();

            // 登出统一认证
            Yii::app()->UzAuthApi->logout();
        }