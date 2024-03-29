<?php

class Users extends Model implements Observer
{
	private $_isLoggedIn, $_sessionName , $_cookieName;
	public static $currentLoggedInUser =null;

	public function __construct($user='')
	{
		$table = 'users';
		parent:: __construct($table);
		$this->_sessionName = CURRENT_USER_SESSION_NAME;
		$this->_cookieName = REMEMBER_ME_COOKIE_NAME;
		$this->_softDelete = true;
		if($user != '')
		{

			if(is_int($user))
			{
				$u = $this->_db->findFirst('users',['conditions' => 'id = ?','bind' =>[$user]]);
			}
			else
			{
				$u = $this->_db->findFirst('users',['conditions' => 'username = ?','bind' =>[$user]]);
			}

			//database ekata danawa


			if($u)
			{
				foreach($u as $key => $val)
				{
					$this->$key = $val;
				}
			}
		}
	}

	public function findByUsername($username)
	{
		return $this->findFirst(['conditions' => 'username = ?','bind' =>[$username]]);
	}

	public function findByUserType($usertype)
	{
		return $this->find(['conditions' => 'userType = ?','bind' =>[$usertype]]);
	}


	public static function currentLoggedInUser()
	{
		if(!isset(self::$currentLoggedInUser) && Session::exists(CURRENT_USER_SESSION_NAME)) //ccheck session existss
		{

			$u=new Users((int)Session::get(CURRENT_USER_SESSION_NAME));
			self::$currentLoggedInUser = $u;



		}

		return self::$currentLoggedInUser;
	}


	public function login($rememberMe=false)
	{
		Session::set($this->_sessionName,$this->id);
		if($rememberMe)
		{ //dnd($rememberMe);
			//var_dump($rememberMe);
			$hash = md5(uniqid() + rand(0,100));
			$user_agent = Session::uagent_no_version();
			//dnd($this->_sessionName);
			Cookie::set($this->_cookieName , $hash , REMEMBER_ME_COOKIE_EXPIRY);
			$fields=['session'=>$hash,'user_agent'=>$user_agent,'user_id'=>$this->id];
			$this->_db->query("DELETE FROM user_session WHERE user_id = ? AND user_agent =?", [$this->id,$user_agent]);

			$this->_db->insert('user_session',$fields);
		}

	}



	public function logout()
	{
		//dnd(self::$currentLoggedInUser);
		// $user_agent = Session::uagent_no_version();
		$userSession = Usersession::getFromCookie();
		// if($userSession) $userSession->delete();
		if($userSession) $userSession->delete();
		$this->_db->query("DELETE FROM user_session Where user_id = ? AND user_agent =?", [$this->id,$user_agent]);
		Session::delete(CURRENT_USER_SESSION_NAME);
		//dnd($_COOKIE);
		if(Cookie::exists(REMEMBER_ME_COOKIE_NAME))
			{	//dnd($currentLoggedInUser);
				Cookie::delete(REMEMBER_ME_COOKIE_NAME);
			}

		self::$currentLoggedInUser =null;
		return true;
	}

	public static function loginUserFromCookie()
	{
		$userSession = Usersession::getFromCookie();
		// dnd($userSession);
		// $user_session_model = new UserSession();
		// $user_session = $user_session_model->findFirst(
		// 	[
		// 		'conditions' => "user_agent = ? AND session = ?",
		// 		'bind' => [Session::uagent_no_version(),Cookie::get(REMEMBER_ME_COOKIE_NAME)] //cookie name eka gaththa
		// 	]);
		// dnd($userSession);
		if(($userSession->user_id) != '')
		{
			// var_dump($userSession->user_id);
			$user= new self((int)($userSession->user_id));
			// dnd($user);
		}
		// dnd($user);
		if($user)
		{

		$user->login();
		}
		// $user->login();
		// return self::currentLoggedInUser;
		return $user;

	}

	public function registerNewUser($params)
	{
		$this->assign($params);
		 $this->deleted=0;
		// $this->password = password_hash($this->password, PASSWORD_DEFAULT);
		$this->password = md5($this->password);
		$this->save();
	}

	public function set_delete($num=1)
	{
		$this->deleted=0;
	}

	public function acls()
	{
		if(empty($this->acl)) return [];
		return json_decode($this->acl,true);
	}

	public $deleted =0;

	public function findByUserId($userId,$params=[])
	{
		// dnd($userId);
		$conditions = ['conditions'=>'id = ?','bind'=>[$userId]
	];
	$conditions = array_merge($conditions,$params);
	return $this->find($conditions);
	}

	public function displayName()
	{
		return $this->fname.' '.$this->lname;
	}

	public static $addValidation =
	[
                'fname'=>[
                    'display' => 'First Name',
                    'required' => true
                ],
                'lname'=>[
                    'display' => 'Last Name',
                    'required' => true
                ],
                'username' => [
                    'display' => 'username',
                    'required' => true,
                    'unique' => 'users',
                    'valid_email' => true,
                    'min'=> 4,
                    'max' =>25
                ],
                'email' => [
                    'display' => 'Email',
                    'required' => true,
                    'unique' => 'users',
                    'valid_email' => true,
                    'min'=> 4,
                    'max' =>25
                ],
                'password' => [
                    'display' => 'Password',
                    'required' => true,
                    'min' => 6,
                    'max' => 100
                ],
                'address' => [
                    'display' => 'Address',
                    'required' => true,
                    'min' => 6,
                    'max' => 100
                ],
                'phoneNumber' => [
                    'display' => 'Contact Number',
                    'required' => true,
                    'min' => 10,
                    'max' => 10
                ],
                'phoneNumber2' => [
                    'display' => 'Contact Number 2',
                    'min' => 10,
                    'max' => 100
                ],
                'serviceType' => [
                    'display' => 'Service Type',
                    'required' => true

                ],
                'userType' => [
                    'display' => 'User type',
                    'required' => true,
                    'max' => 100
                ],

                'confirm' => [
                    'display' => 'Confirm Password',
                    'required' => true,
                    'matches' => 'password'

                ],
                'area' => [
                    'display' => 'area',
                    'required' => true

                ],



            ];


	public function findByIdAndUserId($account_id,$user_id,$params=[])
	{
		// dnd($account_id);
		// dnd($user_id);
		$conditions =
		 [
		'conditions' => 'id = ? AND user_id = ?',
		'bind' => [$account_id , $user_id]
	];

	// dnd($conditions);
	$conditions = array_merge($conditions,$params);
	// dnd($conditions);

	return $this->findFirst($conditions);
	}

	public function displayAddress()
	{
		$address = '';
		if(!empty($this->address))
		{
			$address.=$this->address."<br>";

		}
		// if(!empty($this->address1))
		// {
		// 	$address.=$this->address1."<br>"; //if two or more address

		// }
		// if(!empty($this->city))
		// {
		// 	$address.=$this->city.",";
		// }

		// 	$address.=$this->state." ".$this->zip."<br>";
		// }
		return $address;
	}

	public function displayAccountLabel()
	{
		$html = $this->displayName()."<br>";
		$html .= $this->displayAddress();
		return $html;
	}

	public function displayType()
	{
		// return $this->fname.' '.$this->lname;
		if($this->userType == "Both")
		{
			return "Both Provider and Customer";
		}
		return $this->userType;
	}


	public function displayAddressLabel()
	{
		$html = $this->displayName()."<br>";
		$html .= $this->displayAddress();
		return $html;
	}

	// public function update($request,$username)
	// {
	// 	// dnd($request->topic);
	// 	$message = 'Your '. $request->topic .' is accepted by '. $username;
	// 	// dnd($message);
	// 	return $message;
	// }

	public function findById($user_id,$params=[])
	{
		 $conditions = [
		'conditions' => 'id = ?',
		'bind' => [$user_id]
	];

	// dnd($conditions);
	$conditions = array_merge($conditions,$params);
	// dnd($conditions);

	return $this->findFirst($conditions);
	}

	public function findByEmail($email,$params=[])
	{
		 $conditions = [
		'conditions' => 'email = ?',
		'bind' => [$email]
	];

	// dnd($conditions);
	$conditions = array_merge($conditions,$params);
	// dnd($conditions);

	return $this->findFirst($conditions);
	}







	public function updateObserver($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($provider->notifications==NULL)
		{
			$Notification = "Your ". $request->service." has been aceepted by ".$customer->username;
		}
	else
	{
		$Notification = "Your ". $request->service." has been aceepted by ".$customer->username. ",".$provider->notifications;
	}
	// dnd($Notification);

return $this->update($provider->id, ['notifications' => $Notification]);

	}

		public function updateCancelObserver($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($provider->notifications==NULL)
		{
			$Notification = $customer->username." has cancelled accepting your ".$request->service;
		}
	else
	{
		$Notification = $customer->username." has cancelled accepting your ".$request->service. ",".$provider->notifications;
	}
	// dnd($Notification);

return $this->update($provider->id, ['notifications' => $Notification]);

	}

	public function updateCancelProvider($request,$customer,$provider)

	{

	if($customer->notifications==NULL)
		{
			$Notification = "You cancelled accepting ". $request->service." which was requested by ".$provider->username;
		}
	else
	{
		$Notification = "You cancelled accepting ". $request->service." which was requested by ".$provider->username. ",".$customer->notifications;
	}
		return $this->update($customer->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function updateProvider($request,$cus,$pro)

	{

	if($cus->notifications==NULL)
		{
			$Notification = "You accepted ". $request->service." which was requested by ".$pro->username;
		}
	else
	{
		$Notification = "You accepted ". $request->service." which was requested by ".$pro->username. ",".$cus->notifications;
	}
		return $this->update($cus->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function updateConfirmObserver($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($customer->notifications==NULL)
		{
			$Notification = "Your request ". 'to accept '.$request->service.' request'." has been confirmed by ".$provider->username;
		}
	else
	{
		$Notification =  "Your request ". 'to accept '.$request->service.' request'." has been confirmed by ".$provider->username. ",".$customer->notifications;
	}
	// dnd($Notification);

return $this->update($customer->id, ['notifications' => $Notification]);

	}

	public function updateCustomer($request,$customer,$provider)

	{

	if($provider->notifications==NULL)
		{
			$Notification = "You confirmed ". $request->service." which was accepted by ".$customer->username;
		}
	else
	{
		$Notification = "You confirmed ". $request->service." which was accepted by ".$customer->username. ",".$provider->notifications;
	}
		return $this->update($provider->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function setMessagesEmpty($user)
	{
		return $this->update($user->id, ['notifications' => '']);
	}

	public function sendOthers($provider,$customer,$request)
 	{
 		// if($provider->username!= 'yasith' && $provider->username!= 'provider'){dnd($provider);}
 	if($provider->notifications==NULL)
		{
			$Notification = $request->service." has been handed over to another service provider by ".$customer->username;
		}
	else
	{
		$Notification =  $request->service." has been handed over to another service provider by ".$customer->username. ",".$provider->notifications;
	}

 		return $this->update($provider->id, ['notifications' => $Notification]);
 	}

 	public function updateCancelConfirmObserver($requests,$provider,$customer)
 	{
 		{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($customer->notifications==NULL)
		{
			$Notification = 'Confirmation for your '.$requests->service.' accepting'." was cancelled by ".$provider->username;
		}
	else
	{
		$Notification =  'Confirmation for your '.$requests->service.' accepting'." was cancelled by ".$provider->username. ",".$customer->notifications;
	}
	// dnd($Notification);

return $this->update($customer->id, ['notifications' => $Notification]);

	}
 	}

 	public function updateCancelCustomer($requests,$provider,$customer)
	{

	if($provider->notifications==NULL)
		{
			$Notification = "You cancelled ". $requests->service." confirmation of ".$customer->username;
		}
	else
	{
		$Notification = "You cancelled ". $requests->service." confirmation of ".$customer->username. ",".$provider->notifications;
	}
		return $this->update($provider->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function sendCompleteness($request,$servicer,$user)
	{

 			// dnd($servicer[0]->username);
	if($user->notifications==NULL)
		{
			$Notification = "You marked ". $request->topic." as completed which was provided by". $servicer[0]->username;
		}
	elseif($user->notifications!=NULL)
	{
		$Notification = "You marked ". $request->service." as completed which was provided by ". $servicer[0]->username. ",".$user->notifications;
	}
		$this->update($user->id, ['notifications' => $Notification]);

	if($servicer[0]->notifications==NULL)
		{
			$Notification = $user->username." marked ". $request->service." provided by you as completed";
		}
	elseif($servicer[0]->notifications!=NULL)
	{
		$Notification = $user->username." marked ". $request->service." provided by you as completed". ",". $servicer[0]->notifications;
	}
		$this->update($servicer[0]->id, ['notifications' => $Notification]);



	}

	public function sendUnCompleteness($request,$servicer,$user)
	{

 			// dnd($servicer[0]->username);
	if($user->notifications==NULL)
		{
			$Notification = "You marked ". $request->service." as not yet completed which was provided by ". $servicer[0]->username;
		}
	elseif($user->notifications!=NULL)
	{
		$Notification = "You marked ". $request->service." as not yet completed which was provided by ". $servicer[0]->username. ",".$user->notifications;
	}
		$this->update($user->id, ['notifications' => $Notification]);

	if($servicer[0]->notifications==NULL)
		{
			$Notification = $user->username." marked ". $request->service." provided by you as not yet completed.";
		}
	elseif($servicer[0]->notifications!=NULL)
	{
		$Notification = $user->username." marked ". $request->service." provided by you as not yet completed.". ",". $servicer[0]->notifications;
	}
		$this->update($servicer[0]->id, ['notifications' => $Notification]);



	}


public function randomlink($id,$link,$length = 20) {

    // dnd((string)$id." ".$link);
    $this->update($id, ['emailLink' => $link]);

}

public function markRate($servicerId , $rate ,$time)
{
	$this->update($servicerId, ['overallRating' => $rate]);
	$this->update($servicerId, ['ratingtimes' => $time]);
}



public function sendVerification($email,$id)
{

$link = "https://localhost/CodeBiz/MVC/emails/setActive/".$id."/shdshbxhkankdxsakxnjkj3242kj434jg54hhdaksdhsxdhsbdmas";

// dnd($link);
	$subject = "Thank you for joining with " . 'BoardingVibes';

$mail_content =

        'Please click the following link to verify your user account "https://localhost/CodeBiz/MVC/emails/setActive/'.$id.'/shdshbxhkankdxsakxnjkj3242kj434jg54hhdaksdhsxdhsbdmas"';













	$a = mail($email,"Boarding Vibes - Verify The Password",$mail_content );
	// dnd($a);
}

public function setActive($id)
{
	$this->update($id, ['active' => 1 ]);
}

public function updatePassword($id,$password)
{
	$this->update($id, ['password' => $password ]);
}

public function DeleteLink($id)
{
	$this->update($id, ['emailLink' => '' ]);
}

public function sendforgotten($email,$id,$emaillink)
{

// dnd($emaillink);
	$m = "Dear member , To procede your password immediately Click the following link";

$link = "https://localhost/CodeBiz/MVC/emails/getNewPassword/".$id."/".$emaillink;

// dnd($link);
	$subject = "Thank you for registering to " . 'BoardingVibes';

$mail_content =

        $m." ".$link

;













	$a = mail($email,"Boarding Vibes - Forget Password",$mail_content );
	// dnd($a);


}



















public function updateObserverCus($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($provider->notifications==NULL)
		{
			$Notification = "Your ". $request->topic." has been aceepted by ".$customer->username;
		}
	else
	{
		$Notification = "Your ". $request->topic." has been aceepted by ".$customer->username. ",".$provider->notifications;
	}
	// dnd($Notification);

return $this->update($provider->id, ['notifications' => $Notification]);

	}

		public function updateCancelObserverCus($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($provider->notifications==NULL)
		{
			$Notification = $customer->username." has been cancelled the acceptance to your ".$request->topic;
		}
	else
	{
		$Notification = $customer->username." has been cancelled the acceptance to your ".$request->topic. ",".$provider->notifications;
	}
	// dnd($Notification);

return $this->update($provider->id, ['notifications' => $Notification]);

	}

	public function updateCancelProviderCus($request,$customer,$provider)

	{

	if($customer->notifications==NULL)
		{
			$Notification = "You cancelled acceptance of the ". $request->topic." which was request by the ".$provider->username;
		}
	else
	{
		$Notification = "You cancelled acceptance of the ". $request->topic." which was request by the ".$provider->username. ",".$customer->notifications;
	}
		return $this->update($customer->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function updateProviderCus($request,$cus,$pro)

	{

	if($cus->notifications==NULL)
		{
			$Notification = "You accepted the ". $request->topic." which was request by the ".$pro->username;
		}
	else
	{
		$Notification = "You accepted the ". $request->topic." which was requested by the ".$pro->username. ",".$cus->notifications;
	}
		return $this->update($cus->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function updateConfirmObserverCus($request,$customer,$provider)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($customer->notifications==NULL)
		{
			$Notification = "Your ". 'acceptance for the '.$request->topic.' request'." has been confirmed by ".$provider->username;
		}
	else
	{
		$Notification =  "Your ". 'acceptance for the '.$request->topic.' request'." has been confirmed by ".$provider->username. ",".$customer->notifications;
	}
	// dnd($Notification);

return $this->update($customer->id, ['notifications' => $Notification]);

	}

	public function updateCustomerCus($request,$customer,$provider)

	{

	if($provider->notifications==NULL)
		{
			$Notification = "You confirm the ". $request->topic." which was accepted by the ".$customer->username;
		}
	else
	{
		$Notification = "You confirm the ". $request->topic." which was accepted by the ".$customer->username. ",".$provider->notifications;
	}
		return $this->update($provider->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	// public function setMessagesEmpty($user)
	// {
	// 	return $this->update($user->id, ['notifications' => '']);
	// }

	// public function sendOthers($provider,$customer,$request)
 // 	{
 // 		// if($provider->username!= 'yasith' && $provider->username!= 'provider'){dnd($provider);}
 // 	if($provider->notifications==NULL)
	// 	{
	// 		$Notification = $request->topic." has been given to another topic provider by ".$customer->username;
	// 	}
	// else
	// {
	// 	$Notification =  $request->topic." has been given to another topic provider by ".$customer->username. ",".$provider->notifications;
	// }

 	// 	return $this->update($provider->id, ['notifications' => $Notification]);
 	// }

 	public function updateCancelConfirmObserverCus($requests,$provider,$customer)
 	{
 		{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($customer->notifications==NULL)
		{
			$Notification = 'Confirmation for your '.$requests->topic.' acceptance'." has been cancelled by ".$provider->username;
		}
	else
	{
		$Notification =  'Confirmation for your '.$requests->topic.' acceptance'." has been cancelled by ".$provider->username. ",".$customer->notifications;
	}
	// dnd($Notification);

return $this->update($customer->id, ['notifications' => $Notification]);

	}
 	}

 	public function updateCancelCustomerCus($requests,$provider,$customer)
	{

	if($provider->notifications==NULL)
		{
			$Notification = "You cancelled the ". $requests->topic." confirmation of ".$customer->username;
		}
	else
	{
		$Notification = "You cancelled the ". $requests->topic." confirmation of ".$customer->username. ",".$provider->notifications;
	}
		return $this->update($provider->id, ['notifications' => $Notification]);
	// dnd($Notification);
	}

	public function sendCompletenessCus($request,$servicer,$user)
	{

 			// dnd($servicer[0]->username);
	if($user->notifications==NULL)
		{
			$Notification = "You mark complete as the ". $request->topic." serviced of ". $servicer[0]->username;
		}
	elseif($user->notifications!=NULL)
	{
		$Notification = "You mark complete as the ". $request->topic." serviced of ". $servicer[0]->username. ",".$user->notifications;
	}
		$this->update($user->id, ['notifications' => $Notification]);

	if($servicer[0]->notifications==NULL)
		{
			$Notification = $user->username." mark complete as the ". $request->topic." serviced of You";
		}
	elseif($servicer[0]->notifications!=NULL)
	{
		$Notification = $user->username." mark complete as the ". $request->topic." serviced of you". ",". $servicer[0]->notifications;
	}
		$this->update($servicer[0]->id, ['notifications' => $Notification]);



	}

	public function sendUnCompletenessCus($request,$servicer,$user)
	{

 			// dnd($servicer[0]->username);
	if($user->notifications==NULL)
		{
			$Notification = "You mark not completed yet as the ". $request->topic." serviced of ". $servicer[0]->username;
		}
	elseif($user->notifications!=NULL)
	{
		$Notification = "You mark not completed yet as the ". $request->topic." serviced of ". $servicer[0]->username. ",".$user->notifications;
	}
		$this->update($user->id, ['notifications' => $Notification]);

	if($servicer[0]->notifications==NULL)
		{
			$Notification = $user->username." mark not completed yet as the ". $request->topic." serviced of You";
		}
	elseif($servicer[0]->notifications!=NULL)
	{
		$Notification = $user->username." mark not completed yet as the ". $request->topic." serviced of you". ",". $servicer[0]->notifications;
	}
		$this->update($servicer[0]->id, ['notifications' => $Notification]);



	}




	public function MarkReport($type,$id,$user_id,$other,$add)
	{
		// dnd($userId);
		$xxxx= (string)($add->reportedBy).",".(string)$user_id;
		$this->update($id, ['reported' => ($add->reported+1)]);
		$this->update($id, ['reportedBy' => $xxxx]);

		return true;
	}








	public function ReportNoti($type,$obj,$reciever)
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($type == "add")
	{
		$vv = $obj->topic." advertisement";
	}
	elseif($type == "req")
	{
		$vv = $obj->service." request";
	}
	else
	{
		$vv = "user account";
	}

	if($reciever->notifications==NULL)
		{
			$Notification = "Your ".$vv." has been reported in several times.";
		}
	else
	{
		$Notification = "Your ".$vv." has been reported in several times.". ",".$reciever->notifications;
	}
	// dnd($Notification);

return $this->update($reciever->id, ['notifications' => $Notification]);

	}








		public function ReportAdminNoti($type,$obj,$reciever,$r='')
	{
		// dnd($request);
		// dnd($provider);
		// dnd($customer);

	if($type == "add")
	{
		$vv = $obj->topic." advertisement";
		$hh = $r->username;
	}
	elseif($type == "req")
	{
		$vv = $obj->service." request";
		$hh = $r->username;
	}
	else
	{
		$vv = "user account";
		$hh = $obj->username;
	}



	if($reciever->notifications==NULL)
		{
			$Notification = $vv." of ".$hh." has been exceded the maximum reported times..";
		}
	else
	{
		$Notification = $vv." of ".$hh." has been exceded the maximum reported times..". ",".$reciever->notifications;
	}
	// dnd($Notification);

return $this->update($reciever->id, ['notifications' => $Notification]);

	}











	public function AdminsendNoti($message,$reciever)
	{


	if($reciever->notifications==NULL)
		{
			$Notification = $message;
		}
	else
	{
		$Notification = $message. ",".$reciever->notifications;
	}
	// dnd($Notification);

return $this->update($reciever->id, ['notifications' => $Notification]);

	}



public function DeleteNoti($reciever)
{
	// dnd($reciever);
	mail($reciever,"Account Banned - Boarding Vibes","Your account has been banned. If you want to reset it Send Email with valid reasons.");

}


public function reEntryPassword($password)
{

	return $this->update(currentUser()->id, ['password' => $password]);

}

public function uploadPhoto($id,$file)
{

	return $this->update($id, ['photolink' => $file]);

}



}










?>
