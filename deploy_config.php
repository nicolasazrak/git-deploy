<?php

set_time_limit(600);

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
