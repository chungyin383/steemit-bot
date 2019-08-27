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
	<script type="text/javascript" src="https://cdn.steemjs.com/lib/latest/steem.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
	
		const requiredSP = 2000;
		
		$('#upvote_button').click(function(){
			
			$('#upvote_button').hide();
			$('.upvote.messagebox').html('');
			$('.upvote.messagebox').css( "display", "none" );
			
			var temp = breakURL($("#upvote_url").val());
			var author = temp[0];
			var permlink = temp[1];
			var delay = Number($("#upvote_start").val()) * 60 * 1000;
			var random_range = Math.max(0.5, Number($("#upvote_length").val())) * 60 * 1000;
			var pad =  $("#upvote_strength").val()=="" ? 1 : Number($("#upvote_strength").val()) / 100;
			
				
			$('#upvote_warning').html("Upvote in progress..."); 
			$('#upvote_warning').css( "display", "block" );
			
			// record usage 
			var data = {'action': 'recordUsage', 'steemit': author, 'permLink': permlink};
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
					upvote(wifVoterList[i].wif, wifVoterList[i].steemit, +(Number(wifVoterList[i].strength) * pad).toFixed(0));
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
						
					}, delay + (username=='kenchung'?0:Math.random() * random_range));
					
				}
				
			});
			
		});
		
		$('#addtg_button').click(function(){
			if ($("#addtg_tg").val()!== '' && $("#addtg_steemit").val()!== ''){
				var data = {'action': 'addtg', 'tg':$("#addtg_tg").val(), 'steemit':$("#addtg_steemit").val()};
				$.post('ajax.php', data, function (response) {
					$("#addtg_output").css( "display", "block" );
					$('#addtg_output').html(response);
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
		
		$("#upvote_url").on("input", function(){
			$("#upvote_output").css( "display", "none" );
			$('#upvote_output').html('');
		});
		
		$("#addtg_tg, #addtg_steemit").on("input", function(){
			$("#addtg_output").css( "display", "none" );
			$('#addtg_output').html('');
		});
		
	});
	</script>
</head>
<body>
	<div class='header'>Admin page</div>
	
	<div class="section">
		<div class="title">Force upvote</div>
		URL: <input id="upvote_url" type="text" class="focus">
		Start upvoting at X minutes from now (blank means 0):<input id="upvote_start" type="text" class="focus">
		Voting will last for X minutes (blank means 0) (suggested minimum value = 1): <input id="upvote_length" type="text" class="focus">
		X% to be applied on top of voting strengths (blank means 100%)(do not enter the % sign) <b>REMEMBER TO CHECK DELEGATION</b>: <input id="upvote_strength" type="text" class="focus">
		<div id="upvote_button" class="button">Upvote!</div>
		<div id='upvote_warning' class='messagebox upvote'></div>
		<div id='upvote_elpasedtime' class='messagebox upvote'></div>
		<div id='upvote_status' class='messagebox upvote'></div>
		<div id='upvote_details' class='messagebox upvote'></div>
	</div>
	<hr>
	
	<div class="section">
		<div class="title">Add new tg user</div>
		Telegram name: <input id="addtg_tg" type="text" class="focus">
		Steemit user name (without @): <input id="addtg_steemit" type="text" class="focus">
		<div id='addtg_output' class='messagebox'></div>
		<div id="addtg_button" class="button">Submit</div>
	</div>
</body>
</html>