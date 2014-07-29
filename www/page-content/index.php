<?php 
/*
 * The GlobalPage variable is available to any template file. It references the RequestHandler object in index.php.
 * You can use includeFile($filename), to include files from the /page-include/ folder. 
 * This is useful for including things like headers and footers. 
 */
$GlobalPage->includeFile('header.php'); ?>
		<div id="container">
			<header>
				<div id="logo">
					<h1>Template</h1>
				</div>
				<nav>
					<ul>
						<li><a href="/">Home</a></li>
						<li><a href="#">Some link</a></li>
					</ul>
				</nav>
			</header>
			<div id="body">
				<?php $Page->createBody(); ?>
			</div>
		</div>
<?php $GlobalPage->includeFile('footer.php'); ?> 
