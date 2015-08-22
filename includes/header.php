<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>OpenRepeater<?php if ($pageTitle) { echo " | ".$pageTitle; } ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- The styles -->
	<link id="bs-css" href="../theme/css/bootstrap-classic.css" rel="stylesheet">
	<style type="text/css">
	  body {
		padding-bottom: 40px;
	  }
	  .sidebar-nav {
		padding: 9px 0;
	  }
	</style>

	<link href='../theme/css/bootstrap-responsive.css' rel='stylesheet'>
	<link href='../theme/css/charisma-app.css' rel='stylesheet'>
	<link href='../theme/css/jquery-ui-1.8.21.custom.css' rel='stylesheet'>
	<link href='../theme/css/fullcalendar.css' rel='stylesheet'>
	<link href='../theme/css/fullcalendar.print.css' rel='stylesheet'  media='print'>
	<link href='../theme/css/chosen.css' rel='stylesheet'>
	<link href='../theme/css/uniform.default.css' rel='stylesheet'>
	<link href='../theme/css/colorbox.css' rel='stylesheet'>
	<link href='../theme/css/jquery.cleditor.css' rel='stylesheet'>
	<link href='../theme/css/jquery.noty.css' rel='stylesheet'>
	<link href='../theme/css/noty_theme_default.css' rel='stylesheet'>
	<link href='../theme/css/elfinder.min.css' rel='stylesheet'>
	<link href='../theme/css/elfinder.theme.css' rel='stylesheet'>
	<link href='../theme/css/jquery.iphone.toggle.css' rel='stylesheet'>
	<link href='../theme/css/opa-icons.css' rel='stylesheet'>
	<link href='../theme/css/uploadify.css' rel='stylesheet'>
	
	<?php 
	// Display custom CSS if defined by page
	if ($customCSS) {
		echo "<!-- custom CSS for current page -->\n";
		$customCSS = preg_replace('/\s+/', '', $customCSS);
		$cssArray = explode(',',$customCSS);
		foreach ($cssArray as $cssfile) {
		  echo "\t<link href='../theme/css/".$cssfile."' rel='stylesheet'>\n";
		}
	}
	?>


	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	  <script src="http://html5shim.googlecode.com/svn/trunk/html5"></script>
	<![endif]-->

	<!-- The fav icon -->
	<link rel="shortcut icon" href="../theme/img/favicon.ico">

	<?php if ($header_scripts) { echo $header_scripts; } ?>

</head>

<body<?php if ($body_onload) { echo ' ' . $body_onload; } ?>>
	<?php if(!isset($no_visible_elements) || !$no_visible_elements)	{ ?>

	<!-- Server Notifications -->

<?php
	/* CHECK FOR MEMCACHE FLAG AND NOTIFY USER IF SERVER NEEDS RESTARTED */
	$current_page_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$memcache_obj = new Memcache;
	$memcache_obj->connect('localhost', 11211);
	$var = $memcache_obj->get('update_settings_flag');

	if ($var == 1) {
		echo '
		<div class="server_bar" style="">
			<span>SVXLink Configs Rebuild & Restart: </span>

			<!-- Button triggered modal -->
			<button class="btn" data-toggle="modal" data-target="#restartServer"><i class="icon-refresh"></i> Rebuild Configs & Restart SVXLink</button>
		</div>

	<!-- Modal - RESTART SERVER -->

	<div class="modal fade" id="restartServer" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
		<h3 class="modal-title" id="myModalLabel">Rebuild & Restart Repeater</h3>
	      </div>
	      <div class="modal-body">
			<h4>Are You Sure You Want to Do This?</h4>
			<p>This will generate new configuration files based on the setting you have updated here. After the files have been created the repeater software will have to be restarted. You should check to make sure that the repeater is currently at idle and that there are no active conversations taking place. Any active Echolink connections will be disconnected. Do you still wish to proceed?</p>
	      </div>
	      <div class="modal-footer">
			<form action="functions/svxlink_update.php" method="post" style="margin:0;">
				<input type="hidden" name="return_url" value="'.$current_page_url.'">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-success"><i class="icon-refresh icon-white"></i> Restart</button>
			</form>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div>
	<!-- /.modal -->

		';
	}
?>

	<!-- topbar starts -->
	<div class="navbar">
		<div class="navbar-inner">
			<div class="container-fluid">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".top-nav.nav-collapse,.sidebar-nav.nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>

				<div class="header-logo">
					<a href="../dashboard.php"><img src="../theme/img/OpenRepeaterLogo-Header.png"></a>
					<span>BETA</span>
				</div>

				<!-- user dropdown starts -->
				<div class="btn-group pull-right" >
					<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
						<i class="icon-user"></i><span class="hidden-phone"> <?php echo $_SESSION['username'];?></span>
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<li><a href="../login.php?action=setPassword">Change Password</a></li>
						<li class="divider"></li>
						<li><a href="../login.php?action=logout">Logout</a></li>
					</ul>
				</div>
				<!-- user dropdown ends -->
			</div>
		</div>
	</div>
	<!-- topbar ends -->
	<?php } ?>
	<div class="container-fluid">
		<div class="row-fluid">
		<?php if(!isset($no_visible_elements) || !$no_visible_elements) { ?>

			<!-- left menu starts -->
			<div class="span2 main-menu-span">
				<div class="well nav-collapse sidebar-nav">
					<ul class="nav nav-tabs nav-stacked main-menu">
						<li class="nav-header hidden-tablet">Main</li>
						<li><a class="ajax-link" href="dashboard.php"><i class="icon-home"></i><span class="hidden-tablet"> Dashboard</span></a></li>
						<li><a class="ajax-link" href="settings.php"><i class="icon-wrench"></i><span class="hidden-tablet"> Repeater</span></a></li>
						<li><a class="ajax-link" href="identification.php"><i class="icon-bullhorn"></i><span class="hidden-tablet"> Identification</span></a></li>
						<li><a class="ajax-link" href="courtesy_tone.php"><i class="icon-music"></i><span class="hidden-tablet"> Courtesy Tones</span></a></li>
						<li><a class="ajax-link" href="echolink.php"><i class="icon-signal"></i><span class="hidden-tablet"> EchoLink</span></a></li>
						<li><a class="ajax-link" href="ports.php"><i class="icon-cog"></i><span class="hidden-tablet"> Tx/Rx Ports</span></a></li>
						<li><a class="ajax-link" href="log.php"><i class="icon-list-alt"></i><span class="hidden-tablet"> Repeater Log</span></a></li>
					</ul>
				</div><!--/.well -->
			</div><!--/span-->
			<!-- left menu ends -->

			<noscript>
				<div class="alert alert-block span10">
					<h4 class="alert-heading">Warning!</h4>
					<p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript" target="_blank">JavaScript</a> enabled to use this site.</p>
				</div>
			</noscript>

			<div id="content" class="span10">
			<!-- content starts -->
			<?php } ?>
