<?php
App::uses('AppController', 'Controller');
/**
 * Users Controller
 *
 * @property User $User
 * @property PaginatorComponent $Paginator
 * @property SessionComponent $Session
 */
class UsersController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'Session','Resize');
        
        public function beforeFilter(){
            parent::beforeFilter();
            $this->Auth->allow(array('admin_login','admin_forgetpwd','admin_resetpwd','admin_add','verify','resetpwd','app_login','app_registration'
                ,'app_edit','app_user','app_alluser','app_forgetpwd','app_additem'));
        }

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->User->recursive = 0;
		$this->set('users', $this->Paginator->paginate());
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		$options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
		$this->set('user', $this->User->find('first', $options));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->User->create();
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(__('The user has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
			}
		}
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(__('The user has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
			$this->request->data = $this->User->find('first', $options);
		}
	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->User->delete()) {
			$this->Session->setFlash(__('The user has been deleted.'));
		} else {
			$this->Session->setFlash(__('The user could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}
        
        
/**
 * Set Cookies method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */        
        protected function _setCookie($id) {
		if (!$this->request->data('User.remember_me')) {
			return false;
		}
		$data = array(
			'username' => $this->request->data('User.username'),
			'password' => $this->request->data('User.password')
		);
		$this->Cookie->write('User', $data, true, '+2 week');
		return true;
	}
/**
 * admin_login method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */          
        public function admin_login() {
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$this->_setCookie($this->Auth->user('id'));
				//return $this->redirect($this->Auth->redirect());
				return $this->redirect(array('action'=>'index'));
			} else {
				$this->Session->setFlash(__('Invalid username or password, try again'));
			}
		}
		if ($this->Auth->loggedIn() || $this->Auth->login()) {
                        $this->Session->write('User.adid', $this->Auth->user('id'));
			$this->redirect(array('controller'=>'users','action'=>'index'));
		}
	}
/**
 * admin_logout method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */           
        public function admin_logout() {
		$this->Auth->logout();
                $this->Cookie->delete('User');
                $this->redirect(array('controller'=>'users','action'=>'login'));
	}
/**
 * admin_changepwd method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */   
        
        public function admin_changepass($id = null){
	      if ($this->request->is('post')) {		
		   $password =AuthComponent::password($this->data['User']['old_password']);	
                   $em= $this->Auth->user('email');
		   $pass=$this->User->find('first',array('conditions'=>
                       array('AND'=>array('User.password'=>$password,'User.email' => $em))));
		   if($pass){
			  if($this->data['User']['new_password'] != $this->data['User']['cpassword'] ){
				$this->Session->setFlash("New password and Confirm password field do not match");		  
			  }
			  else {  
                                $this->User->data['User']['password'] = $this->data['User']['new_password'];
                                $this->User->id = $pass['User']['id'];
                                if($this->User->exists()){
                                        $pass['User']['password'] = $this->data['User']['new_password'];
                                        if($this->User->save($this->request->data)) {
                                          $this->Session->setFlash("Password updated");
                                          $this->redirect(array('controller'=>'users','action' => 'index'));
                                        }
                                }
			   }
		   }
		   else{
			   $this->Session->setFlash("Your old password did not match.");
		   }
	      }
        }
        
        
/**
 * admin_forgetpwd method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */           
        public function admin_forgetpwd() {
            $this->User->recursive=-1;
            if(!empty($this->data)){
                if(empty($this->data['User']['email'])){
                     $this->Session->setFlash('Please Provide Your Email Address that You used to Register with Us');
                }else{
                    $email=$this->data['User']['email'];
                    $fu=$this->User->find('first',array('conditions'=>array('User.email'=>$email)));
                    if($fu){
                        if($fu['User']['status']=="1"){
                                $key = Security::hash(String::uuid(),'sha512',true);
                                $hash=sha1($fu['User']['email'].rand(0,100));
                                $url = Router::url( array('controller'=>'Users','action'=>'reset'), true ).'/'.$key.'#'.$hash;
                                $ms="<p>Click the Link below to reset your password.</p><br /> ".$url;
                                $fu['User']['tokenhash']=$key;
                                $this->User->id=$fu['User']['id'];
                                if($this->User->saveField('tokenhash',$fu['User']['tokenhash'])){
                                        $l = new CakeEmail('smtp');
                                        $l->emailFormat('html')->template('default','default')->subject('Reset Your Password')
                                                ->to($fu['User']['email'])->send($ms);
                                        $this->set('smtp_errors', "none");
                                        $this->Session->setFlash(__('Check Your Email To Reset your password', true));
                                        $this->redirect(array('controller' => 'Users','action' => 'login'));
                                }
                                else{
                                        $this->Session->setFlash("Error Generating Reset link");
                                }
                        }
                        else{
                                $this->Session->setFlash('This Account is not Active yet.Check Your mail to activate it');
                        }
                    }
                    else{
                            $this->Session->setFlash('Email does Not Exist');
                    }
                }
            }
      }

/**
 * admin_resetpwd method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */        
    public function admin_resetpwd($token=null) {
         configure::write('debug',2);
	     $this->User->recursive=-1;
	     if(!empty($token)){
                $u=$this->User->findBytokenhash($token);
                if($u){
                    $this->User->id=$u['User']['id'];
                    if(!empty($this->data)){
                            if($this->data['User']['password'] != $this->data['User']['password_confirm']){
                                $this->Session->setFlash("Both the passwords are not matching...");
                                return;
                            }
                            $this->User->data=$this->data;
                            $this->User->data['User']['email']=$u['User']['email'];
                            $new_hash=sha1($u['User']['email'].rand(0,100));//created token
                            $this->User->data['User']['tokenhash']=$new_hash;
                            if($this->User->validates(array('fieldList'=>array('password','password_confirm')))){
                                    if($this->User->save($this->User->data))
                                    {
                                            $this->Session->setFlash('Password Has been Updated');
                                            $this->redirect(array('controller' => 'Users','action' => 'login'));
                                    }
                            }
                            else{
                            $this->set('errors',$this->User->invalidFields());
                            }
                    }
                }else{
                $this->Session->setFlash('Token Corrupted, Please Retry.the reset link 
                        <a style="cursor: pointer; color: rgb(0, 102, 0); text-decoration: none;
                        background: url("http://files.adbrite.com/mb/images/green-double-underline-006600.gif") 
                        repeat-x scroll center bottom transparent; margin-bottom: -2px; padding-bottom: 2px;"
                        name="AdBriteInlineAd_work" id="AdBriteInlineAd_work" target="_top">work</a> only for once.');
                }
             }else{
                  $this->Session->setFlash('Pls try again...');
                  $this->redirect(array('controller' => 'pages','action' => 'login'));
             }
	}
        
        
        
        /**
 * admin_resetpwd method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */        
    public function resetpwd($token=null) {
         configure::write('debug',2);
	     $this->User->recursive=-1;
	     if(!empty($token)){
                $u=$this->User->findBytokenhash($token);
                $current_time=date("Y-m-d H:i:s");
                $tokenhash_time=date('Y-m-d H:i:s',strtotime($u['User']['tokenhash_time'] . "+1 days"));
                
                if($current_time>$tokenhash_time){
                    $this->Session->setFlash('Time expire reset your password again');
                    $this->redirect('/');
                }
                if($u){
                    $this->User->id=$u['User']['id'];
                    if(!empty($this->data)){
                            if($this->data['User']['password'] != $this->data['User']['password_confirm']){
                                $this->Session->setFlash("Both the passwords are not matching...");
                                return;
                            }
                            
                            if($this->User->validates(array('fieldList'=>array('password','password_confirm')))){
                                    $this->User->data=$this->data;
                                    $this->User->data['User']['email']=$u['User']['email'];
                                    $new_hash=sha1($u['User']['email'].rand(0,100));//created token
                                    $this->User->data['User']['tokenhash']=$new_hash;
                                    if($this->User->save($this->User->data))
                                    {
                                            $this->Session->setFlash('Password Has been Updated');
                                            $this->redirect(array('controller' => 'Users','action' => 'login'));
                                    }
                            }
                            else{
                            $this->set('errors',$this->User->invalidFields());
                            }
                    }
                }else{
                $this->Session->setFlash('Token Corrupted, Please Retry.the reset link 
                        <a style="cursor: pointer; color: rgb(0, 102, 0); text-decoration: none;
                        background: url("http://files.adbrite.com/mb/images/green-double-underline-006600.gif") 
                        repeat-x scroll center bottom transparent; margin-bottom: -2px; padding-bottom: 2px;"
                        name="AdBriteInlineAd_work" id="AdBriteInlineAd_work" target="_top">work</a> only for once.');
                }
             }else{
                  $this->Session->setFlash('Pls try again...');
                  $this->redirect(array('controller' => 'pages','action' => 'login'));
             }
	}
        
        
        
//        public function verify($id=null){
//            Configure::write("debug",2);
//            $id= base64_decode($id);
//          
//            $this->User->id=$id;
//	    $this->request->data['User']['status']='1';
//	    $this->request->data['User']['verify_email']='1';
//            if($this->User->save($this->request->data)){
//                    $fu = $this->User->read(array('email','status','tokenhash','id'),$id);
//
//                    if($fu){
//                        if($fu['User']['status']=="1"){
//                                $key = Security::hash(String::uuid(),'sha512',true);
//                                $hash=sha1($fu['User']['email'].rand(0,100));
//                                $url = Router::url( array('controller'=>'users','action'=>'resetpwd'), true ).'/'.$key.'#'.$hash;
//                                $ms="<p>Click the Link below to reset your password.</p><br /> ".$url;
//                                $fu['User']['tokenhash']=$key;
//                                $this->User->id=$fu['User']['id'];
//                                $current_time=date("Y-m-d H:i:s");
//                                $this->request->data['User']['tokenhash']=$fu['User']['tokenhash'];
//                                $this->request->data['User']['tokenhash_time']=$current_time;
////                                debug($this->request->data);exit;
//                                if($this->User->save($this->request->data)){
//                                     
//                                        $this->redirect($url);
//                                }
//                                else{
//                                        $this->Session->setFlash('Error Generating Reset link');
//                                        $this->redirect('/');
//                                }
//                        }
//                        else{
//                            $this->Session->setFlash('This Account is not Active yet.Check Your mail to activate it');
//                            $this->redirect('/');
//                        }
//                    }
//                    else{
//                        $response['error'] = '1';
//                        $response['msg'] = 'Email does Not Exist';
//                        $this->Session->setFlash('Email does Not Exist');
//                        $this->redirect('/');
//                    }
//                 $this->Session->setFlash(__('Congratulations Your account has been verified!!! '));
//                 $this->redirect("/");
//            }else{
//                $this->Session->setFlash(__('Something went wrong please resend verification code!!! '));
//                $this->redirect("/");
//            }
//            
//        }  
        
        
         public function verify($id = null) {
         Configure::write("debug", 2);
         $id= base64_decode($id);
         $fu=$this->User->find('first',array('conditions'=>array('User.id'=>$id)));
          
         if($id)
         {
            $current_time = date("Y-m-d H:i:s");
       
            $createdtime = $fu['User']['created'];  
            
           
             $datetime2 = date_create($current_time);
            $datetime1 = date_create($createdtime);
            $interval = date_diff($datetime1, $datetime2);
            $finaldate = $interval->format('%a');
            if ($finaldate >= 1) {
                 $this->User->id=$id;
                $this->User->delete();
                  echo "Sorry !! You link has been expired!!";
                  exit;
            
            }
            else
            {
               
                 $this->User->create();
            $this->User->id=$id;
            $this->request->data['User']['status'] ='1';
            $this->request->data['User']['verify_email'] ='1'; 
 
           
                $this->User->save($this->request->data);
                echo "Congratulations Your account has been verified!!!";
                exit;
            }
         }
 else {
                echo "Sorry !!Your are not able to accces this link!!!";
                 exit;
     
  }
        
    }

    /*-------------------------------------------------------Webservice---------------------------------------------*/        
        
//        public function app_login(){
//            $this->recursive = -1;
//            $this->layout = 'ajax';
//            ob_start();
//            var_dump($this->request->data);
//            $c = ob_get_clean();
//            $fc = fopen('files' . DS . 'detail.txt', 'w');
//            fwrite($fc, $c);
//            fclose($fc);
//            if ($this->request->is('post')) {
//                if (!$this->Auth->login()) {
//                    $response['error'] = '1';
//                    $response['msg'] = 'User not valid';
//                    $this->set('response', $response);
//                } else {
//                    $user = $this->User->find('first', array('conditions' => array('id' => $this->Auth->user('id'))));
//                    if ($user['User']['status'] == 0) {
//                        $response['error'] = '1';
//                        $response['msg'] = 'User not active';
//                        $this->set('response', $response);
//                    } else {
//                        if($user['User']['image']){
//                            $user['User']['image'] = FULL_BASE_URL . $this->webroot . 'files/profile_image/' . $user['User']['image'];
//                        }
//                        
//                        $this->User->id = $user['User']['id'];
////                        $this->User->saveField('device_token', $this->request->data['User']['device_token']);
//                        $response['error'] = '0';
//                        $response['list'] = $user;
//                        $this->set('response', $response);
//                    }
//                }
//                $this->render('ajax');
//            }
//        }
      public function app_login(){
          Configure::write('debug', 0);
            
          
            $this->layout = 'ajax';
 
            if ($this->request->is('post')) {
            
               $check=$this->User->find('first', array('conditions' => array(
                "User.username" => $this->request->data['User']['username']
                   
                           
                    ),'fields'=>array('username'),'recursive'=>'-1'));
               
               
              $this->request->data['User']['username']=$check['User']['username'];
             
                if (!$this->Auth->login()) {
                
                    $response['error'] = '1';
                    $response['msg'] = 'User not valid';
                    $this->set('response', $response);
                } else {
                    
                    $user = $this->User->find('first', array('conditions' => array('id' => $this->Auth->user('id'))));
                    
                    if ($user['User']['status'] == 0) {
                        $this->Auth->logout();
                        $this->Cookie->delete('User');
                        $response['error'] = '1';
                        $response['msg'] = 'User not active';
                        $this->set('response', $response);
                    } else {
                        $user['User']['image'] = FULL_BASE_URL . $this->webroot . 'files/profile_image/' . $user['User']['image'];
                        $this->User->id = $user['User']['id'];
                        $this->User->saveField('device_token', $this->request->data['User']['device_token']);
                        $response['error'] = '0';
                        $response['list'] = $user;
                        $this->set('response', $response);
                    }
                }
               
            }
            $this->set('response', $response);
             $this->render('ajax');
        }
      
        public function app_registration() {
            Configure::write('debug',0);
            $this->layout = 'ajax';
            ob_start();
            var_dump($this->request->data);
            $c = ob_get_clean();
            $fc = fopen('files' . DS . 'detail.txt', 'w');
            fwrite($fc, $c);
            fclose($fc);
            if ($this->request->is('post')) {
//                    if ($this->User->hasAny(array('User.username' => $this->request->data['User']['username']))) {
//                        $response['error'] = '1';
//                        $response['msg'] = 'Username already exist';
//                    } else {
                        if ($this->User->hasAny(array('User.email' => $this->request->data['User']['email']))) {
                            $response['error'] = '1';
                            $response['msg'] = 'Email_id already exist';
                        } else {
                            $this->User->create();
//                            $one=$this->request->data['User']['image'];
//                            if($one['error']==0){
//                                $ext = pathinfo($one['name'], PATHINFO_EXTENSION);
//                                $image_name=$this->request->data['User']['image']=date('YmdHis').".".$ext;
//                            }else{
//                                $this->request->data['User']['image']="";
//                            }
                            $this->request->data['User']['role']='user';
                            $this->request->data['User']['username']=$this->request->data['User']['email'];
                            if ($this->User->save($this->request->data)) {
//                                if($one['error']==0){
//                                    $pth="files".DS."profile_image".DS.$image_name;
//                                    $pth1="files".DS."profile_image".DS."thumbnail".DS.$image_name;
//                                    move_uploaded_file($one['tmp_name'], $pth);
//                                    copy($pth, $pth1);
//                                    $this->Resize->resize($pth1,50,50);
//                                }
                                $verify_id = base64_encode($this->User->getLastInsertID());
                                $url=FULL_BASE_URL . $this->webroot . "users/verify/" . $verify_id;
                                $ms="Welcome to Mobile 
                                    <b><a href='" . $url . "' style='text-decoration:none'>Click to verify your email.</a></b><br/>";
                                $l= new CakeEmail('smtp');
                                $l->emailFormat('html')->template('default','default')->subject('Registration Successfully!!!')->
                                        to($this->request->data['User']['email'])->send($ms);
                                $response['error'] = '0';
                                $response['msg'] = 'Register successfully';
                                $response['data'] = $this->request->data;
                                $response['id']=$this->User->getLastInsertID();
                                
                            } else {
                                $response['error'] = '1';
                                $response['msg'] = 'Sorry please try again';
                                
                            }
		        }
//                    }
	       }
               $this->set('response', $response);
               $this->render('ajax');
        }
        public function app_edit($id = null) {
                $this->layout = 'ajax';
                ob_start();
                var_dump($this->request->data);
                $c = ob_get_clean();
                $fc = fopen('files' . DS . 'detail.txt', 'w');
                fwrite($fc, $c);
                fclose($fc);
                $this->User->id=$id;
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is(array('post', 'put'))) {
//                        $one=$this->request->data['User']['image'];
//                        if($one['error']==0){
//                            $x = $this->User->read('image',$id);
//                            if($x['User']['image']){
//                                $x1 =  'files'.DS.'profile_image'.DS.$x['User']['image'];
//                                $x2 =  'files'.DS.'profile_image'.DS.'thumbnail'.DS.$x['User']['image'];
//                                unlink($x1);
//                                unlink($x2);
//                            }
//                            $ext = pathinfo($one['name'], PATHINFO_EXTENSION);
//                            $image_name=$this->request->data['User']['image']=date('YmdHis').".".$ext;
//                            $pth="files".DS."profile_image".DS.$image_name;
//                            $pth1="files".DS."profile_image".DS."thumbnail".DS.$image_name;
//                            move_uploaded_file($one['tmp_name'], $pth);
//                            copy($pth, $pth1);
//                            $this->Resize->resize($pth1,50,50);
//                        }else{
//                            $x = $this->User->read('image',$id);
//                            $this->request->data['User']['image']=$x['User']['image'];
//                        }
                        
			if ($this->User->save($this->request->data)) {
				$response['error'] = '0';
                                $response['msg'] = 'Register successfully';
			} else {
				$response['error'] = '1';
                                $response['msg'] = 'Sorry';
			}
		}
                $this->set('response', $response);
                $this->render('ajax');
	}
        public function app_user($id=null){
            $this->layout = 'ajax';
            $res=$this->User->find('first',array('conditions'=>array(
                "User.id"=>$id
            ),'recursive'=>'-1'));
            if($res){
                if($res['User']['image']){
                    $res['User']['image'] = FULL_BASE_URL . $this->webroot . 'files/profile_image/thumbnail/' . $res['User']['image'];
                }else{
                    $res['User']['image'] = FULL_BASE_URL . $this->webroot . 'img/no-image.jpg';
                }
                $response['error'] = '0';
                $response['msg'] = 'Success';
                $response['list'] = $res;
            }else{
                $response['error'] = '1';
                $response['msg'] = 'Sorry';
            }
            $this->set('response', $response);
            $this->render('ajax');
        }
        
        public function app_alluser(){
            $this->layout = 'ajax';
            $resp=$this->User->find('all',array('conditions'=>array(
                "AND"=>array(
                    "User.status"=>1
                    ,"User.role"=>'admin'
            )),'recursive'=>'-1'));
            if($resp){
                foreach($resp as $res){
                    if($res['User']['image']){
                        $res['User']['image'] = FULL_BASE_URL . $this->webroot . 'files/profile_image/thumbnail/' . $res['User']['image'];
                    }else{
                        $res['User']['image'] = FULL_BASE_URL . $this->webroot . 'img/no-image.jpg';
                    }
                    $res1[]=$res;
                }
                $response['error'] = '0';
                $response['msg'] = 'Success';
                $response['list'] = $res1;
            }else{
                $response['error'] = '1';
                $response['msg'] = 'Sorry';
            }
            $this->set('response', $response);
            $this->render('ajax');
        }
        public function app_forgetpwd() {
            
            Configure::write('debug',0);
            $this->layout = 'ajax';
            $this->User->recursive=-1;
            if(!empty($this->data)){
                if(empty($this->data['User']['email'])){
                    $response['error'] = '1';
                    $response['msg'] = 'Please Provide Your Email Address that You used to Register with Us';
                }else{
                    $email=$this->data['User']['email'];
                    $fu=$this->User->find('first',array('conditions'=>array('User.email'=>$email)));
                    if($fu){
                        if($fu['User']['status']=="1"){
                                $key = Security::hash(String::uuid(),'sha512',true);
                                $hash=sha1($fu['User']['email'].rand(0,100));
                                $url = Router::url( array('controller'=>'Users','action'=>'resetpwd'), true ).'/'.$key.'#'.$hash;
                                $ms="<p>Click the Link below to reset your password.</p><br /> ".$url;
                                $fu['User']['tokenhash']=$key;
                                $this->User->id=$fu['User']['id'];
                                $current_time=date("Y-m-d H:i:s");
                                $this->request->data['User']['tokenhash']=$fu['User']['tokenhash'];
                                $this->request->data['User']['tokenhash_time']=$current_time;
                                if($this->User->save($this->request->data)){
                                        $l = new CakeEmail('smtp');
                                        $l->emailFormat('html')->template('default','default')->subject('Reset Your Password')
                                                ->to($fu['User']['email'])->send($ms);
                                        $response['error'] = '0';
                                        $response['msg'] = 'Check Your Email To Reset your password';
                                }
                                else{
                                        $response['error'] = '1';
                                        $response['msg'] = 'Error Generating Reset link';
                                }
                        }
                        else{
                            $response['error'] = '1';
                            $response['msg'] = 'This Account is not Active yet.Check Your mail to activate it';
                        }
                    }
                    else{
                        
                        $response['error'] = '1';
                        $response['msg'] = 'Email does Not Exist';
                    }
                }
            }
             $this->set("response", $response);
        $this->render('ajax'); 
      }
      
            
        public function app_additem()
        {
       
            Configure::write('debug',2);
             $this->layout = 'ajax';
                ob_start();
                var_dump($this->request->data);
                $c = ob_get_clean();
                $fc = fopen('files' . DS . 'detail.txt', 'w');
                fwrite($fc, $c);
                fclose($fc);
                
            $this->loadModel('Cat');
            $this->loadModel('Price');
             $this->request->data['Price']='[{"Price":{"pri":{"pri":"12.23","pri":"12.34"},"store":{"store":"computer","store":"locate"},"location":{"location":"3.74"}}}]';
           
            $datas=  json_decode($this->request->data['Price'],true);
            debug($datas);
            
            exit;
            
           if($this->request->is(post))
           {
               
               $this->Cat->save($this->request->data);
               {
                   
               }
               $this->Cat->saveAll($datas);
               {
                   
               }
              $response['error'] = 0;
              $response['msg'] = "Sucess"; 
               
           }
        else {
     
     
           $response['error'] = 1;
            $response['msg'] = "Sorry";
              }
            
            
          $this->set("response", $response);
        $this->render('ajax');  
        }
}
