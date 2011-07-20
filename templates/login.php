<div id="login" style="text-align: center;">
	<form method="post" action="">
		<div class="box" style="width: 500px; min-width: 400px; margin: 0px auto;">
			<span class="caption">Login</span>

			<div class="inner">
				<table style="margin: 0px auto;">
					<tr>
						<td style="width: 80px;"><label for="email">Email</label></td>
						<td><input style="width: 200px;" id="email" type='text' name='email'></td>
					</tr>
					<tr>
						<td><label for="pass">Password</label></td>
						<td><input style="width: 200px;" id="pass" type='password' name='pass'></td>
					</tr>
					<tr>
						<td></td>
						<td style="text-align: right;">
							<a style="font-size: 10px;" href='http://www.123linkit.com/password_resets/new'>Forgot your password?</a>
						</td>
					</tr>
					<?php echo $error_message; ?>
					<tr>
						<td></td>
						<td>
							<input type='submit' class="button btn-fix" value='Login'>
							<a class="button btn-fix" href='?page=linkit_plugin&step=signup'>Sign up here</a>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>
