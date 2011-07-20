<div class="box">
	<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/lightning.png"/>Get Started Using 123LinkIt</span>

	<form method="post" action="?page=linkit_plugin&step=sync_all">
		<div class="inner" style="height: 40px;">
			<div style="width:355px; float: left;">Press the "Synchronize Posts" button to get started<br> and we"ll add affiliate links to all your posts instantly.</div>
			<input id="linkitsavesettings" class="button-primary btn-fix" style="margin-left: 85px; margin-top: 5px;" type="submit" value="Synchronize Posts">
		</div>
	</form>
</div>

<div class="box">
	<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/user.png"/>My Profile</span>
	<div class="inner">

		<table class="options">
			<tr>
				<th colspan="2">Posts &amp; Links</td>
			</tr>
			<tr>
				<td>Number of Posts Analyzed</td>
				<td><?php echo $nposts; ?></td>
			</tr>
			<tr>
				<td>Total Links Added</td>
				<td><?php echo $nlinks; ?></td>
			</tr>
			<tr>
				<td>Average number of links per post</td>
				<td><?php echo $avglinks; ?></td>
			</tr>
			<tr>
				<th colspan="2">Your Referrals</td>
			</tr>
			<tr>
				<td>Number of Referrals</td>
				<td><?php echo $nreferrals; ?></td>
			</tr>
			<tr>
				<td>Total amount of commissions from referrals</td>
				<td><?php echo $totalreferrals; ?></td>
			</tr>
		</table>

		<form method="post" action="?page=linkit_plugin&step=sync_profile">
			<input class="button-primary" style="" type="submit" value="Update">
		</form>
	</div>
</div>

<div class="box">
	<span class="caption">My Account</span>
	<div class="inner">View your <a href="http://www.123Linkit.com">full reports and commissions</a> by logging into your 123LinkIt.com account.</div>
</div>
