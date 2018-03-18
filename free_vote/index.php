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
	<script type="text/javascript" src="./steem.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){

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
			$('#upvote_warning').html("Do not close this tab, otherwise voting will stop. Please wait..."); 
			$('#upvote_warning').css( "display", "block" );
			
			var temp = breakURL($("#upvote_url").val());
			var author = temp[0];
			var permlink = temp[1];
			var delay = Number($("#upvote_start").val()) * 60 * 1000;
			var random_range = Number($("#upvote_length").val()) * 60 * 1000;
			var pad = Math.min(1, $("#upvote_strength").val()=="" ? 1 : Number($("#upvote_strength").val()) / 100);
			
			if (author == 'kawaiiiiiiii030') {
				
				// blacklist	
				var data = {'action': 'recordUsage', 'url': '[BLACKLISTED] ' + $("#upvote_url").val()};
				$.post('ajax.php', data, function (response) {
					$("#upvote_warning").html("You have been blacklisted. Please contact admin if you have any questions.");
				});
			
			} else {
				
				// record usage
				var data = {'action': 'recordUsage', 'url':$("#upvote_url").val()};
				$.post('ajax.php', data, function (response) {});
				
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
						upvote(wifVoterList[i].wif, wifVoterList[i].username, Number(wifVoterList[i].weight) * pad);
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
							
						}, delay + Math.random() * random_range);
						
					}
					
				});
				
			}

		});
		
		function statRefresh(){
			
			$("#stat_status").css( "display", "block" );
			$("#stat_status").html('Loading...');
			
			var html = '<table><tr><th>Telegram name</th><th>Steemit name</th><th>Category</th><th>Bot vote strength</th><th>SP</th><th>Reputation</th><th>Voting power</th>' + ($("#stat_url").val()== '' ? '' : '<th>Voting strength</th>') +'</tr>';
			var steem_vest_ratio, userList, steemitNameList = [];
			
			var data = {'action': 'getUserStat'};
			var req1 = $.post('ajax.php', data, function (result) {
				userList = result;
				for (var i in userList){
					steemitNameList.push(userList[i].steemit);
				}
			});
			
			var req2 = steem.api.getDynamicGlobalProperties(function(err, result) {
				steem_vest_ratio = parseFloat(result.total_vesting_fund_steem) / parseFloat(result.total_vesting_shares);
			});
			
			var req3 = $.Deferred();
			
			if ($("#stat_url").val()!== ''){
				temp = breakURL($("#stat_url").val());
				author = temp[0];
				permlink = temp[1];
				steem.api.getContent(author, permlink, function(err, result) {
					req3.resolve(result.active_votes);
				});
			} else {
				req3.resolve();
			}
			
			$.when(req1, req2, req3).done(function(x1, x2, votedList){
				steem.api.getAccounts(steemitNameList, function(err, result){
					for (var i in steemitNameList){
						html += '<tr><td>' + userList[i].tg + '</td>';
						html += '<td>' + userList[i].steemit + '</td>';
						html += '<td>' + cat_name(userList[i].category) + '</td>';
						html += '<td>' + (userList[i].weight==null?'':userList[i].weight/100 + '%') + '</td>';
						html += '<td>' + ((parseFloat(result[i].vesting_shares) + parseFloat(result[i].received_vesting_shares) - parseFloat(result[i].delegated_vesting_shares)) * steem_vest_ratio).toFixed(2) + '</td>';
						html += '<td>' + ((Math.log10(Number(result[i].reputation))-9)*9+25).toFixed(3) + '</td>';
						html += '<td>' + result[i].voting_power / 100 + '%</td>';
						if (typeof votedList !== 'undefined'){
							found = false;
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
					$("#stat_status").html('');
					$("#stat_status").css( "display", "none" );
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
		
		$('#addkey_button').click(function(){
			if ($("#addkey_username").val()!== '' && $("#addkey_postingkey").val()!== ''){
				var data = {'action': 'addKey', 'username':$("#addkey_username").val(), 'wif':$("#addkey_postingkey").val()}
				$.post('ajax.php', data, function (response) {
					$("#addkey_output").css( "display", "block" );
					$('#addkey_output').html(response);
				});
			}
		});
		
		$('#amendStr_button').click(function(){
			if ($("#amendStr_username").val()!== '' && $("#amendStr_weight").val()!== ''){
				var data = {'action': 'changeWeight', 'username':$("#amendStr_username").val(), 'weight':$("#amendStr_weight").val()*100};
				$.post('ajax.php', data, function (response) {
					$("#amendStr_output").css( "display", "block" );
					$('#amendStr_output').html(response);
				});
			}
		});
		
		$('#addacc_button').click(function(){
			if ($("#addacc_tgname").val()!== '' && $("#addacc_username").val()!== ''){
				var data = {'action': 'addAcc', 'username':$("#addacc_username").val(), 'tgname':$("#addacc_tgname").val()}
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
			var data = {'action': 'getHistory'};
			$.post('ajax.php', data, function (response) {
				$('#history_output').html(response);
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
		
		$("#addkey_username, #addkey_postingkey").on("input", function(){
			$("#addkey_output").css( "display", "none" );
			$('#addkey_output').html('');
		});
		
		$("#amendStr_username, #amendStr_weight").on("input", function(){
			$("#amendStr_output").css( "display", "none" );
			$('#amendStr_output').html('');
		});
		
		$("#addacc_tgname, #addacc_username").on("input", function(){
			$("#addacc_output").css( "display", "none" );
			$('#addacc_output').html('');
		});
		
	});
	</script>
</head>
<body>
	<div class='header'>LIHKG Steemit Voting Bot<p>每人每日限用一次哦 :)</div>
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
			<p>唔使註冊任何嘢，只要將你個post嘅URL填落下面，撳一下upvote，你就會收到一大堆upvote架啦。</p>
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
		<p>（一）如果你留空URL，就咁撳Refresh，你會得到VIP Group所有用戶嘅基本資料。Category係指你有冇登記做auto-voter，同埋你係邊一類voter。（詳情可見FAQ，簡單黎講就係：Immediate vote係已經提供咗private posting key俾我嘅人，佢哋會最先upvote；Streemian係join咗trail嘅人，會係第二批人vote；None就係唔會自動vote你嘅人。）Bot vote strength係指你set咗自動vote人vote得幾大力，只有屬於Immediate vote嘅人先可以show到出黎。</p>
		<p>（二）<b>[極邪惡function] </b>如果你打咗一個post嘅URL落下面之後先撳Refresh，你仍然會得到上面所述嘅大表，唯一唔同嘅係多咗最右一個column，會show哂邊個vote咗你幾多percent。正常黎講Immediate vote同Streemian嘅人你係唔使擔心，佢哋會自動upvote。你可以根據呢個表盡情chur未vote你嘅人幫你upvote :)</p>
		<p>如果你有啲分身或者有新人加入，可以喺「Add accounts」度加你個user name落去。同埋如果你join咗Streemian亦請通知我，等我手動改你個category。</p></div>
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
		<div class="title">Add / amend private posting keys</div>
		<div class="instruction">未提供private posting key俾我嘅人可以喺度提交條key，你嘅category會自動改為Immediate vote。已提供private posting key俾我嘅人如果改咗密碼可以喺度修改條key。</div>
		Steemit user name (without @): <input id="addkey_username" type="text" class="focus">
		Private posting key: <input id="addkey_postingkey" type="text" class="focus">
		<div id='addkey_output' class='messagebox'></div>
		<div id="addkey_button" class="button">Submit</div>
	</div>
	<hr>
	<div class="section">
		<div class="title">Add accounts</div>
		<div class="instruction">如果有新人加入，又或者你有分身想加入個bot，可以喺呢度輸入資料，如果唔係你嘅資料就show唔到喺「Users / post statistics」呢度啦。</div>
		Telegram user name <input id="addacc_tgname" type="text" class="focus">
		Steemit user name (without @): <input id="addacc_username" type="text" class="focus">
		<div id='addacc_output' class='messagebox'></div>
		<div id="addacc_button" class="button">Submit</div>
	</div>
	<hr>
	<div class="section">
		<div class="title">FAQ</div>
		<div class="instruction">
		<p><b>Q</b>: 運作原理？</p>
		<p><b>A</b>: 大家日常喺steemit做嘅動作包括vote、comment、resteem同出post，都係需要由private posting key提供權限。當你經呢個網頁提交咗private posting key之後，每當有人輸入URL要求自動upvote，呢個網頁會用posting key代佢哋upvote。當中helloworld123係我嘅分身，佢喺streemian成立咗一條trail，有follow到條trail嘅人亦會跟住helloworld123一齊upvote。</p>
		
		<br>
		
		<p><b>Q</b>: 點樣可以加入自動upvote嘅大家庭？</p>
		<p><b>A</b>: 有兩個方法：</p>
		<p>（一）經呢個網頁提交private posting key，private posting key可以喺Wallet --> Permissions --> Posting --> 撳一下Show Private Key之後搵到。</p>
		<p>（二）去streemian.com開個account，follow佢嘅instruction。佢approve左你個account之後去<a href="https://streemian.com/profile/curationtrail/trailing/558">呢度</a>follow呢條trail。</p>
		
		<br>
		
		<p><b>Q</b>: 咁兩個方法邊個好啲？</p>
		<p><b>A</b>: <b>第一個方法嘅利與弊：</b></p>
		<p>- 方便快捷，接近100% work，可以隨時喺度改voting strength。</p>
		<p>- 第一批人upvote，可以賺多啲curation reward。</p>
		<p>- 但係由於技術所限，你條key係冇被加密，有心人係可以偷到。不過條posting key係唔可以偷到你銀包嘅錢，偷咗最多都只可以幫你upvote同出post。同埋只係呢個group嘅人先知道呢條link，其實風險有限，就算真係被人偷咗你都可以改條key。</p>
		<p><b>第二個方法嘅利與弊：</b></p>
		<p>- 麻煩，多bug，唔一定work。</p>
		<p>- 第二批人upvote，會少多啲curation reward。</p>
		<p>- 佢會攞你<b>active key</b>（我唔知點解），呢條key係可以偷到你銀包嘅錢，當然佢係open-source嘅，理論上唔會有任何惡意行為嘅。</p>
		<p>- 你條key應該係用一啲安全嘅方法放咗喺佢哋度，所以呢方面風險細啲。</p>
		<p><b>Anyway任何方法都有利與弊，請喺加入前自行判斷有關風險。</b></p>
		
		<br>
		
		<p><b>Q</b>: 用minnowsupport先定呢個bot先？</p>
		<p><b>A</b>: 呢個先。因為有啲人join咗minnowsupport（例如我），如果你用minnowsupport先，你會先得到我0.1% upvote，然後再run呢個bot，雖然個command係叫我upvote 100%，但係不能override之前嘅vote，所以都係得0.1%。</p>
		
		<br>
		
		<p><b>Q</b>: 我之前有join steemain trail，但點解category都係none嘅？</p>
		<p><b>A</b>: 我係base on之前啲post肉眼睇有冇auto upvote，可能有睇錯，但由於streemian成日有問題，你set咗都未必代表你真係vote到。如果我錯咗，記得話我知，我會改返佢。</p>
		
		<br>
		
		<p><b>Q</b>: 係咪要加入自動upvote之後先可以用呢個bot？</p>
		<p><b>A</b>: 咁又冇明文規定嘅，但係最好就攞人著數嘅同時都回報社群啦 :) </p>
		
		</div>
	</div>
	
</body>
</html>