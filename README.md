git-deploy
==========

It's a simple way to upload files from git to php shared hosting without access to git or shell, using github post hooks.
Main difference between other similar proyects is that this script makes a backup of the previous folder insted of updating modified files.


Instalation
===========

- First, upload deploy.php and desploy_config.php to your server.
- Then, go to you repo settings -> WebHook & Services -> Add a webHook -> Use the url where deploy.php is



Config
======

<pre>
<code>
$config = array(
	"repo" => array(
			"user" => "github_user",
			"pass" => "github_password",
			"branch" => "master",
			"url" => "https://github.com/twbs/bootstrap/archive/"
		),
	"path" => array(
			"old" => "old/",
			"source" => "dist/",
			"destination" => "bootstrap/",
			"temp" => "temp_deploy/"
		),
	"emails" => array()
);
</code>
</pre>



Limitations
===========

- Can only deploy to a specific folder
- All paths in config must end with /






