<?php
 
include '../sql_access.php';
 
define('SERVICE_SETTINGS_CONFIGURE','1');	// will create new tables (configure the parking)
define('SERVICE_SETTINGS_RESET','2'); // will clear all the rows of tables
define('SERVICE_SETTINGS_DELETE','3');	// will delete tables
//define('SERVICE_SETTINGS_EXPAND','4');	// will expand current table
define('SERVICE_USER_LOGOUT','5');	// basic logout
define('SERVICE_USER_SIGNUP','6');	// basic signup
define('SERVICE_USER_LOGIN','7');	// basic login
define('SERVICE_USER_DELETE', '8');	// basic account deactivation
define('SERVICE_BLOCK', '9');	// block a parking space
define('SERVICE_UNBLOCK', '10');	// unblock a parking space
define('SERVICE_UNBLOCK_ALL', 'SERVICE_SETTINGS_RESET'); // unblock all the parking space
define('SERVICE_STATUS', '11');
define ('SERVICE_VEHICLE_REGISTERATION', '12');
 
// check if table exists in database, if exists then deletes it
 
function Delete_Table($table)
{
	// make connection
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
	// query for delete table
	$sql = "DROP TABLE ".$table.";";
	//initializing return value
	$retval = false;
	// check if table exists
	if(mysql_query("DESCRIBE ".$table.""))
	{
    // Exists so delete
	$retval = mysql_query( $sql) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
	}
	else
	{
		//table didnt exist so return true
		$retval = true;
	}
	// close db connection
	mysql_close($link);
	//return
	return $retval;
}
 
// this function create a new parking table and return a boolean saying whether 
// creation of table was successful or not
function Create_Table($table, $ROWS)
{
 	// delete old table if exists
	Delete_Table($table);
	// write code for creating two new tables with name format user_parking_xW
	// where x is 4 or 2 and user is the user_name
	// set status as true if table creation is successful
	$sql = "CREATE TABLE ".$table." (slot_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, vehicle_no VARCHAR(20) NOT NULL, mobile_no INT NOT NULL, Time INT NOT NULL, Lim INT NOT NULL DEFAULT ".$ROWS." );";
	// establish connection to db							  
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
	//execute query to create table 
	$retval = mysql_query( $sql) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
        if($retval)
	{
		// insert a dummy row with all values set as 0, this makes sure table is never empty
		$qry = "INSERT INTO ".$table." (vehicle_no, mobile_no, Time) VALUES ('0', 0, 0);";
        	$newresult = mysql_query( $qry) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());		
	}
	else
	{
		echo "table creation failed";
	}
	//close connection with db
        mysql_close($link);
 
	return $retval;
 
}
 
//
//function Recreate_Table()
//{
//	Create_Table();
//	
//	return $status;
//}
 
// delete contents of entire table and reset the primary key, return 1 when success
function Reset_Table($table)
{
	// clean the contents of all the rows in table and reset primary key
	$sql = "TRUNCATE ".$table.";";
	// establish link
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
	//exec query
    	$retval = mysql_query( $sql) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
	// insert a dummy row with all values as 0 to make sure table doesnt remain empty
	$qry = "INSERT INTO ".$table." (vehicle_no, mobile_no, Time) VALUES ('0', 0, 0);";
	$newresult = mysql_query( $qry) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
	//close db
	mysql_close($link);
 
	return $retval;
}
 
// this function returns the number of rows which have vehicle_id !=0, this basically implies the number of slots which are 
// already booked
function Table_Status($table)
{
	// connect to db
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
	// query to select rows which are already blocked
	$query = " SELECT vehicle_no FROM ".$table." WHERE vehicle_no != '0';";
	$result = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
	$status = 0;
    	// get num of rows which have vehicles id
	$status = mysql_num_rows($result);
    	// close db
	mysql_close($link);
 
	return $status;
}

// this function returns the value at the Lim field of the table
function Get_Table_Lim($table)
{
	// connect to DB
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
	// query field 'Lim' from table and get only one field
	$query = "SELECT Lim FROM ".$table." LIMIT 1 ;";
	$result = mysql_query($query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
	// format the result in an associative array
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	// store the value from array
	$retval = $row["Lim"];
	// free the result
	mysql_free_result($result);
	// close db
	mysql_close($link);
    
	return $retval;
}
 
function Block_Parking($table, $vehicle, $mobile)
{
	// get time
	$time = time();
	// get the table status
   	$status = Table_Status($table);
	// get table limit
	$table_lim = Get_Table_Lim($table);
	// initialize return value to 0
	$retval = 0;
	// check if any slot is empty, if yes then get the empty slot, or if table has not reached its limit,
	//then insert a new row
	if ($table_lim > $status)
	{
	        // there is space in table, connect to the DB
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
		// get the rows which have no vechile in them means they were previously got unblocked
		$query = "SELECT * FROM ".$table." WHERE vehicle_no = '0';";
		$result = mysql_query($query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
		// get the number of rows
		$numResults = mysql_num_rows($result);
                //if there are no rows which are free, we will insert one more row as there is space in table
		if ($numResults == 0)
		{
                        // insert the row with the data
			$qry = "INSERT INTO ".$table." (vehicle_no, mobile_no, Time) VALUES ('$vehicle', $mobile, $time);";
        	        $newresult = mysql_query( $qry) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
			// get the row number which was inserted and save it for slot number to return
			$retval = mysql_insert_id();
 
		}
		else
		{       // came here because there was atleast one row which was unblocked earlier
			// fetch all the results in an associative array
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			// get the slot_id value of first row fetched 
			$slot = $row["slot_id"];
			// free memory
			mysql_free_result($result);
                        // just making sure with this if condition that we arent exceeding the table limit
			if($slot <= $table_lim)
			{
			        // updating the row with new data
			 	$query = " UPDATE ".$table." SET vehicle_no = '".$vehicle."', mobile_no = ".$mobile.", Time = ".$time." WHERE slot_id = '$slot';";
   				$result = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
				// returning the slot_id
				$retval = $slot;
			}
		}
                //close DB
		mysql_close($link);
	}
	else
	{
		echo "no free slot";
	}
 
	return $retval;
 
}
 
// function to unblock a parking space 
function Unblock_Parking($table, $vehicle)
{
        // connect to the DB
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   	// find the row having the vehicle nummber and unblock it
   	$query = " UPDATE ".$table." SET vehicle_no = 0, mobile_no = 0, Time = 0 WHERE vehicle_no = '$vehicle';";
   	$retval = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());

	// close db
	mysql_close($link);

	return $retval;
}
 
// function for user login, authentication is dependent on how password was hashed during signup 
function User_Login()
{
   if (isset($_GET['user']) && isset($_GET['password']))
	{
                // get user name and password from url
		$user = $_GET['user'];
		$password = $_GET['password'];
		//connect to DB
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
		$user=mysql_real_escape_string($user);
   	 	// find if username exist in table
   	 	$query = "SELECT * FROM login WHERE username = '$user';";
		$result = mysql_query($query);
		//get the number of rows having that username
		$numResults = mysql_num_rows($result);
		//if number of rows is zero that means that user isnt registered yet
		if($numResults==0)
		{
			echo "user does not exist";
		}
		else
		{
		        // get data in an associative array
			$userData = mysql_fetch_array($result, MYSQL_ASSOC);
        	        // take the salt of the first result and hash it with password
        	        $hash = hash('sha256', $password.$userData['salt']);
			mysql_free_result($result);
       		        if($hash != $userData['password']) // Incorrect password.
        	        {
           		
           		        echo "invalid password";
			}
			else
			{ 

				/* output in necessary format */
				$token = $userData['uid'];
				$usertype = $userData['user_type'];
				header('Content-type: application/json');
                session_start();
	        	$_SESSION["Login"] = "YES";
	        	$_SESSION["Time"] = time();
	        	$_SESSION["Username"] = $user;
	        	$_SESSION["UserType"] = $usertype;
				$_SESSION["uid"] = $token;
            	$_SESSION["Sessionid"] = session_id();								
            	echo json_encode(array('session'=>$_SESSION));
				$query = " UPDATE login SET sessionid = ".$_SESSION['Sessionid']." WHERE vehicle_no = '$vehicle';";
   	$retval = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
			}
		}	
		@mysql_close($link);
	}
}
// function to logout an user
function User_Logout()
{
 
 	// session_start();
  	// Unset all of the session variables.
  	$_SESSION = array();
  	// If it's desired to kill the session, also delete the session cookie.
 	 // Note: This will destroy the session, and not just the session data!
 	if (ini_get("session.use_cookies")) 
  	{
    	        $params = session_get_cookie_params();
    	        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"] );
        }
 
	// Finally, destroy the session.
	session_destroy();
	echo "you have successfully logged out";
 
}


function NewUser() 
{ 
 $name = $_GET['name'];
 $username = $_GET['user'];
 $email = $_GET['email'];
 $password = $_GET['password'];
 $usertype = $_GET['user_type'];
 $random_salt = hash('sha256', uniqid(mt_rand(1, mt_getrandmax()), true));
 // Create salted password 
 $password = hash('sha256', $password . $random_salt);
 $link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
 mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
 $query = "INSERT INTO login (name,username,user_type,email,password, salt) VALUES ('$name','$username','$usertype','$email','$password', '$random_salt')"; 
 $data = mysql_query ($query)or die(mysql_error());
 // close db
 mysql_close($link);
 if($data) 
 { 
   echo "YOUR REGISTRATION IS COMPLETED..."; 
 }
} 

 
function User_Signup()
{
	if(!empty($_GET['user'])) //checking the 'user' name which is from Sign-Up.html, is it empty or have some text 
 	{ 
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   		$query = mysql_query("SELECT * FROM login WHERE username = '$_GET[user]'") or die(mysql_error());
   		// close db
		mysql_close($link);
		$numResults = mysql_num_rows($query);

   		//if(!$row = mysql_fetch_array($query) or die(mysql_error()))
   		if($numResults == 0)
   		{ 
			NewUser();
   		} 
   		else
   		{
     		echo "SORRY...USERNAME ALREADY EXISTS..."; 
   		}	  
 	}
}
 
function User_Delete()
{

	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   	$query = mysql_query("DELETE FROM login WHERE username = '$_GET[user]'") or die(mysql_error());
	echo "user $_GET[user] was deleted from database";
	mysql_close($link);
}

function Add_Vehicle()
{
	if (isset($_GET['vehicleid']) && isset($_GET['name']) && isset($_GET['phone']) && isset($_GET['company']) && isset($_GET['wheels']))
	{
		$vehicleid = $_GET['vehicleid'];
		$name = $_GET['name'];
		$mobilenum = $_GET['phone'];
		$companyname = $_GET['company'];
		$wheels = $_GET['wheels'];
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   		$query = mysql_query("SELECT * FROM vehicles WHERE vehicle_no = '$vehicleid'") or die(mysql_error());
		$numResults = mysql_num_rows($query);

   		if($numResults == 0)
   		{ 
    		 $query = "INSERT INTO login (name,vehicle_no,wheeler,company_name, mobile_no) VALUES ('$name', '$vehicleid', '$wheels', '$companyname', '$mobilenum')"; 
 $data = mysql_query ($query)or die(mysql_error());
 			if ($data)
			{
				echo "Registeration successful";
			}
			else
			{
				echo "Registeration failed";
			}
   		} 
   		else
   		{
     		echo "SORRY...VEHICLE ALREADY EXISTS IN DATABASE..."; 
   		}
		// close db
		mysql_close($link);
	}
}

function UpdateCompanyInfo($company, $floor, $num2w, $num4w)
{
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   	$query = mysql_query("SELECT * FROM company_info WHERE company_name = '$company'") or die(mysql_error());
	$numResults = mysql_num_rows($query);
	if(numResults==0)
	{
		$query = "INSERT INTO company_info (company_name,parking_floor,2w_slots, 4w_slots) VALUES ('$company', '$floor', '$num2w', '$num4w')"; 
 		$data = mysql_query ($query)or die(mysql_error());
 		if ($data)
		{
			echo "update successful";
		}
		else
		{
			echo "update failed";
		}
	}
	else
	{
		$query = " UPDATE company_info SET parking_floor = '$floor', 2w_slots = '$num2w', 4w_slots = '$num4w' WHERE comapany_name = '$company';";
   		$result = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 		if($result)
		{
			echo "update successful";			
		}
		else
		{
			echo "update failed";
		}
	}
}
//////////////////////////////////////////////////////////////
/////						Main					//////////
//////////////////////////////////////////////////////////////
session_start();

if (isset($_GET[service]))
{
	
	$service = $_GET[service];
	switch($service)
	{
		case SERVICE_USER_LOGIN:
		
			User_Login(); //done
			break;
		
		case SERVICE_USER_LOGOUT : // done
 
			User_Logout();
			break;
			
		case SERVICE_USER_SIGNUP :
 
			User_Signup(); //needs update for user type
			break;
		
		case SERVICE_USER_DELETE : //done
 
			User_Delete();
			break;
		
		case SERVICE_VEHICLE_REGISTERATION : 
		
			Add_Vehicle();
			break;
			
		case SERVICE_SETTINGS_CONFIGURE : 
 			
			$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
			mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   			$query = mysql_query("SELECT * FROM login WHERE username = '$user'") or die(mysql_error());
			$userData = mysql_fetch_array($query, MYSQL_ASSOC);
			$user_type = $userData['user_type'];
			mysql_close($link);
			
			if($user_type=="admin")
			{
				if (isset($_GET["company"]) && isset($_GET["num_2W"]) && isset($_GET["num_4W"]) && isset($_GET["floor"]))
				{
					$company = $_GET["company"];
					$floor = $_GET["floor"];
					$num2w = $_GET["num_2W"];
					$num4w = $_GET["num_4W"];
					$table2W = $company."parking_2W";
					$table4W = $company."parking_4W";
					
					UpdateCompanyInfo($company, $floor, $num2w, $num4w); //done
					
					Create_Table($table2W, $num2w);
					Create_Table($table4W, $num4w);
						
				}
				else
				{
					echo "insufficient params";
				}
			}
			else
			{
				echo "permission denied, insufficient privilages";
			}
			
			break;
		
		case SERVICE_BLOCK :
			
			if (isset($_GET["vehicleid"]))
			{
				$vehicleid = $_GET["vehicleid"];
				$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
				mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   				$query = mysql_query("SELECT * FROM vehicles WHERE vehicle_no = '$vehicleid'") or die(mysql_error());
				$vehicleData = mysql_fetch_array($query, MYSQL_ASSOC);	
				$wheels = $vehicleData['wheeler'];
				$mobilenum = $vehicleData['mobile_no'];
				$company = $vehicleData['company_name'];
				$query = mysql_query("SELECT * FROM company_info WHERE company_name = '$company'") or die(mysql_error());
				$CompanyData = mysql_fetch_array($query, MYSQL_ASSOC);
				$floor = $CompanyData['parking_floor'];
				mysql_close($link);
				
				$table2W = $company."parking_2W";
				$table4W = $company."parking_4W";
				
				if ($wheels == 2)
				{
					$block = Block_Parking($table2W, $vehicleid, $mobilenum);		
				}
				else if ($wheels == 4)
				{
					$block = Block_Parking($table4W, $vehicleid, $mobilenum);
				}
				else
				{	
					echo "invalid number of wheels";
					exit();
				}
 
				if ($block)
				{
 
		            echo json_encode(array('slot_id'=>$wheels."W".$retval, 'floor'=>$floor, 'time'=>localtime(), 'vehicle'=>$vehicleid, 'contact'=>$mobilenum ));
 
				}
				else
				{
					echo "failed to block any parking";
				}
			}
			break;
 
		case SERVICE_UNBLOCK : //done
 
			if (isset($_GET["vehicleid"]))
			{
				
				$vehicleid = $_GET["vehicleid"];
				$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
				mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   				$query = mysql_query("SELECT * FROM vehicles WHERE vehicle_no = '$vehicleid'") or die(mysql_error());
				$vehicleData = mysql_fetch_array($query, MYSQL_ASSOC);	
				$wheels = $vehicleData['wheeler'];
				$mobilenum = $vehicleData['mobile_no'];
				$company = $vehicleData['company_name'];
				$query = mysql_query("SELECT * FROM company_info WHERE company_name = '$company'") or die(mysql_error());
				$CompanyData = mysql_fetch_array($query, MYSQL_ASSOC);
				$floor = $CompanyData['parking_floor'];
				mysql_close($link);
				$table2W = $company."parking_2W";
				$table4W = $company."parking_4W";
				
				if ($wheels == 2)
				{
					$unblock = Unblock_Parking ($table2W, $vehicleid);
				}
				else if ($wheels == 4)
				{
					$unblock = Unblock_Parking($table4W, $vehicleid);
				}
				else
				{
					echo "these vehicles are not supported";
				}
				
				if($unblock)
				{
					echo json_encode(array('time'=>localtime(), 'vehicle'=>$vehicleid, 'contact'=>$mobilenum ));
				}
				else
				{
					echo "failed to unblock";
				}
			}
			else
			{
				echo "insuffiecient params in URI";
			}
			break;
			
		case SERVICE_STATUS :
		
			$company = $_GET['company'];
			$table2W = $company."parking_2W";
			$table4W = $company."parking_4W";
			$w4_status = Table_Status($table4W);
			$w2_status = Table_Status($table2W);
			$w4_lim = Get_Table_Lim($table4W);
			$w2_lim = Get_Table_Lim($table2W);
			$w2_free = $w2_lim - $w2_status;
			$w4_free = $w4_lim - $w4_status;
			$Wheeler_2 = array('blocked'=> $w2_status, 'free' =>$w2_free);
			$Wheeler_4 = array('blocked'=> $w4_status, 'free' =>$w4_free);
			echo json_encode(array('company'=> $company,'four_wheeler'=>$Wheeler_4, 'two_wheeler'=>$Wheeler_2 ));
			break; 			
		
//		case SERVICE_SETTINGS_RESET :
//
//			if ((Reset_Table($table2W)) && (Reset_Table($table4W)))
//			{
//				echo "success"; 
//			}
// 
//			break;
		default:
			echo "service type not supported";
			echo $service;
			break;
	}
	
	
}
else
{
	echo "no service defined";
}


?>