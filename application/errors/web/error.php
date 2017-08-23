<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Error <?php echo (isset($status) && $status) ? ":" . $status : NULL; ?></title>
<style type="text/css">

::selection { background-color: #E13300; color: white; }
::-moz-selection { background-color: #E13300; color: white; }
html { overflow: visible; }
body {
	background-color: #D3C1AF;
	margin: 1px auto;
	width: 100%;
	height: 100%;
	overflow: auto;
	font: 13px/20px normal Helvetica, Arial, sans-serif;
	color: #FFF;
}

#wrapper {
	margin: 5vh auto;
	width: 95vw;
	height: auto;
	color: #2C3E50;
	background-color: #FFFFFF;
	border: 2px solid #2C3E50;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	-ms-border-radius: 5px;
	-o-border-radius: 5px;
	border-radius: 5px;
}

h1 {
	color: #2C3E50;
	background-color: transparent;
	border-bottom: 2px solid #2C3E50;
	font-size: 19px;
	font-weight: normal;
	margin: 0;
	padding: 1%;
}

p { margin: 1%; }

a {
	color: #003399;
	background-color: transparent;
	font-weight: normal;
}

code {
	font-family: Consolas, Monaco, Courier New, Courier, monospace;
	font-size: 12px;
	background-color: #f9f9f9;
	border: 1px solid #D0D0D0;
	color: #002166;
	display: block;
	margin: 14px 0 14px 0;
	padding: 12px 10px 12px 10px;
}
</style>
</head>
<body>
	<div id="wrapper">
		<h1><?php echo (isset($header)) ? $header : "Error"; ?></h1>
		<?php
		if(isset($severity)) {
			?>
			<p><strong>Severity: </strong><?php echo $severity; ?></p>
			<?php
		}
		
		if(isset($file)) {
			?>
			<p><strong>In File: </strong><?php echo $file; ?></p>
			<?php
		}
		
		if(isset($line)) {
			?>
			<p><strong>At Line: </strong><?php echo $line; ?></p>
			<?php
		}
		
		if(isset($message)) {
			?>
			<p><strong>Message: </strong><?php echo $message; ?></p>
			<?php
		}
		?>
	</div>
</body>
</html>





















