<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php $GlobalPage->pageTitle(); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
		<link rel="icon" href="/favicon.ico" type="image/ico" />
		<link rel="stylesheet" href="/res/styles/normalizer.css" />
		<link rel="stylesheet" href="/res/styles/foundation.css" />
		<link rel="stylesheet" href="/res/styles/main.css" />
		<script src="/res/scripts/foundation/vendor/modernizr.js"></script>
	</head>
	<body>
		<nav class="top-bar" data-topbar>
			<ul class="title-area">
				<li class="name">
					<h1>
						<a href="/">
							<?php echo SITE_NAME; ?>
						</a>
					</h1>
				</li>
				<li class="toggle-topbar menu-icon"><a href="#"><span>menu</span></a></li>
			</ul>

			<section class="top-bar-section">
				<ul class="right">
					<li class="divider"></li>
					<li>
						<a href="/">Home</a>
					</li>
				</ul>
			</section>
		</nav>
		<div id="body">