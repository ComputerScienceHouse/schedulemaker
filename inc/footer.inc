	</div>
	<div id='footer' style='margin-bottom:52px'>
		<div class='logo'><a href='index.php'>ScheduleMaker</a></div>
		<div class='csh'><a href="http://www.csh.rit.edu/"><img src="./img/csh.png" alt="CSH" /></a></div>
		<div class='nav'><a href='generate.php'>Build Schedules</a> | <a href='browse.php'>Browse Courses</a> | <a href='roulette.php'>Course Roulette</a> | <a href='status.php'>Status</a></div>
		<div>
			Idea: John Resig (phytar at csh.rit.edu)<br />
			Development: Ben Russell (benrr101 at csh.rit.edu)<br />
			Hosting: <a href="http://www.csh.rit.edu/">Computer Science House</a><br />
			Uses <a href="http://somerandomdude.com/work/iconic/">iconic icon set by P.J. Onori</a>, 
			licensed under <a href="http://creativecommons.org/licenses/by-sa/3.0/us/">CC BY-SA 3.0</a>
		</div>
	</div>
	</div>
	<? if(!isset($_COOKIE['hide_banner'])) { ?>
	<!--<div id='bollocks' style='position:fixed; bottom:0px; right:0px; height:52px; width:800px; background-color:green'>
		BOLLOCKS
		<div id='hide' style='position:absolute; right:0px; top:-20px'>
			<a href='#' onClick='return hideCookie(this);'>[ hide ]</a>
		</div>
	</div>-->
	<? } ?>
	<script type='text/javascript'>
		document.title = "<?= isset($TITLE) ? $TITLE : "ScheduleMaker" ?>";
		
		function setCookie(c_name,value,exdays)	{
			var exdate=new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
			document.cookie=c_name + "=" + c_value;
		}

		function hideCookie(obj) {
			setCookie("hide_banner", "1", 365);
			$(obj).parent().parent().remove();
			return false;
		}

		// Add the 
	</script>
	</body>
</html>
