<div id="login" style="text-align: center;">
	<form method='post' action='?page=linkit_plugin&step=createuser'>
		<div class="box" style="width: 500px; min-width: 400px; margin: 0px auto;">
			<span class="caption">Sign Up</span>

			<div class="inner">
				<table style="margin: 0px auto;">
					<tr>
						<td style="width: 120px;"><label for="email">Email</label></td>
						<td><input style="width: 200px;" id="email" type='text' name='LinkITEmail'></td>
					</tr>
					<tr>
						<td><label for="password">Password</label></td>
						<td><input style="width: 200px;" id="password" type='password' name='LinkITPassword'></td>
					</tr>
					<tr>
						<td><label for="passwordc">Password confirmation</label></td>
						<td><input style="width: 200px;" id="passwordc" type='password' name='LinkITPasswordc'></td>
					</tr>
					<tr>
						<td><label for="blogcategory">Blog category</label></td>
						<td><?php echo $this->select('blogcategory', $categories); ?></td>
					</tr>
					<tr>
						<td colspan="2"><label><input type="checkbox" name="agree" value="agreed" />
								By checking this box, I confirm I have read and agree to the 123LinkIt 
								<a href="http://www.123linkit.com/terms-and-conditions" target="_blank">Terms of Service</a>
								and <a href="http://www.123linkit.com/privacy-policy" target="_blank">Privacy Policy</a>.</td>
					</tr>
					<?php echo $error_message; ?>
					<tr>
						<td></td>
						<td>
							<input type='submit' class="button btn-fix" value="Sign Up">
						</td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>
