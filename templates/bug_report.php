<form method='post' action='?page=linkit_reportbug'>
	<div class="box">
		<span class="caption">Report Bug</span>

		<div class="inner">
			<table style="margin: 0px auto;">
				<tr>
					<td><label for="message">Message:</label></td>
					<td><textarea style="width: 500px;height: 300px;" id="message" type='text' name='LinkITMsg' onclick="if(this.value=='Tell us what went wrong.')this.value='';">Tell us what went wrong.</textarea></td>
				</tr>

				<?php echo $error_message; ?>
				<tr>
					<td></td>
					<td>
						<input type='submit' class="button btn-fix" value="Send">
					</td>
				</tr>
			</table>
		</div>
	</div>
</form>
