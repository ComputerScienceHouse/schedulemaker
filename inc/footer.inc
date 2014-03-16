			</div>
			<div id="footer">
				<div class="csh"><a target="_blank" href="http://www.csh.rit.edu/"><img src="<?=$HTTPROOTADDRESS?>img/csh.png" alt="CSH" /></a></div>
				<a href='status.php'>Status</a> | <a target="_blank" href="https://github.com/ComputerScienceHouse/schedulemaker/issues">Report Issues</a>
				<div>
					Idea: John Resig (phytar at csh.rit.edu)<br>
					Development v2: Ben Russell (benrr101 at csh.rit.edu),<br>
					Development v3: Ben Grawi (bgrawi at csh.rit.edu)<br>
					Hosting: <a href="http://www.csh.rit.edu/">Computer Science House</a><br>
				</div>
			</div>
		</div>
		<!-- LOAD SCRIPTS LAST -->
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/mousetrap/1.4.6/mousetrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.0.3/js/bootstrap.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.14/angular.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.14/angular-animate.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.14/angular-sanitize.min.js"></script>
		<script src="<?=$HTTPROOTADDRESS?>js/app.js?v=<?=$APP_VERSION?>"></script>
		<script>
		  // GOOGLE ANALYTICS CODE
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		  ga('create', '<?= $GOOGLEANALYTICS ?>', 'rit.edu');
		  ga('send', 'pageview');
	
		  //IE 10 MOBILE FIX
	      if (navigator.userAgent.match(/IEMobile\/10\.0/)) {
		  var msViewportStyle = document.createElement("style")
		  msViewportStyle.appendChild(
		    document.createTextNode(
		      "@-ms-viewport{width:auto!important}"
		    )
		  )
		  document.getElementsByTagName("head")[0].appendChild(msViewportStyle)
		}
	    </script>
	</body>
</html>
