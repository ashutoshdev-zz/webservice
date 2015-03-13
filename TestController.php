<?php
App::uses('AppController', 'Controller');
/**
 * Users Controller
 *
 * @property User $User
 * @property PaginatorComponent $Paginator
 * @property SessionComponent $Session
 */
class TestsController extends AppController {

	public $components = array('Paginator', 'Session');
        public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow(array('index'));
	}
	public function index() {
		$indexInfo['description'] = "App User Registration(post method)(2-d array) ";
		$indexInfo['url'] = FULL_BASE_URL.$this->webroot."users/app_registration";
		$indexInfo['parameters'] = 
                '
		<b>data[User][email] - </b> User email<br>
                <b>data[User][password] - </b>Password<br>
                ';
                $indexarr[] = $indexInfo;
                
                $indexInfo['description'] = "App User ForgetPwd(post method)(2-d array) ";
		$indexInfo['url'] = FULL_BASE_URL.$this->webroot."users/app_forgetpwd";
		$indexInfo['parameters'] = 
                '
		<b>data[User][email] - </b> User email<br>
                ';
                $indexarr[] = $indexInfo;
                
                 $indexInfo['description'] = "App user login";
		$indexInfo['url'] = FULL_BASE_URL.$this->webroot."users/app_login";
		$indexInfo['parameters'] = 
                '
		<b>data[User][username] - </b> User email<br>
                <b>data[User][password] - </b>Password<br>
                ';
                $indexarr[] = $indexInfo;
                
                
                $this->set('IndexDetail',$indexarr);
	}
}
