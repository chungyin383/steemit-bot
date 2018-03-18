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
		case 'recordUsage':
			$result = $conn->query("DELETE FROM voteHistory ORDER BY timestamp ASC LIMIT 1");
			if ($stmt = $conn->prepare("INSERT INTO voteHistory(url) VALUES(?)")){
				$stmt->bind_param("s", $_POST['url']);
				$stmt->execute();
			}
			break;
		case 'getUserStat':
			header('Content-Type: application/json;');
			if ($result = $conn->query("SELECT VIPlist.*, wifVoterList.weight FROM VIPlist LEFT JOIN wifVoterList ON VIPlist.steemit = wifVoterList.username ORDER BY VIPlist.category, VIPlist.tg")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;	
		case 'getHistory':
			if ($result = $conn->query("SELECT url, CONVERT_TZ(timestamp,'+00:00','+08:00') AS timestamp FROM voteHistory ORDER BY timestamp DESC")){
				$html = '<table><tr><th>Timestamp</th><th>URL</th></tr>';
				while($row = $result->fetch_assoc()){
					$html .= '<tr><td>' . $row['timestamp'] . '</td><td><a href="' . $row['url'] . '">' . $row['url'] . '</a></td></tr>';
				}
				echo $html . '</table>';
			}
			break;
		case 'getWifVoterList':
			header('Content-Type: application/json;');
			if ($result = $conn->query("SELECT * FROM wifVoterList")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
		case 'changeWeight':
			if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM wifVoterList WHERE username = ?")){
				$stmt->bind_param("s", $_POST['username']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			if ($result['count']==1){
				if ($stmt = $conn->prepare("UPDATE wifVoterList SET weight = ? WHERE username = ?")){
					$stmt->bind_param("ss", $_POST['weight'], $_POST['username']);
					if($stmt->execute()===TRUE){
						echo "Voting strength of @" . $_POST['username'] . " has been updated.";
					} else {
						echo "Error: " . $conn->error;
					}
				}
			} else {
				echo "This user has not submitted his private posting key to @kenchung, thus the voting strength cannot be amended. ";
			}
			break;
		case 'addKey':
			if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM wifVoterList WHERE username = ?")){
				$stmt->bind_param("s", $_POST['username']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			if ($result['count']==1){
				if ($stmt = $conn->prepare("UPDATE wifVoterList SET wif = ? WHERE username = ?")){
					$stmt->bind_param("ss", $_POST['wif'], $_POST['username']);
					if($stmt->execute()===TRUE){
						echo "Private posting key of @" . $_POST['username'] . " has been updated.";
					} else {
						echo "Error: " . $conn->error;
					}
				}
			} else {
				$stmt = $conn->prepare("INSERT INTO wifVoterList(username,wif,weight) VALUES(?,?,10000)");
				$stmt->bind_param("ss", $_POST['username'], $_POST['wif']);
				if($stmt->execute()===TRUE){
					$stmt = $conn->prepare("UPDATE VIPlist SET category = 1 WHERE steemit = ?");
					$stmt->bind_param("s", $_POST['username']);
					if($stmt->execute()===TRUE){
						echo "Private posting key of @" . $_POST['username'] . " has been added.";
					} else {
						echo "Error: " . $conn->error;
					}
				} else {
					echo "Error: " . $conn->error;
				}
			}
			break;
		case 'addAcc':
			if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM wifVoterList WHERE username = ?")){
				$stmt->bind_param("s", $_POST['username']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			$cat = ($result['count']==1 ? 1 : 3);
			if ($stmt = $conn->prepare("INSERT INTO VIPlist(tg,steemit,category) VALUES(?,?,?)")){
				$stmt->bind_param("ssi", $_POST['tgname'], $_POST['username'], $cat);
				if($stmt->execute()===TRUE){
					echo "Steemit account @" . $_POST['username'] . " has been added.";
				} else {
					echo "Error: " . $conn->error;
				}
			}
			break;
	}
}
$conn->close();

?>