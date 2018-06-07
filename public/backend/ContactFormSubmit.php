<?php 

class ContactFormSubmit{

	public $errors = [];
	private $_data = [];
	private $_db;
	private $_dbName = 'example_contact_form';

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Submit Form
	 * @param Array $postData Raw $_POST data
	 */
	public function submit($postData){
		if(!$this->setData($postData)) return false;
		if(!$this->validate()) return false;
		if(!$this->sendEmail("guy-smiley@example.com")) return false;
		if(!$this->createDBConnection('127.0.0.1', 'root')) return false;
		$this->connectToDatabase();
		if(!$this->addContactFormToDatabase()) return false;
		$this->closeDB();
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Validates the post
	 * @return Boolean if values don't validate return false
	 */
	public function validate(){
		if(empty($this->_data)){
			$this->errors = ['error' => 'Please submit all fields'];
			return false;
		}
		//basic validation
		$this->_validateParam($this->_data['full_name'], 'full_name');
		$this->_validateParam($this->_data['email'], 'email');
		$this->_validateParam($this->_data['message'], 'message');
		//further validation
		if(!isset($this->errors['full_name']) && !preg_match("/^[a-zA-Z ]*$/", $this->_data['full_name'])){
			$this->errors['full_name'] = 'Only letters and spaces allowed';
		}
		if(!isset($this->errors['email']) && !filter_var($this->_data['email'], FILTER_VALIDATE_EMAIL)){
			$this->errors['email'] = 'Enter a valid email address';
		}
		if(isset($this->_data['phone']) && !empty($this->_data['phone']))
		{
			//attempt to format the number
			if(!isset($this->_data['phone']{3})) $this->errors['phone'] = 'Please enter a valid phone number';
			else {
				$this->_data['phone'] = preg_replace("/[^0-9]/", "", $this->_data['phone']);
			  	$length = strlen($this->_data['phone']);
			  	switch($length) {
					case 7:
						$this->_data['phone'] = preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $this->_data['phone']);
						break;
					case 10:
						$this->_data['phone'] = preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $this->_data['phone']);
						break;
					case 11:
						$this->_data['phone'] = preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "+$1 ($2) $3-$4", $this->_data['phone']);
						break;
					default:
						$this->errors['phone'] = 'Please enter a valid phone number';
						break;
			  	}
		  	}
		}
		if(!empty($this->errors)) return false;
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Sets the data
	 * @param Array $data Sets the data from $_POST
	 * @return  Boolean All required fields are submitted
	 */
	public function setData($data){
		$this->errors = [];
		//In production enviroment, further security measures should be taken including csrf tokens, etc.
		$this->_cleanPost($data);
		$requiredFields = ['full_name', 'email', 'message'];
		$missingFields = array_diff($requiredFields, array_keys($data));
		if(count($missingFields) > 0) foreach($missingFields as $field){
			if($field === 'phone') continue;
			$this->errors[$field] = $field.' is required.';
		}
		if(!empty($this->errors)) return false;
		$this->_data = ['full_name' => $data['full_name'], 'email' => $data['email'], 'message' => $data['message']];
		if(isset($data['phone'])) $this->_data['phone'] = $data['phone'];
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Returns form data
	 * @return Array Form data passed in from $_POST and is sanitized
	 */
	public function getData(){
		return $this->_data;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Sends contact form email out
	 * @param  String $to Email address of who to send the contact form to
	 * @return Boolean If error we return false
	 */
	public function sendEmail($to)
	{
		$subject = $this->_data['full_name']." sent you a message!";
		$message = "
		<html>
			<head>
				<title>".$subject."</title>
			</head>
			<body>
				<table>
					<tr style=\"vertical-align: top;margin-bottom: 10px;\">
						<td><strong>Message:<strong></td>
						<td style=\"padding-left: 10px;\">".$this->_data['message']."</td>
					</tr>
					<tr>
						<td colspan=\"2\">
							<strong>Contact Information:</strong><br>
							Name: ".$this->_data['full_name']."<br>
							Email: ".$this->_data['email']."
							".(isset($this->_data['phone']) ? "<br>Phone: ".$this->_data['phone'] : "")."
						</td>
					</tr>
				</table>
			</body>
		</html>
		";

		//Email Headers
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		$headers[] = 'From: Jacob Hyde <jhyde@example.com>';

		if(!mail($to, $subject, $message, implode("\r\n", $headers))){
			$this->errors = ['error' => 'An unkonwn error has occured'];
			return false;
		}
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Creates a database connection
	 * @param  String $server   Server to connect to
	 * @param  String $username User of the server to use
	 * @param  String $password Password to use
	 * @return Boolean If cannot connect to Database return false
	 */
	public function createDBConnection($server, $username, $password = ''){
		$this->_db = new mysqli($server, $username, $password);
		if($this->_db->connect_error) return false;
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Connects to the database or creates it if not exists
	 */
	public function connectToDatabase($createIfNotExists = true){
		$dbExists = $this->_db->query("SELECT COUNT(*) as `exists` FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$this->_dbName."'")->fetch_assoc()['exists'];
		if($dbExists == 0 && $createIfNotExists)
		{
			$this->createDatabase();
			//select the database
			mysqli_select_db($this->_db, $this->_dbName);
			$this->createTable();
		}else if($dbExists == 1){
			//select the database
			mysqli_select_db($this->_db, $this->_dbName);
		}else{
			$this->_errors = ['error' => 'An unkonwn error has occured']; //should alert devops
			return false;
		}
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Creates the database
	 * @return Boolean If database was created
	 */
	public function createDatabase(){
		if($this->_db->query("CREATE DATABASE `".$this->_dbName."`") !== true){
			$this->errors = ['error' => 'An unkonwn error has occured']; //should alert devops
			return false;
		}
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Create the contact_form table
	 * @return Boolean If table was created
	 */
	public function createTable(){
		$tableSQL = "CREATE TABLE `contact_form` (
	  		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  		`full_name` varchar(255) NOT NULL DEFAULT '',
	  		`email` varchar(255) NOT NULL,
	  		`phone` varchar(20) DEFAULT '',
	  		`message` longtext NOT NULL,
	  		PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
		if($this->_db->query($tableSQL) !== true){
			$this->errors = ['error' => 'An unkonwn error has occured']; //should alert devops
			return false;
		}
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Adds the contact form the database
	 * @return Boolean returns result of insert
	 */
	public function addContactFormToDatabase(){
		if(empty($this->_data)){
			$this->errors = ['error' => 'Please submit all fields'];
			return false;
		}
		$insertCols = "";
		$insertVals = "";
		//insert values string
		foreach($this->_data as $column => $value){
			$insertCols .= $this->_db->real_escape_string($column).",";
			$insertVals .= "'".$value."'".",";
		}
		$insertCols = substr($insertCols, 0, -1);
		$insertVals = substr($insertVals, 0, -1);

		$insertContactFormSQL = "INSERT INTO contact_form (".$insertCols.") VALUES (".$insertVals.")";
		if($this->_db->query($insertContactFormSQL) !== true){
			$this->errors = ['error' => 'An unkonwn error has occured'];
			return false;
		}
		return true;
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Close the database connection
	 */
	public function closeDB(){
		$this->_db->close();
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Clean params from SQL injection AND XSS
	 */
	private function _cleanPost(&$params){
		//if array or vars we should clean it
	    foreach ($params as &$var) {
	        is_array($var) ? cleanPost($var) : $var = filter_var(strip_tags(stripslashes(trim($var))), FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW | FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_AMP);
	    }
	}

//----------------------------------------------------------------------------------------------------------------------

	/**
	 * Basic validation that the field is not empty. If so we add to the errors
	 * @param String $name Name of field
	 * @param String $value Value of field
	 */
	private function _validateParam($name, $value){
		if(empty($value)) $this->errors[$name] = $name.' is required.';
	}

//----------------------------------------------------------------------------------------------------------------------

}

?>