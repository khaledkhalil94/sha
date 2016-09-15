<?php 
require_once( $_SERVER["DOCUMENT_ROOT"] .'/sha/src/init.php');

//Allow access only via ajax requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {

	Redirect::redirectTo('404');
}

if(isset($_POST['action'])){

	$action = $_POST['action'];
	unset($_POST['action']);


} elseif(isset($_GET['action'])){

	$action = $_GET['action'];
	unset($_GET['action']);

} else {

	die("Error! bad request.");
}

switch ($action) {

	// follow a user
	case 'follow':

		$userID = $_POST['id'];

		$follow = User::follow($userID);

		if($follow === true){

			die(json_encode(['status' => true]));
		} else {
			
			die(json_encode(['status' => false, 'err' => $follow]));
		}

		break;

	// unfollow a user
	case 'unfollow':

		$userID = $_POST['id'];

		$unfollow = User::unfollow($userID);

		if($unfollow === true){

			die(json_encode(['status' => true]));
		} else {
			
			die(json_encode(['status' => false]));
		}

		break;

	// get user profile card
	case 'profile_card':

		$uid = $_POST['id'];

		$userq = new User();
		$user = $userq->get_user_info($uid);

		$logged = $session->is_logged_in();

		if(!is_object($user)) die("User was not found.");

		if($logged){
			$is_frnd = $userq->is_friend($uid, USER_ID);
		}

		$html = 
			"<div class='ui card'>
			<a class='image' href='".BASE_URL."user/{$user->id}/'>
				<img src='$user->img_path'>
			</a>
			<div class='content'>
			<h3 class='header'>{$user->full_name}";
			if($logged && $is_frnd){
				$html .= "<i title='You and $user->firstName are friends' class='mdi mdi-account-multiple' style='color: #1ed02d; margin-left:5px;'></i>";
			}
			$html .= "</h3><div class='meta'>
				<span class='username'><a href='".BASE_URL."user/{$user->id}/'>@{$user->username}</a></span>
				<div class='user-points'>
					<a class='ui label' style='color:#04c704;' title='Total Points'>
					<i class='thumbs outline up icon'></i>
					". User::get_user_points($uid) ."
					</a>
				</div>
			</div>
			</div>";
			if(!$logged){
				$html .= "<a href='/sha/login.php' class='ui button green'>Follow</a>";
			} elseif($uid === USER_ID){
			} elseif(User::is_flw($uid, USER_ID) !== true){
				$html .= "<button id='user_flw' user-id='{$uid}' class='ui button green'>Follow</button>";
			} else { 
				$html .= "<button id='user_unflw' user-id='{$uid}' class='ui button red'>Following</button>";
			}
		$html .= "</div>";
		die($html);
		break;

	case 'feed':

		$data = $_POST;
		unset($data['action']);

		$user_id = $data['user_id'];
		$content = $data['content'];
		$token = $data['token'];

		// check token validation
		if(!Token::validateToken($token)){
			 die(json_encode(['status' => false, 'err' => 'Token is not valid.']));
		}

		global $database;

		$data = ['user_id' => $user_id, 'content' => $content, 'poster_id' => USER_ID];
		$insert = $database->insert_data(TABLE_ACTIVITY, $data);
		
		if($insert === true){
			$id = $database->lastId;

			die(json_encode(['status' => true, 'id' => $id]));
		}

	case 'get_post':

		$id = sanitize_id($_GET['id']);

		$post = new Post();
		$comment = $post->get_post($id);

		if(is_object($comment)){
			
			die(json_encode($comment));

		} else {

			die(json_encode(['status' => false, 'err' => $comment]));
		}
		break;
	

	default:
		break;
}
