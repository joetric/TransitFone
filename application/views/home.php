<!--
//Copyright 2011 Joseph R. Tricarico.<br />
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Transitfone SMS</title>
<style>

/*FONTS*/
a{
text-decoration:none;
border-bottom:dotted 1px #ccc;
color:auto;
}
.header,
h1, h2{
font-weight:bold;
font-family:Arial, Helvetica, sans-serif
}
p{
font-family:Georgia, "Times New Roman", Times, serif;
font-size:16px;
}
p.smaller{
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:10px;
	line-height:140%;
	margin: 0;
}
.sms{
font-size:13px;
font-family:Verdana, Arial, Helvetica, sans-serif;
color:#333;
font-weight:bold;
background-color:#eee;
}

/*FONT SIZE*/
h1{
font-size:28px;
}
h2{
font-size:20px;
}


/*COLORS*/
h1, p, p a{
color: #666;
}
h2{
color: #999;
}
.accent-color{
color:#09c;
font-weight:bold;
}

/*POSITIONING*/
html, body{
padding:0;
margin:0;
height:100%;
}
h1{
margin:0;
}
.footer{
	text-align:center;
	position: relative;
	bottom: 0px;
}
.indent30{
margin-left:30px;
}
.header{
background-color:#09c;
height:50px;
color:white;
width:100%;
box-shadow: 0 1px 3px black;
-moz-box-shadow: 0 1px 3px black;
-webkit-box-shadow: 0 1px 3px black;
}
.content{
max-width:950px;
margin:0 auto;
padding:0 5px;
}
.container .content{
	margin-top:40px;
}
.header .content{
position:relative;
top:2px;
}
.container{
width:100%;
}
.wrapper {
min-height: 100%;
height: auto !important;
height: 100%;
margin: 0 auto -4em;
}
.footer, .push {
height: 4em;
}
</style>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-27073758-1']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body>
<div class="wrapper">
	<div class="header">
		<div class="content"><a href="/"><img src="/img/transitfone_h46_bg0cf.gif" alt="transitfone" width="216" height="46" border="0" /></a></div>
	</div>
	<div class="container">
		
		<div class="content">
			<img src="/img/transitfone_ss_galaxy_cropped.jpg" style="float:right;margin-left:50px;"/>
			<h1>SEPTA bus detour alerts via text</h1>
			<p><span class="accent-color">Subscribe</span> by texting the letter <strong>s</strong> and the <strong>bus route number(s)</strong> to <span class="accent-color">215-310-5550</span>. Put a space after the letter 's' and between the route numbers, like this:</p>
			<span class="sms indent30">s 23 H XH </span>
			<p>To <span class="accent-color">unsubscribe</span> from one or more routes, text the letter <strong>u</strong> and the <strong>bus route number(s)</strong>. For example, to unsubscribe from alerts for Route 23, type:</p>
			<p><span class="sms indent30">u 23 </span></p>
			<p>To <span class="accent-color">unsubscribe from all alerts</span>, text:</p>
			<span class="sms indent30">u all</span>
		</div>
	</div>
	<div class="push"></div>
</div>
<div class="footer">
<p class="smaller"><br />
		&copy;2011 <a href="http://www.azavea.com/about-us/staff-profiles/joe-tricarico">Joseph Tricarico</a>. Derived from <a href="http://codeigniter.com/">CodeIgniter</a> and contributions by <a href="http://twitter.com/benedmunds">Ben Edmunds</a>.<br />
		Data provided by the <a href="http://septa.org/">Southeastern Pennsylvania Transportation Authority</a>. <br />
	  For help or comments, e-mail <strong>info@transitfone.com</strong>. Click <a href="http://stats.pingdom.com/yfyiivab1310/433995">here for uptime stats</a>.</p>
</div>
</body>
</html>