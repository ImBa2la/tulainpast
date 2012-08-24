<?php
/**
 * Description of subscribe
 *
 * @author dev-kirill
 * /classes/ajax.php?action=subscribe&subs[name]=Test&subs[email]=tesT&subs[act]=0
 */
class subscribe {
	function ajax($params,$xml,$mysql){
		$params['subs']['email'] = (mymail::isEmail($params['subs']['email'])) ? 
			$params['subs']['email'] : 
			(mymail::isEmail(base64_decode($params['subs']['email'])) ? base64_decode($params['subs']['email']) : null);
		
		if($_SESSION['login'] //registred users
			&& ($res = $mysql->query('select * from '.$mysql->getTableName('users').' where `login`="'.$_SESSION['login'].'"',true))
			&& $mysql->update(
				$mysql->getTableName('users')
				,array('subscribe'=>($params['subs']['act'] == 0)?0:1)
				,'`login`="'.$_SESSION['login'].'"'
			)
		){
			$mess = 'Вы успешно '.(($params['subs']['act'] == 0)?'отписались от рассылки':'подписались на рассылку, ваш e-mail подписки: '.$res['email']);
			$json = "{\"result\":\"successful\",\"message\":\"".$mess."\"}";
		//clicked subscribe
		}elseif($params['subs']['email'] && mymail::isEmail($params['subs']['email'])){
			$q = 'select * from '.$mysql->getTableName('users').' where `email`="'.$params['subs']['email'].'"';
			if($res = $mysql->query($q,true) &&
				$mysql->update(
					 $mysql->getTableName('users')
					,array('subscribe'=>($params['subs']['act'] == 0)?0:1)
					,'`email`="'.$params['subs']['email'].'"'
				)
			){
				$mess = 'Вы успешно '.(($params['subs']['act'] == 0)?'отписались от рассылки':'подписались на рассылку');
			}elseif($params['subs']['name']){
				//return "{\"result\":\"error\",\"message\":\"TEST\"}";
				$data = array(
					 'login'		=>'"'.md5($params['subs']['name'].'_'.$params['subs']['email'].'_'.mt_rand(0, 9999)).'"'
					,'password'		=>'"'.md5($params['subs']['name']).'"'
					,'name'			=>'"'.$params['subs']['name'].'"'
					,'email'		=>'"'.$params['subs']['email'].'"'
					,'subscribe'	=>1
				);
				//$_SESSION['login'] = $data['login'];
				$mysql->insert($mysql->getTableName('users'),$data);
				$mess = 'Вы успешно подписались на рассылку';
			}else{
				$mess = false;
			}
			
			if($mess) //make json data result
				$json = "{\"result\":\"successful\",\"message\":\"".$mess."\"}";
			else //error
				$json = "{\"result\":\"error\",\"message\":\"Ошибка заполнения формы\"}";
			
		}
		return $json;
	}
}

?>
