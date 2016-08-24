<?php 
require_once('init.php');

class User {


	/**
	 *@var boolean $error error status
	 */
	public $error=false;

	/**
	 *@var array of errors
	 */
	public $errors = [];


	/**
	 * deletes a user entirely from the database along with local files
	 *
	 * @param string @email user's email
	 * @param string @pw user's password
	 * 
	 * @return boolean
	 */
	public function deleteUser($email, $pw){
		global $connection;

		$con = $connection;
		
		// check for empty values
		if(empty($email) || empty($pw)) {
			$this->errors[] = "Email and Password can't be empty";
			return false;
		}


		// check token validation
		if(!Token::validateToken($data['auth_token'])){
			$this->error = true;
			$this->errors[] = "Token is not valid.";
			return false;
		}

		// validate email
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->errors[] = "Email is not valid.";
			return false;
		}

		$user = Auth::getUserDetails($email);

		// if email or password are wrong
		if(!is_object($user)) {
			$this->errors[] = "Details are not correct.";
			return false;
		}

		// if the user id or email don't match those in the database
		if($user->id !== USER_ID) {
			$this->errors[] = "Details are not correct.";
			return false;
		}

		// if the given password doesn't match the user password in the db
		if(!password_verify($pw, $user->password)) {
			$this->errors[] = "Details are not correct.";
			return false;
		}
		
		// root path to the user's local server folder
		$path = DEF_IMG_UP_DIR.$user->id.DS;

		// all good, begin deleting all user data
		// useing transactions

		// store the queries to be executed
		$queries = [];


		// delete from login_info
		$sql1 = "DELETE FROM ". TABLE_INFO ." WHERE id = {$user->id} LIMIT 1";
		$queries[] = $con->prepare($sql1);

		// delete from users
		$sql2 = "DELETE FROM ". TABLE_USERS ." WHERE id = {$user->id} LIMIT 1";
		$queries[] = $con->prepare($sql2);

		// delete from user_privacy
		$sql3 = "DELETE FROM ". TABLE_PRIVACY ." WHERE user_id = {$user->id} LIMIT 1";
		$queries[] = $con->prepare($sql3);

		// being transaction and stop auto commit
		$con->beginTransaction();

		// execute all the queries
		$count = count($queries);
		$affected_rows = 0;

		for ($i=0; $i < $count; $i++) { 

			$queries[$i]->execute();
			if($queries[$i]->rowCount() == 1){

				$affected_rows++;
			} else {

				$this->errors[$i+1] = $queries[$i]->queryString;
			}
		}
		
		// if the deleted rows count is not the same as the queries count, roll back everything
		if ($affected_rows !== $count) {
			$con->rollBack();

			return false;

		} elseif(!rm_dir($path)) {

			$this->errors['folder'] = "Error deleting local folder.";
			return $errors;

		} else {

			// delete from remaining tables 

			// delete pictures
			$sql = "DELETE FROM ". TABLE_PROFILE_PICS ." WHERE user_id = {$user->id} LIMIT 1";
			$con->exec($sql);

			// delete questions
			$sql = "DELETE FROM ". TABLE_QUESTIONS ." WHERE uid = {$user->id} LIMIT 1";
			$con->exec($sql);

			// delete comments
			$sql = "DELETE FROM ". TABLE_COMMENTS ." WHERE uid = {$user->id} LIMIT 1";
			$con->exec($sql);

			// delete points
			$sql = "DELETE FROM ". TABLE_POINTS ." WHERE user_id = {$user->id} LIMIT 1";
			$con->exec($sql);

			// delete reports
			$sql = "DELETE FROM ". TABLE_REPORTS ." WHERE reporter = {$user->id} LIMIT 1";
			$con->exec($sql);

			// delete messages
			$sql = "DELETE FROM ". TABLE_MESSAGES ." WHERE user_id OR sender_id = {$user->id} LIMIT 1";
			$con->exec($sql);

			// confirm the actions
			$con->commit();

			return true;

		}
	}

	/**
	 * changes user settings (usernane, email, password)
	 *
	 * @param array @data user settings values
	 * @param ing @user_id (default is the id stored in session)
	 * 
	 * @return boolean
	 */
	public function changeSettings($data, $user_id=USER_ID){
		global $database;

		if(!is_array($data)) return false;
		//print_r($data); exit;
		$id = $user_id;

		// check token validation
		if(!Token::validateToken($data['auth_token'])){
			$this->error = true;
			$this->errors[] = "Token is not valid.";
			return false;
		}

		// check if old password is passed
		if(!isset($data['old_password'])) {
			$this->errors['old_password'] = "You must enter your old password.";
			$this->error = true;
			return false;
		} else {
			$pw = $data['old_password'];
		}

		// verify password
		if(!Auth::password_check($id, $pw)) {
			$this->errors['old_password'] = "Password is incorrect.";
			$this->error = true;
			return false;
		}

		// array of data to be updated
		$newData = [];

		// no need for this anymore
		unset($data['old_password']);


		$username = isset($data['username']) ? $data['username'] : false;
		$email = isset($data['email']) ? $data['email'] : false;
		$pw1 = isset($data['password']) ? $data['password'] : false;
		$pw2 = isset($data['repassword']) ? $data['repassword'] : false;

		// at least one field should be changed
		if(!$username && !$email && !$pw1) {
			$this->errors[] = "No data to be changed.";
			$this->error = true;
			return false;
		}

		// get user details by his id
		$user = Auth::getUserDetails($id);

		// if the given username is different than the one in the database
		// check if it exists in another row
		if($username && ($username !== $user->username)) {
			if(!Auth::form_check("username", $username)) {

				$this->errors['username'] = "Username already exists.";
				$this->error = true;
			}

			// check unsername length
			if (strlen($username) > 15){
				$this->error = true;
				$this->errors['username'] = "Username must be between 4 and 15 characters.";
				
			} elseif(strlen($username) < 4){
				$this->error = true;
				$this->errors['username'] = "Username must be between 4 and 15 characters.";

			}

			// check username allowed characters
			if(preg_match('/[^a-z_\-0-9]/i', $username)){
				$this->error = true;
				$this->errors['username'] = "Username may only contain alphanumeric characters or '_'";
			}

			$newData['username'] = $username;

		}

		// the same for email
		if($email && ($email !== $user->email)) {
			if(!Auth::form_check("email", $email)) {
				
				$this->errors['email'] = "email already exists.";
				$this->error = true;
			}

			// validate email
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$this->error = true;
				$this->errors['email'] = "email is not valid";
			}

			$newData['email'] = $email;


		}

		// username and email are passed, check for password change
		if($pw1 && $pw2){
			// if password 1 doesn't match password 2
			if($pw1 !== $pw2){
				$this->errors[] = "Passwords don't match.";
				$this->error = true;
				return false;
			}

			// check password length
			if (strlen($pw1) < 4){
				$this->error = true;
				$this->errors['password'] = "Password must be at least 4 characters long.";
				return false;
			}

			$pw = password_hash($pw1, PASSWORD_BCRYPT);
			$newData['password'] = $pw;
		}

		if($this->error) return false;

		// no errors, we have the new data, update the table

		// get fields and values from the data array
		$fields = array_keys($newData);
		$values = array_values($newData);

		$update = $database->update_data('login_info', $fields, $values, 'id', $id);
		if($update !== true){ // if something went wrong while updating
			return $database->errors;
		}

		return true;
	}

	/**
	 * adds a user to blocklist
	 *
	 * @param int @user_id
	 * 
	 * @return boolean
	 */
	public static function block($user_id){
		global $database;
		global $connection;

		$self = USER_ID;

		// first check if the user is already blocked
		$sql = "SELECT 1 FROM `block_list` WHERE user_id = :self AND blocked_id = :blocked";

		$stmt = $connection->prepare($sql);
		
		$stmt->bindValue(':self', $self, PDO::PARAM_INT);
		$stmt->bindValue(':blocked', $user_id, PDO::PARAM_INT);

		$stmt->execute();

		$exists = (bool)$stmt->fetch();

		if($exists) return "You have already blocked this user.";

		$data = array(
			'user_id' => $self,
			'blocked_id' => $user_id
			);

		$insert = $database->insert_data('block_list', $data);
		
		if($insert === true) {
			return true;
		} else {
			return array_shift($database->errors);
		}
	}

	/**
	 * remove a user from blocklist
	 *
	 * @param int @user_id
	 * 
	 * @return boolean
	 */
	public static function unBlock($user_id){
		global $connection;

		$self = USER_ID;

		$sql = "DELETE FROM `block_list` WHERE user_id = $self AND blocked_id = :user_id";

		$stmt = $connection->prepare($sql);
		$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

		if(!$stmt->execute()){
			$error = $stmt->errorInfo();

			return $error[2];
		}

		return true;
	}

	/**
	 * gets an array of block list by user
	 *
	 * @param int @user_id
	 * 
	 * @return array
	 */
	public static function blocked_by_user($user_id){
		global $connection;

		$sql = "SELECT block_list.blocked_id FROM `block_list` WHERE user_id = ?";

		$stmt = $connection->prepare($sql);
		$stmt->bindValue(1, $user_id, PDO::PARAM_INT);

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function get_blocks($user_id){
		global $connection;

		$sql = "SELECT block_list.*, info.username AS username, info.id AS uid,
		CONCAT(users.firstName, ' ', users.lastName) AS full_name,
		pics.path FROM `block_list`

		INNER JOIN `students` AS users ON block_list.blocked_id = users.id
		INNER JOIN `login_info` AS info ON block_list.blocked_id = info.id
		INNER JOIN `profile_pic` AS pics ON block_list.blocked_id = pics.user_id
		
		WHERE block_list.user_id = :user_id";

		$stmt = $connection->prepare($sql);
		$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

		if(!$stmt->execute()){
			$error = ($stmt->errorInfo());

			$this->error = true;
			$this->errors = $error[2];
			return $error[2];
		}

		$blocked = $stmt->fetchAll(PDO::FETCH_OBJ);

		return $blocked;
	}

	// TBR
	public static function find_by_id($id, $msql=""){
		$sql = "SELECT * FROM " .static::$table_name." WHERE id={$id}";
		if(!empty($msql)) $sql .= $msql;
		$found = static::find_by_sql($sql);
		return !empty($found) ? array_shift($found) : false;
	}


	// TBR
	public static function find_by_sql($sql=""){
		global $connection;

		$result = $connection->prepare($sql);
		$result->execute();
		$object_array = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$object_array[] = static::instantiate($row);
		}
		return $object_array;
	}


	protected static function instantiate($data){
		$object = new static;

		foreach ($data as $attribute => $value) {
			if ($object->has_attribute($attribute)) {
				$object->$attribute = trim($value);
			}
		}

		return $object;
	}

	private function has_attribute($attribute){
		$object_vars = get_object_vars($this);

		return array_key_exists($attribute, $object_vars);
	}
	
	// public function attributes(&$values){
	// 	$attributes = array();
	// 	$values = array();
	// 	foreach (static::$db_fields as $field) {
	// 		if(property_exists($this, $field)){
	// 			$attributes[$field] = $this->$field;
	// 			$values[":".$field] = $this->$field;
	// 		}
	// 	}
	// 	return $attributes;
	// }

	public static function query($sql){
		global $connection;
		$stmt = $connection->prepare($sql);

		if(!$stmt->execute()){
			$error = ($stmt->errorInfo());
			echo $error[2];
			return false;
		}
		return true;
	}

	/**
	 * searches for a user in the database by username or id
	 *
	 * @param mixed $name
	 * 
	 * @return array
	 */
	public static function users_search($name){
		global $connection;

		$id = is_numeric($name) ? 'id' : 'username';

		if($id == 'username'){
			if($name[0] == '@'){
				$name = substr($name, 1);
			}
		}

		$sql = "SELECT info.username AS title, info.id AS id, pic.path AS image FROM `login_info` AS info
		LEFT JOIN `profile_pic` AS pic ON info.id = pic.user_id
		WHERE info.{$id} LIKE ? LIMIT 4";

		$stmt = $connection->prepare($sql);
		$stmt->bindValue(1, "%{$name}%");

		if(!$stmt->execute()){
			$error = $stmt->errorInfo();

			die($error[2]);
		}

		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return $results;
	}
}

?>