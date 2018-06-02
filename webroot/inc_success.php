<?php if (!defined('FORUM')) die('go away'); ?>

<div style="font-size:80%; position:fixed; top:5px; right:5px; z-index:23;"><a href="<?php echo $cfg_url_logout; ?>">Logout</a></div>

<div class="container">

    <div class="jumbotron">
        <a href="https://wiki.braveineve.com" target="_blank"><img src="img/brave.png" class="pull-right" width="265"></a>
        <h1>Success!</h1>
	<p>
	    Welcome to the Brave Forum Management,<br>
	    your available characters are listed below.<br>
	</p>
	<p style="font-size:95%;">
	    <b>Please checkout the <i>FAQ</i> section...</b><br>
	    <br>
	    <b>Forum: <a href="http://forums.bravecollective.com" target="_blank">http://forums.bravecollective.com</a></b>
	</p>
    </div>

    <div class="row">

	<div class="col-md-6">

	    <div class="panel panel-default">
		<div class="panel-heading">
		    <h3 class="panel-title">Primary Character</h3>
		</div>
		<div class="panel-body" id="list-primary">
		</div>
	    </div>

	    <div class="panel panel-default">
		<div class="panel-heading">
		    <h3 class="panel-title">Alt Characters</h3>
		</div>
		<div class="panel-body" id="list-alts">
		</div>
	    </div>
	</div>

	<div class="col-md-6">
	    <div class="panel panel-default">
		<div class="panel-heading">
		    <h3 class="panel-title">FAQ</h3>
		</div>
		<div class="panel-body">

<h6>How does this work?</h6>
By loading this page your primary character defined in Core has been created as a forum user.<br>
Click the <i>Reset Password</i> function to generate a new random password, then use the credentials listed on the left in order to <a href="http://forums.bravecollective.com/ucp.php?mode=login" target="_blank">login</a>. Be aware that you can <a href="http://forums.bravecollective.com/ucp.php?i=ucp_profile&mode=reg_details" target="_blank">change</a> your password in the forum itself or use this page to generate a new random password anytime.<br>

<h6>First Steps?</h6>
Once you successfilly logged into the forum, you should setup your <a href="http://forums.bravecollective.com/ucp.php?i=ucp_profile&mode=profile_info" target="_blank">profile</a>.<br>
Adding your email and jabber address to your profile is <u>recommended</u> as they can be used for <a href="http://forums.bravecollective.com/ucp.php?i=ucp_notifications&mode=notification_options">notifications</a> in case you receive a PM or are quoted in a reply.<br>

<h6>What about my Alt Characters?</h6>
The user under which you are known on the forum is your <i>Primary Character</i> as shown on the upper left.<br>
You can easily associate your <i>Alt Characters</i> to your primary account by clicking the buttons on the left. For each associated character, your primary character inherits the permissions from that character (e.g. access to corp forums in case they exist).
The names of associated characters are <u>publicly</u> visible in your user profile by any registered user.

		</div>
	    </div>
	</div>

    </div>

</div>

<script id="form-primary" type="text/x-handlebars-template">

<div class="form-horizontal"  onsubmit="return false">
    <legend><img src="https://image.eveonline.com/Character/{{character_id}}_128.jpg" width=24> {{character_name}}</legend>
    <div class="row">
	<div class="col-xs-3">
	    <b>Username</b><br>
	    <b>Password</b><br>
	</div>
	<div class="col-xs-9">
	    {{character_name}}<br>
	    <span id="reset-pass">&lt;Reset Password&gt;</span>
	    <button type="submit" id="reset-button" class="pull-right btn btn-xs btn-primary" onclick="resetPrimary()">
		<span id="reset-spin" class="glyphicon glyphicon-refresh hide"></span> Reset Password
	    </button>
	</div>
    </div>
</div>

</script>

<script id="form-alts" type="text/x-handlebars-template">

    <div class="row">
	<div class="col-md-4">
	<img src="https://image.eveonline.com/Character/{{character_id}}_128.jpg" width=24> {{character_name}} 
	</div>
	<div class="col-md-5">
	     <b><span class="pull-righta" id="toggle-{{character_id}}-state">{{state}}</span></b>
	</div>
	<div class="col-md-3">
	    <button type="submit" id="toggle-{{character_id}}-button" class="btn btn-xs btn-primary" onclick="toggleAlt('{{character_id}}')">
		<span id="toggle-{{character_id}}-spin" class="glyphicon glyphicon-refresh hide"></span> <span id="toggle-{{character_id}}-label">Toggle Association</span>
	    </button>
	</div>
    </div>

<br>

</script>

<script src="js/inc_success.js"></script>
