<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" charset="utf-8"/>
	<link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
	<title>LIHKG Steemit Vote Bot</title>
	<style>
	body{
		font-family: 'Montserrat', 'Microsoft JhengHei', sans-serif;
		margin: 0;
		padding: 0;
		font-size:16px;
	}
	input[type="text"] {
		display: block;
		width: 100%;
		font-family: sans-serif;
		font-size: 18px;
		appearance: none;
		box-shadow: none;
		border-radius: none;
		padding: 10px;
		margin: 5px 0;
		border: solid 1px #dcdcdc;
		transition: box-shadow 0.3s, border 0.3s;
		-webkit-box-sizing: border-box; /* Safari/Chrome, other WebKit */
		-moz-box-sizing: border-box;    /* Firefox, other Gecko */
		box-sizing: border-box;         /* Opera/IE 8+ */
	}
	input[type="text"]:focus {
		outline: none;
		border: solid 1px #707070;
		box-shadow: 0 0 5px 1px #969696;
	}
	.button{
		display:block;
		width: 200px;
		margin-right: auto;
		margin-left: auto;
		margin-top: 20px;
		padding: 10px;
		cursor: pointer;
		vertical-align:middle;
		line-height: 2;
		text-align: center;
		background-color: #87CEFA;
		transition: background-color 0.5s ease; 
	}
	.button:hover{
		background-color: #00BFFF;
	}
	.section{
		padding:10px;
	}
	.header{
		font-size:30px;
		padding:15px;
		background-color: #87cefa;
		text-align: center; 
	}
	.title{
		font-size: 20px;
		font-weight: bold;
		padding:10px 0px;
	}
	.messagebox{
		border-left: 6px solid #2196F3;
		background-color: #ddffff;
		padding: 5px;
		margin: 5px 0;
		display: none;
	}
	.instruction {
		background-color: #FFFACD;
		padding: 10px;
		margin: 10px 0;
	}
	hr{
		border: 0;
		height: 1px;
		background: #333;
		background-image: linear-gradient(to right, #ccc, #333, #ccc);
		margin: 1px 0;
	}
	table {
		margin-top: 10px;
		border-collapse: collapse;
		width: 100%;
	}

	th, td {
		text-align: left;
		padding: 8px;
	}

	tr:nth-child(even){background-color: #f2f2f2}

	th {
		background-color: #4CAF50;
		color: white;
	}
	p {
		margin: 10px 0;
	}
	select {
		-webkit-appearance: button;
		-webkit-border-radius: 2px;
		-webkit-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1);
		-webkit-padding-end: 20px;
		-webkit-padding-start: 2px;
		-webkit-user-select: none;
		background-image: url(http://i62.tinypic.com/15xvbd5.png), -webkit-linear-gradient(#FAFAFA, #F4F4F4 40%, #E5E5E5);
		background-position: 97% center;
		background-repeat: no-repeat;
		border: 1px solid #AAA;
		color: #555;
		font-size: inherit;
		margin: 20px auto;
		overflow: hidden;
		padding: 5px 10px;
		text-overflow: ellipsis;
		white-space: nowrap;
		width: 500px;
		display: block;
	}
	</style>
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="./steem.min1.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
	
		const requiredSP = 2000;
		
		historyRefresh();
		statRefresh();
		selfCheck();
		
		$('#selfcheck_button').click(function(){
			selfCheck();
		});
		
		$('#upvote_button').click(function(){
			
			$('#upvote_button').hide();
			$('.upvote.messagebox').html('');
			$('.upvote.messagebox').css( "display", "none" );
			
			var temp = breakURL($("#upvote_url").val());
			var author = temp[0];
			var permlink = temp[1];
			var delay = Number($("#upvote_start").val()) * 60 * 1000;
			var random_range = Math.max(0.5, Number($("#upvote_length").val())) * 60 * 1000;
			var pad = Math.min(1, $("#upvote_strength").val()=="" ? 1 : Number($("#upvote_strength").val()) / 100);
			
			// validate bot use
			var data = {'action': 'validateBotUse', 'steemit': author, 'permLink': permlink};
			$.post('ajax.php', data, function (response) {
				
				$('#upvote_warning').html(response.message); 
				$('#upvote_warning').css( "display", "block" );
				
				// if passed checking, proceed to voting
				if (!response.error){
					
					// record usage if this link has not use bot previously 
					if (!response.timestamp){
						var data = {'action': 'recordUsage', 'steemit': author, 'permLink': permlink};
						$.post('ajax.php', data, function (response) {});
					}
					
					// get vest ratio
					steem.api.getDynamicGlobalProperties(function(err, result) {
						
						var steem_vest_ratio = parseFloat(result.total_vesting_fund_steem) / parseFloat(result.total_vesting_shares);
						
						// get Wif list
						$.post('ajax.php', {'action': 'getWifVoterList'}, function (wifVoterList) {
							
							var counter = 0;
							var time_start = new Date();
							var timerObj = setInterval(myTimer, 1000);
							$('#upvote_elpasedtime').css( "display", "block" );
							
							function myTimer() {
								var d = new Date();
								var minutes = Math.floor((d - time_start) / 60000);
								var seconds = (((d - time_start) % 60000) / 1000).toFixed(0);
								$('#upvote_elpasedtime').html("Time elapsed: " + minutes + ":" + (seconds < 10 ? '0' : '') + seconds);
							}
							
							for (var i in wifVoterList){
								var entitledVS = Math.min(1,(response.delegation * steem_vest_ratio / requiredSP ));
								if (wifVoterList[i].strength==0){
									$('#upvote_details').html($('#upvote_details').html() + wifVoterList[i].steemit + " has set a voting strength of 0% and thus has been skipped.<br>");
									updateStatus();
								} else {
									upvote(wifVoterList[i].wif, wifVoterList[i].steemit, +(Number(wifVoterList[i].strength) * pad * entitledVS).toFixed(0));
								}
							}
							
							function updateStatus(){
								$('#upvote_status').html(++counter + " out of " + wifVoterList.length + " accounts have been processed.");
								if (counter == wifVoterList.length) {
									$('#upvote_warning').html("Completed, you may close the browser now.");
									clearInterval(timerObj);
									$('#upvote_button').show();
								}
							}
							
							// set upvote events to be executed after some random time
							function upvote(wif,username,weight){
								
								setTimeout(function(){
									
									// get pending reward
									getRewards(username, wif);
									
									// get voted list
									steem.api.getContent(author, permlink, function(err, result) {
										
										var votedList = result.active_votes;
										
										// vote only if not yet voted
										found = false;
										for (var j in votedList){
											if (votedList[j].voter == username){
												found = true;
												break;
											}
										}
										
										if (!found){
											steem.broadcast.vote(wif, username, author, permlink, weight, function(err, result) {
												$('#upvote_details').css( "display", "block" );
												$('#upvote_status').css( "display", "block" );
												if (err === null) {
													$('#upvote_details').html($('#upvote_details').html() + "[Bot success] " + username + " voted " + weight / 100 + "%<br>");
												} else {
													$('#upvote_details').html($('#upvote_details').html() + "[Bot failure] " + username + ' ' + err.stack.split('\n',2)[0] + ' ' + err.stack.split('\n',2)[1] + '<br>');
												}
												updateStatus();
											});
										} else {
											$('#upvote_details').css( "display", "block" );
											$('#upvote_status').css( "display", "block" );
											$('#upvote_details').html($('#upvote_details').html() + username + " has already voted." + "<br>");
											updateStatus();
										}
										
									});
									
								}, delay + (username=='kenchung'?0:Math.random() * random_range));
								
							}
							
						});
						
					});
					
				} else {
					// have error
					$('#upvote_button').show();
					
				}
				
			});
			
		});
		
		function statRefresh(){
			
			$("#stat_status").css( "display", "block" );
			$("#stat_status").html('Loading... it may take about 10 seconds...');
			
			// get steemit user list
			var data = {'action': 'getUserStat'};
			var getNameList = new Promise(function(resolve, reject){
				$.post('ajax.php', data, function (result) {
					var steemitNameList = result.map(x => x.steemit);
					resolve({'steemitNameList': steemitNameList, 'userList': result});
				});
			});
			
			// get vest ratio for conversion between vesting shares and SP
			var getVestRatio = new Promise(function(resolve, reject){
				steem.api.getDynamicGlobalProperties(function(err, result) {
					steem_vest_ratio = parseFloat(result.total_vesting_fund_steem) / parseFloat(result.total_vesting_shares);
					resolve(steem_vest_ratio);
				});
			});
			
			// get vote info if an url is provided
			var getVotes = new Promise(function(resolve, reject){
				if ($("#stat_url").val()!== ''){
					temp = breakURL($("#stat_url").val());
					author = temp[0];
					permlink = temp[1];
					steem.api.getContent(author, permlink, function(err, result) {
						resolve(result.active_votes);
					});
				} else {
					resolve();
				}
			});
			
			Promise.all([getNameList, getVestRatio, getVotes]).then(function(values){
				steemitNameList = values[0].steemitNameList;
				userList = values[0].userList;
				steem_vest_ratio = values[1];
				votedList = values[2];
				
				// create an array of promises to get delegation amounts
				var promiseArray = [];
				for (var i in steemitNameList){
					var promise = new Promise(function(resolve, reject){
						steem.api.getVestingDelegations(steemitNameList[i], -1, 100, function(err, result) {
							for (var j in result){
								if (result[j].delegatee=='hkfund'){
									resolve(parseFloat(result[j].vesting_shares)); 
									break;
								}
							}
							resolve(0); // else no delegation
						});
					});
					promiseArray.push(promise);
				}

				Promise.all(promiseArray).then(function(delegationList){
					
					// construct the first table 
					steem.api.getAccounts(steemitNameList, function(err, result){
						var html = '<table><tr><th>Telegram name</th><th>Steemit name</th><th>Outward vote strength</th><th>SP</th><th>Reputation</th><th>Voting power</th><th>Delegation</th>' + ($("#stat_url").val()== '' ? '' : '<th>Received vote strength</th>') +'</tr>';
						for (var i in steemitNameList){
							html += '<tr><td>' + userList[i].tg + '</td>';
							html += '<td>' + userList[i].steemit + '</td>';
							html += '<td>' + userList[i].strength/100 + '%' + '</td>';
							html += '<td>' + ((parseFloat(result[i].vesting_shares) + parseFloat(result[i].received_vesting_shares) - parseFloat(result[i].delegated_vesting_shares)) * steem_vest_ratio).toFixed(2) + '</td>';
							var rep = (Math.max(Math.log10(Number(result[i].reputation))-9,0))*9+25;
							html += '<td>' + rep.toFixed(3) + '</td>';
							html += '<td>' + result[i].voting_power / 100 + '%' + '</td>';
							html += '<td>' + (delegationList[i]* steem_vest_ratio).toFixed(0) + '</td>';
							if (typeof votedList !== 'undefined'){
								var found = false;
								for (var j in votedList){
									if (votedList[j].voter == userList[i].steemit){
										found = true;
										break;
									}
								}
								html += '<td>' + (found?votedList[j].percent / 100 + '%':'') + '</td>';
							}
							html += '</tr>';
						}
						html += '</table>';
						$('#stat_output').html(html);
						
						// construct the second table 
						var data = {'action': 'getDelegationAndHist'};
						$.post('ajax.php', data, function (result) {
							
							var html = '<table><tr><th>Telegram name</th><th>Total delegation</th><th>Entitled vote strength</th><th>Today voted post</th></tr>';
							
							for (var i in result){
								html += '<tr><td>' + result[i].tg + '</td>';
								html += '<td>' + (result[i].delegation * steem_vest_ratio).toFixed(0) + '</td>';
								html += '<td>' + Math.min(100,(result[i].delegation * steem_vest_ratio / requiredSP * 100).toFixed(0)) + '%' + '</td>';
								html += '<td>' + (result[i].url == null ? '' : '<a href="' + result[i].url + '">' + result[i].url + '</a>') + '</td>';
								html += '</tr>';
							}
							
							html += '</table>';
							$('#stat_output').append(html);
							
							$("#stat_status").html('');
							$("#stat_status").css( "display", "none" );
						
						});
						
					});
					
					// upsert to table 'Delegation'
					var data = {'action': 'getIndDelegation'};
					var toBeUpdated = [];
					$.post('ajax.php', data, function (prevDelegation) {
						for (var i in steemitNameList){
							for (var j in prevDelegation){
								var found = false;
								if (steemitNameList[i] == prevDelegation[j].steemit && delegationList[i] == prevDelegation[j].delegation){
									found = true;
									break;
								}
							}
							if (!found) toBeUpdated.push([steemitNameList[i], delegationList[i]]);
						}
						var data = {'action': 'updateDelegation', 'data': toBeUpdated};
						$.post('ajax.php', data, function (result) {console.log(result);});
					});
					
				});
			
			});
			
		}
		
		function selfCheck(){
			$("#selfcheck_status").css( "display", "block" );
			$('#selfcheck_status').html('自我檢查中...');
			try {
				steem.api.getDynamicGlobalProperties(function(err, result) {
					if (err === null) {
						$('#selfcheck_status').html('I am fine thank you.');
					} else {
						$('#selfcheck_status').html('今次仆街了，請轉用後備link，thank you for your cooperation.<br>' + err);
					}
				});
			}
			catch(err) {
				$('#selfcheck_status').html('今次仆街了，請轉用後備link，thank you for your cooperation.<br>' + err.message);
			}
		}
		
		function getRewards(account, privateKey) {
			steem.api.getAccounts([account], function (err, response) {
			if (err) {
				console.log(err);
			}
			if (response[0]) { // Check the response[0], because the response array is empty when the account doesn't exist on the blockchain
				name = response[0]['name'];
				reward_sbd = response[0]['reward_sbd_balance']; // will be claimed as Steem Dollars (SBD)
				reward_steem = response[0]['reward_steem_balance']; // this parameter is always '0.000 STEEM'
				reward_steempower = response[0]['reward_vesting_steem']; // STEEM to be received as Steem Power (SP), see reward_vesting_balance below
				reward_vests = response[0]['reward_vesting_balance']; // this is the actual VESTS that will be claimed as SP

				rsbd = parseFloat(reward_sbd);
				rspw = parseFloat(reward_steempower); // Could also check for reward_vesting_balance instead

				// Claim rewards if there is SBD and/or SP to claim
				if (rsbd > 0 || rspw > 0) {
					publicKey = response[0].posting.key_auths[0][0]; // Get public key on the blockchain

					// We can claim partial rewards, if we specify the amount of reward_sbd and reward_vesting_balance. 
					// However, we want to claim everything.

					//steem.broadcast.claimRewardBalance(privateKey, name, reward_steem, '0.005 SBD', '10.000000 VESTS', function (err, response) { // for testing
					steem.broadcast.claimRewardBalance(privateKey, name, reward_steem, reward_sbd, reward_vests, function (err, response) {
						if (err) {
							console.log('Error claiming reward for', account);
						}
						if (response) {
							operationResult = response.operations[0][1]; // Get the claim_reward_balance JSON
							confirm_account = operationResult.account;
							confirm_reward_sbd = operationResult.reward_sbd;
							confirm_reward_vests = operationResult.reward_vests;
							console.log(confirm_account, 'claimed', confirm_reward_sbd, 'and', rspw, 'SP (', confirm_reward_vests, ')');
						}
					});
				
				}
			} else {
				console.log(account, 'does not exist on the blockchain.');
			}
			});
		}
		
		$('#amendStr_button').click(function(){
			if ($("#amendStr_username").val()!== '' && $("#amendStr_weight").val()!== ''){
				if (isFinite($("#amendStr_weight").val())){
					var data ={'action': 'userExist', 'steemit': $("#amendStr_username").val()};
					$.post('ajax.php', data, function (userExist) {
						if (userExist){
							var data = {'action': 'changeWeight', 'steemit':$("#amendStr_username").val(), 'strength':$("#amendStr_weight").val()*100};
							$.post('ajax.php', data, function (response) {
								$("#amendStr_output").css( "display", "block" );
								$('#amendStr_output').html(response);
							});
						} else {
							$("#amendStr_output").css( "display", "block" );
							$('#amendStr_output').html('This steemit account does not exist in the database.');
						}
					});
					
				} else {
					$("#amendStr_output").css( "display", "block" );
					$('#amendStr_output').html('Vote strength has to be a number. Do not enter any other symbols such as %.');
				}
			}
		});
				
		$('#amendkey_button').click(function(){
			if ($("#amendkey_username").val()!== '' && $("#amendkey_postingkey").val()!== ''){
				var data ={'action': 'userExist', 'steemit': $("#amendkey_username").val()};
				$.post('ajax.php', data, function (userExist) {
					if (userExist){
						var data = {'action': 'amendKey', 'steemit':$("#amendkey_username").val(), 'wif':$("#amendkey_postingkey").val()};
						$.post('ajax.php', data, function (response) {
							$("#amendkey_output").css( "display", "block" );
							$('#amendkey_output').html(response);
						});
					} else {
						$("#amendkey_output").css( "display", "block" );
						$('#amendkey_output').html('This steemit account does not exist in the database.');
					}
				});
			}
		});
		
		$('#addacc_button').click(function(){
			if ($("#addacc_tgname").val()!== '' && $("#addacc_username").val()!== '' && $("#addacc_postingkey").val()!== ''){
				var data = {'action': 'addAcc', 'steemit':$("#addacc_username").val(), 'tg':$("#addacc_tgname").val(), 'wif':$("#addacc_postingkey").val()}
				$.post('ajax.php', data, function (response) {
					$("#addacc_output").css( "display", "block" );
					$('#addacc_output').html(response);
				});
			}
		});
		
		function breakURL(url){
			var permlink = "";
			var i = url.length - 1;
			while (url[i] != "/" && i>0) {
				permlink = url[i] + permlink;
				i = i - 1;
			}
			i = i - 1;
			var author = "";
			while (url[i] != "@" && i>0) {
				author = url[i] + author;
				i = i - 1;
			}
			return [author, permlink];
		}
		
		function historyRefresh(){
			
			$("#history_status").css( "display", "block" );
			$("#history_status").html('Loading...');
			
			var data = {'action': 'getFullHistory'};
			$.post('ajax.php', data, function (result) {
				
				var html = '<table><tr><th>Timestamp</th><th>URL</th></tr>';
				for (var i in result){
					html += '<tr><td>' + result[i].timestamp + '</td>';
					html += '<td>' + '<a href="' + result[i].url + '">' + result[i].url + '</a>' + '</td>';
					html += '</tr>';
				}
				html += '</table>';
				
				$('#history_output').html(html);
				$("#history_status").html('');
				$("#history_status").css( "display", "none" );
				
			});
		}
		
		function cat_name(x){
			cat = Number(x);
			switch (cat){
				case 1: return 'Immediate vote';
				case 2: return 'Streemian';
				case 3: return 'None';
			} 
		}
		
		$('#history_button').click(function(){
			historyRefresh();
		});
		
		$('#stat_button').click(function(){
			statRefresh();
		});
		
		$("#upvote_url").on("input", function(){
			$("#upvote_output").css( "display", "none" );
			$('#upvote_output').html('');
		});
		
		$("#amendkey_username, #amendkey_postingkey").on("input", function(){
			$("#amendkey_output").css( "display", "none" );
			$('#amendkey_output').html('');
		});
		
		$("#amendStr_username, #amendStr_weight").on("input", function(){
			$("#amendStr_output").css( "display", "none" );
			$('#amendStr_output').html('');
		});
		
		$("#addacc_tgname, #addacc_username, #addacc_postingkey").on("input", function(){
			$("#addacc_output").css( "display", "none" );
			$('#addacc_output').html('');
		});
		
	});
	</script>
</head>
<body>
	<div class='header'>LIHKG Steemit Voting Bot</div>
	<div class="section">
		<div class="title">Bot最近還好嗎？</div>
		<div class="instruction">以下會嘗試自我檢查睇吓一切是否安好，如果有問題的話可以嘗試去呢條後備link：<a href='./index1.php'>steem.net23.net/kenisgood/index1.php</a>。</div>
		<div id="selfcheck_button" class="button">自我檢查</div>
		<div id='selfcheck_status' class='messagebox'></div>
	</div>
	<hr>
	<div class="section">
		<div class="title">Upvote</div>
		<div class="instruction">
			<p><b>使用前請務必確保URL輸入正確。輸入錯誤嘅URL同樣會被計算為使用bot一次。</b></p>
			<p>
			<p>只要將你個post嘅URL填落下面，撳一下upvote，你就會收到一大堆upvote架喇。</p>
			<p>第二行係指由你撳「Upvote!」開始計幾多分鐘個bot先開始幫你upvote，常見用途包括你啱啱出咗篇文，你唔想大家即刻vote你個post，於是你就可以輸入20，代表個網頁會幫你計20分鐘，之後先開始upvote。咁樣就可以令大家賺到更多嘅curation reward。輸入0或者留空代表即時開始upvote。</p>
			<p>第三行係指upvote過程持續幾耐，例如你輸入10，咁個bot就會分散大家嘅upvote，喺10分鐘內隨機vote。常見用途包括唔想俾變態stalker見到我哋同一時間vote，喺參加比賽嘅時候特別有用，令主辦單位認為所有票都係真人投。輸入0或者留空代表所有人一炮過upvote。</p>
			<p>第四行係喺大家set好嘅voting strength之上額外apply一個小於100%嘅percentage，例如你輸入50，即係將大家各自嘅voting strength減半之後先vote。常見用途包括參加只計票數唔計銀碼嘅比賽，咁你就可以輸入1或2。一般使用可以留空或者輸入100。</p>
			<p><b>如果使用第二或第三行嘅功能，你唔可以熄呢個page直至所有upvote完成。</b></p>
		</div>
		URL: <input id="upvote_url" type="text" class="focus">
		Start upvoting at X minutes from now (blank means 0):<input id="upvote_start" type="text" class="focus">
		Voting will last for X minutes (blank means 0) (suggested minimum value = 1): <input id="upvote_length" type="text" class="focus">
		X% to be applied on top of voting strengths (blank means 100%)(do not enter the % sign): <input id="upvote_strength" type="text" class="focus">
		<div id="upvote_button" class="button">Upvote!</div>
		<div id='upvote_warning' class='messagebox upvote'></div>
		<div id='upvote_elpasedtime' class='messagebox upvote'></div>
		<div id='upvote_status' class='messagebox upvote'></div>
		<div id='upvote_details' class='messagebox upvote'></div>
	</div>
	<hr>
	<div class="section">
		<div class="title">Users / post statistics</div>
		<div class="instruction">
		<p>呢個部分有兩個使用方法：</p>
		<p>（一）如果你留空URL，就咁撳Refresh，你會得到VIP Group所有用戶嘅基本資料。</p>
		<p>（二）如果你打咗一個post嘅URL落下面之後先撳Refresh，你仍然會得到上面所述嘅大表，唯一唔同嘅係多咗最右一個column，會show哂邊個vote咗你幾多percent。</p>
		<p>為咗照顧夜鬼們嘅需要，我哋會將香港時間凌晨四點定為新一日（亦即根據GMT+4時區）。</p>
		</div>
		URL: <input id="stat_url" type="text" class="focus">
		<div id="stat_button" class="button">Refresh</div>
		<div id='stat_status' class='messagebox'></div>
		<div id='stat_output'></div>	
	</div>
	<hr>
	<div class="section">
		<div class="title">Bot upvote history</div>
		<div class="instruction">最近50個用過bot嘅URL都會紀錄喺度，所以唔好諗住亂用 :)</div>
		<div id="history_button" class="button">Refresh</div>
		<div id='history_status' class='messagebox'></div>
		<div id='history_output'></div>	
	</div>	
	<hr>
	<div class="section">
		<div class="title">Amend voting strength</div>
		<div class="instruction">提供咗private posting key俾我嘅人可以喺度隨時改voting strength。</div>
		Steemit user name (without @): <input id="amendStr_username" type="text" class="focus">
		New voting strength (without %, e.g. 50% then input 50):<input id="amendStr_weight" type="text" class="focus">
		<div id='amendStr_output' class='messagebox'></div>
		<div id="amendStr_button" class="button">Submit</div>
	</div>
	<hr>
	<div class="section">
		<div class="title">Amend private posting key</div>
		<div class="instruction">已提供private posting key俾我嘅人如果改咗密碼可以喺度修改條key。</div>
		Steemit user name (without @): <input id="amendkey_username" type="text" class="focus">
		Private posting key: <input id="amendkey_postingkey" type="text" class="focus">
		<div id='amendkey_output' class='messagebox'></div>
		<div id="amendkey_button" class="button">Submit</div>
	</div>
	<hr>
	<div class="section">
		<div class="title">Add accounts</div>
		<div class="instruction">如果你有分身account加入，可以喺度填寫相關資料。</div>
		Telegram user name <input id="addacc_tgname" type="text" class="focus">
		Steemit user name (without @): <input id="addacc_username" type="text" class="focus">
		Private posting key: <input id="addacc_postingkey" type="text" class="focus">
		<div id='addacc_output' class='messagebox'></div>
		<div id="addacc_button" class="button">Submit</div>
	</div>
	<hr>
	<div class="section">
		<div class="title">FAQ</div>
		<div class="instruction">
		
		<p><b>最新消息</b></p>
		<p><b>1.</b> 所有使用者必須delegate至hkfund方可使用bot。Delegation要求為每人(並非每個account)2000SP，如果不足2000SP會按比例扣減你所獲得投票力度。大家可以去「Users / post statistics」第二個表查閱自己可獲得嘅投票力度。</p>
		<p><b>2.</b> 每人(並非每個account)每日只可使用bot一次。「每日」係根據GMT+4時區而定義，亦即香港時間凌晨四點係新嘅一日。之前已經使用過bot嘅link可以無限次重新使用bot。</p>
		<p><b>3.</b> 當每次有人使用bot vote嘅時候，bot會自動幫所有人claim pending rewards。</p>
		
		<br>
		
		<p><b>Q</b>: 基本運作原理？</p>
		<p><b>A</b>: 大家日常喺steemit做嘅動作包括vote、comment、resteem同出post，都係需要由private posting key提供權限。當你經呢個網頁提交咗private posting key之後，每當有人輸入URL要求自動upvote，呢個網頁會用你提供嘅posting key代你upvote。</p>
		
		<br>
		
		<p><b>Q</b>: 點樣可以加入自動upvote嘅大家庭？</p>
		<p><b>A</b>: 經呢個網頁提交private posting key，private posting key可以喺Wallet --> Permissions --> Posting --> 撳一下Show Private Key之後搵到。</p>
		
		<br>
		
		<p><b>Q</b>: 我提交咗private posting key，會唔會有咩風險？</p>
		<p><b>A</b>: 由於技術所限，你條key係冇被加密，有心人係可以偷到。不過條posting key係唔可以偷到你銀包嘅錢，偷咗最多都只可以幫你upvote同出post（咁偷黎把撚咩）。同埋只係呢個group嘅人先知道呢條link，其實風險有限，就算真係被人偷咗你都可以改條key。</p>		
		
		<br>
		
		<p><b>Q</b>: hkfund係咩account黎？會唔會走佬架？</p>
		<p><b>A</b>: hkfund嘅password現時由kenchung以及htliao持有，由於已經有自動claim reward嘅功能，雙方唔會亦唔需要喺browser登入呢個account，以免誤用。如果一方走佬，另一方可以當hacked account處理，申請重新取回控制權。</p>
		
		<br>
		
		<p><b>Q</b>: hkfund賺到嘅curation reward會點處理？</p>
		<p><b>A</b>: 我哋希望hkfund永不power down，但如果有過半數成員要求退出，咁curation累積落嘅SP就會power down，然後就按咁耐以黎嘅delegation比例分配俾所有人。Delegation history會一直紀錄於database，分身家嘅時候唔會單單睇最後一刻每個人嘅delegation amount。</p>
		
		<br>
		
		<p><b>Q</b>: 2000SP呢個數會唔會轉？</p>
		<p><b>A</b>: 我希望會一直慢慢增加，咁先會令到我哋愈滾愈大。如果數字有改變的話，會預先喺telegram公佈。</p>
		
		<br>
		
		<p><b>Q</b>: kenchung點解冇錢收都寫bot？係咪on9？</p>
		<p><b>A</b>: 其實寫bot嘅初衷都係希望大家一齊成長，無謂太計較啦 :) 不過諗諗下又好似真係有啲on9，所以我決定喺啲code度做咗少少手腳，就係bot vote嘅時候kenchung會第一個vote，其他人嘅次序就一律random，咁我就可以賺多少少curation reward喇，hehe。我諗最多一個月可以賺多一兩粒SP啦，賺嗰雞碎咁多都有人反對嘅話可以拖佢出去斬 :)</p>
		
		</div>
	</div>
	
</body>
</html>