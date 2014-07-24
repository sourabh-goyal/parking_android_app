<?php
 
include '../sql_access.php';
 
define('SERVICE_SETTINGS_CREATE','1');	// will create new tables
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
	// check 
	if ($table_lim > $status)
	{
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
		$query = "SELECT * FROM ".$table." WHERE vehicle_no = '0';";
		$result = mysql_query($query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
		$numResults = mysql_num_rows($result);
 
		if ($numResults == 0)
		{
 
			$qry = "INSERT INTO ".$table." (vehicle_no, mobile_no, Time) VALUES ('$vehicle', $mobile, $time);";
            $newresult = mysql_query( $qry) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
			$retval = mysql_insert_id();
 
		}
		else
		{
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$slot = $row["slot_id"];
			mysql_free_result($result);
 
			if($slot <= $table_lim)
			{
			 	$query = " UPDATE ".$table." SET vehicle_no = '".$vehicle."', mobile_no = ".$mobile.", Time = ".$time." WHERE slot_id = '$slot';";
   				$result = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error()); 
				$retval = $slot;
			}
		}
 
		mysql_close($link);
	}
	else
	{
		echo "no free slot";
	}
 
	return $retval;
 
}
 
function Unblock_Parking($table, $vehicle)
{
	$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
	mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
   	$query = " UPDATE ".$table." SET vehicle_no = 0, mobile_no = 0, Time = 0 WHERE vehicle_no = '$vehicle';";
   	$retval = mysql_query( $query) or die("A MySQL error has occurred.<br />Error: (" . mysql_errno() . ") " . mysql_error());
	mysql_close($link);
	if ($retval)
	{
		echo "successfully unblocked your parking";
	}
	return $retval;
}
 
function User_Login()
{
   if (isset($_GET['user']) && isset($_GET['password']))
	{
 
		$user = $_GET['user'];
		$password = $_GET['password'];
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die('Cannot connect to the DB');
		mysql_select_db(DB_NAME,$link) or die('Cannot select the DB');
		$user=mysql_real_escape_string($user);
   	 	$query = "SELECT * FROM login WHERE username = '$user';";
		$result = mysql_query($query);
		$numResults = mysql_num_rows($result);
		if($numResults==0)
		{
			echo "user does not exist";
		}
		else
		{
			$userData = mysql_fetch_array($result, MYSQL_ASSOC);
        	$hash = hash('sha256', $password.$userData['salt']);
			mysql_free_result($result);
       		if($hash != $userData['password']) // Incorrect password. So, redirect to login_form again.
        	{
           		//header('Location: index.php');
           		echo "invalid password";
			}
			else
			{ 
				//header('Location: home.html');
				/* output in necessary format */
				$token = $userData['uid'];
				header('Content-type: application/json');
				//echo json_encode($token);
            	session_start();
	        	$_SESSION["Login"] = "YES";
	        	$_SESSION["Time"] = time();
	        	$_SESSION["Username"] = $user;
            	$_SESSION["Sessionid"] = session_id();
            	echo json_encode(array('session'=>$_SESSION));
			}
		}	
		@mysql_close($link);
	}
}
 
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
    	setcookie(session_name(), '', time() - 42000,
   					$params["path"], $params["domain"],
    				$params["secure"], $params["httponly"]
    			  );
    }
 
	// Finally, destroy the session.
	session_destroy();
	echo "you have successfully logged out";
 
 
}
 
function User_Signup()
{
}
 
function User_Delete()
{
}
 
session_start();
 
if (($_SESSION["Login"] != "YES") &&
	!((isset($_GET["user"])) && ($_GET["service"] == SERVICE_USER_LOGIN) &&(isset($_GET["password"])))) // trying to login
{
	// not logged in`
	echo "please login";
	exit();
}
else
{
	if(isset($_GET["user"]) && isset($_GET["service"]))
	{
		$service = $_GET["service"];
		$user = $_GET["user"];
		$table2W = $user."parking_2W";
		$table4W = $user."parking_4W";
 
		switch($service)
		{
		case SERVICE_SETTINGS_RESET :
 
			if ((Reset_Table($table2W)) && (Reset_Table($table4W)))
			{
				echo "success"; 
			}
 
			break;
 
		case SERVICE_SETTINGS_CREATE : //done
 
			if(isset($_GET["num_2W"]) && isset($_GET["num_4W"]))
			{
                $num2w = $_GET["num_2W"];
                $num4w = $_GET["num_4W"];
 
				if((Create_Table($table2W, $num2w)) && (Create_Table($table4W, $num4w)))
				{
					echo "success";
				}
 
			}
			break;
 
		case SERVICE_SETTINGS_DELETE : //done
 
			// delete parking space
			// delete user_parking_4W and user_parking_2W
			if ((Delete_Table($table2W)) && (Delete_Table($table4W)))
			{
				echo "successly deleted all the tables";
			}
			break;
 
//		case SERVICE_SETTINGS_EXPAND :
//			Expand_Table();
//			break;
 
		case SERVICE_USER_LOGOUT : // done
 
			User_Logout();
			break;
 
		case SERVICE_USER_SIGNUP :
 
			User_Signup();
			break;
 
		case SERVICE_USER_LOGIN : // done
 
			User_Login();
			break;
 
		case SERVICE_USER_DELETE :
 
			User_Delete();
			break;
 
		case SERVICE_BLOCK :
			if (isset($_GET["vehicleid"]) && isset($_GET["wheels"]) && isset($_GET["mobile"]))
			{
				$wheels = $_GET["wheels"];
				$vehicleid = $_GET["vehicleid"];
				$mobilenum = $_GET["mobile"];
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
 
		                     echo json_encode(array('slot_id'=>"4w".$retval, 'time'=>localtime(), 'vehicle'=>$vehicleid, 'contact'=>$mobilenum ));
 
				}
				else
				{
					echo "failed to block any parking";
				}
			}
			break;
 
		case SERVICE_UNBLOCK : //done
 
			if (isset($_GET["vehicleid"]) && isset($_GET["wheels"]))
			{
				$wheels = $_GET["wheels"];
				$vehicleid = $_GET["vehicleid"];
				if ($wheels == 2)
				{
					Unblock_Parking ($table2W, $vehicleid);
				}
				else if ($wheels == 4)
				{
					Unblock_Parking($table4W, $vehicleid);
				}
				else
				{
					echo "these vehicles are not supported";
				}
			}
			else
			{
				echo "insuffiecient params in URI";
			}
			break;
		case SERVICE_STATUS :
			$w4_status = Table_Status($table4W);
			$w2_status = Table_Status($table2W);
			$w4_lim = Get_Table_Lim($table4W);
			$w2_lim = Get_Table_Lim($table2W);
			$w2_free = $w2_lim - $w2_status;
			$w4_free = $w4_lim - $w4_status;
			$Wheeler_2 = array('blocked'=> $w2_status, 'free' =>$w2_free);
			$Wheeler_4 = array('blocked'=> $w4_status, 'free' =>$w4_free);
			echo json_encode(array('four_wheeler'=>$Wheeler_4, 'two_wheeler'=>$Wheeler_2 ));
 
			break;
//		case SERVICE_UNBLOCK_ALL :
//			Reset_Table();
//			break;
		default:
			echo "service type not supported";
			echo $service;
			break;
		}
	}	
	else
	{
		echo "error in your request";
	}
}
 
 
?>
 
