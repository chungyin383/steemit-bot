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
	
		case 'getUserStat':
			header('Content-Type: application/json;');
			if ($result = $conn->query("SELECT TgList.tg, TgList.steemit, SteemitList.strength FROM TgList LEFT JOIN SteemitList ON TgList.steemit = SteemitList.steemit ORDER BY TgList.tg, TgList.steemit")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
			
		case 'getTgList':
			header('Content-Type: application/json;');
			if ($result = $conn->query("SELECT * FROM TgList ORDER BY tg")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
			
		case 'getIndDelegation':
			header('Content-Type: application/json;');
			if ($result = $conn->query("
					SELECT Delegation.steemit, Delegation.delegation
					FROM Delegation
					RIGHT JOIN
						( SELECT steemit, MAX(timestamp) AS ts
						FROM Delegation
						GROUP BY steemit
						) AS grouped
					ON Delegation.steemit = grouped.steemit
					AND Delegation.timestamp = grouped.ts
				")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
			
		case 'updateDelegation':
			if (isset($_POST['data'])){
				foreach ($_POST['data'] as $value){
					if ($stmt = $conn->prepare("
							INSERT INTO Delegation (steemit, delegation)
							VALUES (?, ?)
						")){
						$stmt->bind_param("sd", $value[0], $value[1]);
						if($stmt->execute()!==TRUE){
							echo "Error: " . $conn->error;
						}
					}
				}
			}
			break;
			
		case 'getDelegationAndHist':
			header('Content-Type: application/json;');
			if ($result = $conn->query("
					SELECT 
						TgList.tg, 
						SUM(d.delegation) AS delegation,
						CONCAT('https://steemit.com/@', TgList.steemit, '/', VoteHistToday.permlink) AS url
					FROM (
						SELECT Delegation.*
						FROM Delegation
						INNER JOIN
							( SELECT steemit, MAX(timestamp) AS ts
							FROM Delegation
							GROUP BY steemit
							) AS grouped
						ON Delegation.steemit = grouped.steemit
						AND Delegation.timestamp = grouped.ts
					) AS d
					LEFT JOIN TgList ON TgList.steemit = d.steemit
					LEFT JOIN (
						SELECT *
						FROM VoteHist
						WHERE DATE(CONVERT_TZ(NOW(),'+00:00','+04:00')) = DATE(CONVERT_TZ(VoteHist.timestamp,'+00:00','+04:00'))
					) as VoteHistToday ON TgList.steemit = VoteHistToday.steemit
					GROUP BY TgList.tg
				")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
			
		case 'userExist':
			if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM TgList WHERE steemit = ?")){
				$stmt->bind_param("s", $_POST['steemit']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			echo ($result['count']==1);
			break;
			
		case 'getFullHistory':
			header('Content-Type: application/json;');
			if ($result = $conn->query("
					SELECT
						CONVERT_TZ(timestamp,'+00:00','+08:00') AS timestamp,
						CONCAT('https://steemit.com/@', steemit, '/', permlink) AS url
					FROM VoteHist
					ORDER BY timestamp DESC
				")){
				$myArray = array();
				while($row = $result->fetch_assoc()){
					 $myArray[] = $row;
				}
				echo json_encode($myArray);
			}
			break;
					
		case 'changeWeight':
			if ($stmt = $conn->prepare("UPDATE SteemitList SET strength = ? WHERE steemit = ?")){
				$stmt->bind_param("ss", $_POST['strength'], $_POST['steemit']);
				if($stmt->execute()===TRUE){
					echo "Voting strength of @" . $_POST['steemit'] . " has been updated.";
				} else {
					echo "Error: " . $conn->error;
				}
			}
			break;
		
		case 'amendKey':
			if ($stmt = $conn->prepare("UPDATE SteemitList SET wif = ? WHERE steemit = ?")){
				$stmt->bind_param("ss", $_POST['wif'], $_POST['steemit']);
				if($stmt->execute()===TRUE){
					echo "Private posting key of @" . $_POST['steemit'] . " has been updated.";
				} else {
					echo "Error: " . $conn->error;
				}
			}
			break;
		
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
			
		case 'addAcc':
			if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM TgList WHERE tg = ?")){
				$stmt->bind_param("s", $_POST['tg']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
			}
			if ($result['count']>0){
				if ($stmt = $conn->prepare("INSERT INTO TgList(tg,steemit) VALUES(?,?)")){
					$stmt->bind_param("ss", $_POST['tg'], $_POST['steemit']);
					if($stmt->execute()===TRUE){
						if ($stmt = $conn->prepare("INSERT INTO SteemitList(steemit,wif,strength) VALUES(?,?,10000)")){
							$stmt->bind_param("ss", $_POST['steemit'], $_POST['wif']);
							if($stmt->execute()===TRUE){
								echo "New account @" . $_POST['steemit'] . " has been added for " . $_POST['tg'] . ".";
							} else {
								echo "Error: " . $conn->error;
							}
						}
					} else {
						echo "Error: " . $conn->error;
					}
				}
			} else {
				echo "Error: " . $_POST['tg'] . " is not a VIP member in our telegram group.";
			}
			break;
			
		case 'validateBotUse':
			header('Content-Type: application/json;');
			$arr = array();
			$arr['error'] = true;
			
			// check if user exist
			if ($stmt = $conn->prepare("SELECT tg FROM TgList WHERE steemit = ?")){
				$stmt->bind_param("s", $_POST['steemit']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
				$arr['tg'] = $result['tg'];
			}
			
			// check if link has been voted before
			if ($stmt = $conn->prepare("SELECT CONVERT_TZ(timestamp,'+00:00','+08:00') AS timestamp FROM VoteHist WHERE steemit = ? AND permLink = ?")){
				$stmt->bind_param("ss", $_POST['steemit'], $_POST['permLink']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
				$arr['timestamp'] = $result['timestamp'];
			}
			
			// check delegation
			if ($stmt = $conn->prepare("
				SELECT 
					SUM(d.delegation) AS delegation
				FROM (
					SELECT Delegation.*
					FROM Delegation
					INNER JOIN
						( SELECT steemit, MAX(timestamp) AS ts
						FROM Delegation
						GROUP BY steemit
						) AS grouped
					ON Delegation.steemit = grouped.steemit
					AND Delegation.timestamp = grouped.ts
				) AS d
				LEFT JOIN TgList ON TgList.steemit = d.steemit
				WHERE TgList.tg = ?
				GROUP BY TgList.tg
			")){
				$stmt->bind_param("s", $arr['tg']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
				$arr['delegation'] = $result['delegation'];
			}
			
			// check today has used bot or not
			if ($stmt = $conn->prepare("
				SELECT permlink
				FROM VoteHist
				LEFT JOIN TgList
				ON TgList.steemit = VoteHist.steemit
				WHERE DATE(CONVERT_TZ(NOW(),'+00:00','+04:00')) = DATE(CONVERT_TZ(VoteHist.timestamp,'+00:00','+04:00'))
				AND tg = ?
			")){
				$stmt->bind_param("s", $arr['tg']);
				$stmt->execute();
				$result = $stmt->get_result()->fetch_assoc();
				$arr['usedlink'] = $result['permlink'];
			}
			
			if ($arr['tg'] == NULL){
				$arr['message'] = 'The author of the submitted post does not belong to our VIP group. Therefore this post cannot be voted.';
			} else {
				if ($arr['timestamp'] != NULL){
					$arr['message'] = 'Hi ' . $arr['tg'] . ', this link has been upvoted by the bot previously at ' . $arr['timestamp'] . ', therefore you are allowed to use the bot on this link again.';
					$arr['error'] = false;
				} else {
					if ($arr['delegation'] == 0){
						$arr['message'] = 'Hi ' . $arr['tg'] . ', you have not delegated any SP to our mutual account <i>hkfund</i>, therefore you cannot use the bot now. If you believe this is a mistake, please close this tab and reload again, this may be a system error.';
					} else {
						if ($arr['usedlink'] != NULL){
							$link = 'http://steemit.com/@' . $_POST['steemit'] . '/' . $arr['usedlink'];
							$arr['message'] = 'Hi ' . $arr['tg'] . ', you have used the bot on another link <a href="' . $link . '">' . $link . '</a> today already, therefore you cannot use the bot again today.';
						} else {
							$arr['message'] = 'Hi ' . $arr['tg'] . ', your post is being upvoted, please wait and do not close the tab before all votes have been casted.';
							$arr['error'] = false;
						}
					}
				}
			}
				
			echo json_encode($arr);
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