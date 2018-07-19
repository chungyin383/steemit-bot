<?php
include('init.php');
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
// Check connection
if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['action'])) {
	
    switch ($_POST['action']) {
		
		case 'getWifVoterList':
			header('Content-Type: application/json;');
			if ($result = $conn->query("SELECT * FROM SteemitList")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
			
		case 'addtg':
			if ($stmt = $conn->prepare("INSERT INTO TgList(tg, steemit) VALUES (?,?)")){
				$stmt->bind_param("ss", $_POST['tg'], $_POST['steemit']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			break;
			
		case 'recordUsage':
			$result = $conn->query("DELETE FROM VoteHist ORDER BY timestamp ASC LIMIT 1");
			if ($stmt = $conn->prepare("INSERT INTO VoteHist(steemit, permlink) VALUES(?,?)")){
				$stmt->bind_param("ss", $_POST['steemit'], $_POST['permLink']);
				$stmt->execute();
			}
			break;	
		
	}
}
$conn->close();

?>