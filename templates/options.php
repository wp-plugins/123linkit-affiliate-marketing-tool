<div id="options" class="box">
	<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/wrench.png"/>Options</span>
	<div class="inner">

		<form method="post" action="?page=linkit_options">
			<table class="options">
				<tr>
					<td class="description">
						<ul>
							<li class="list-header">Links</li>
							<li><input id="cloak" type="checkbox" name="options_cloaked" <?php echo $cloaked; ?>>&nbsp;<label for="cloak">Automatically cloak all links</label>&nbsp;</li>
							<li><input id="nofollow" type="checkbox" name="options_nofollow" <?php echo $nofollow; ?>>&nbsp;<label for="nofollow">Add nofollow tags to all links</label>&nbsp;</li>
							<li><input id="newwindow" type="checkbox" name="options_new_window" <?php echo $newwindow; ?>>&nbsp;<label for="newwindow">Open links in new window</label>&nbsp;</li>
							<li class="list-header">Blog Category</li>
							<li>
							<label for="blogcategory">Select your blog's category:</label>
							<?php echo $this->select('blogcategory', $categories, $category); ?>
							</li>
						</ul>
					</td>
					<td class="description">
						<ul>
							<li class="list-header">Need help?</li>
							<li><a href="http://www.123linkit.com/general/faq">See our FAQ page</a></li>
							<li><a href="http://getsatisfaction.com/123linkit">Search our support forum</a></li>
							<li><a href="http://www.123linkit.com/general/contact_us">Contact us</a></li>
							<li>If you're having problems with the current settings, you can roll back any changes and settings you made by <a href="?page=linkit_plugin&step=reset_all">resetting the plugin</a>.</li>
							<li class="list-header">Support 123LinkIt</li>
							<li>Give it a good rating on <a href="http://wordpress.org/extend/plugins/bb-login.php?re=http://wordpress.org/extend/plugins/123linkit-affiliate-marketing-tool/">WordPress.org</a></li>
							<li>&nbsp;</li>
							<li>&nbsp;</li>
						</ul>
					</td>
				</tr>
				<tr><td class="description">
				<input id="linkitsavesettings" class="button-primary btn-fix-options" type="submit" value="Save Changes">
			</form>

			<form method="post" action="?page=linkit_plugin&step=restore_defaults" style="display: block; float: left;">
				<input class="button btn-fix-options" type="submit" value="Restore Defaults">
			</form>

			</td></tr>
		</table>
	</div>
</div>
<div class="box">
	<span class="caption">Payment Information</span>
	<div class="inner">To <a href="http://www.123Linkit.com/">ensure payments</a>, log into your 123Linkit account and complete your profile.</div>
</div>
