<div id="challenge" class="box">
	<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/trophy.png"/>The 123LinkIt Challenge</span>
	<div class="inner">
		Lacking ideas for your blog post? We challenge you to write one using the following random keywords:
		<ul>
		<?php foreach($keywords as $link => $value): ?>
			<?php if(is_numeric($link)): ?>
				<li><?php echo $value; ?></li>
			<?php else: ?>
				<li><a href="<?php echo $link; ?>" title="Generate a random post about this topic."><?php echo $value; ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?> 
		</ul>
		<hr class="clear" />
	</div>
</div>
