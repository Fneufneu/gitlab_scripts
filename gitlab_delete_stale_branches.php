<?php

$project_id = 9;
$timelimit = strtotime("-18 month");
$gitlab_token = "XXXXXXXXXXXXXXXXXX";
$gitlab_host = "gitlab.myhost.fr";

$json = gitlab_list_branch();
$datas = json_decode($json, true);
if (!is_array($datas))
	die('json_decode failed'.PHP_EOL);

echo count($datas) . " branchs found" . PHP_EOL;

$branch_deleted = false;
foreach ($datas as $branch) {
	if ($branch['merged']) {
		gitlab_delete_branch($branch['name']);
		continue;
	}
	$ts = strtotime($branch['commit']['committed_date']);
	if (is_int($ts) and $ts < $timelimit) {
		gitlab_delete_branch($branch['name']);
	}
}
if (!$branch_deleted)
	echo "nothing to delete" . PHP_EOL;

function gitlab_list_branch() {
	global $gitlab_host, $gitlab_token, $project_id;

	$url = "https://$gitlab_host/api/v4/projects/$project_id/repository/branches?per_page=100";
	$opts = [
		'http' => [
			'method'=> "GET",
			'header'=> ["PRIVATE-TOKEN: $gitlab_token"],
		]
	];
	$context = stream_context_create($opts);
	$json = file_get_contents($url, false, $context);
	if ($json === false)
		die('file_get_contents failed'.PHP_EOL);
	
	return $json;
}

function gitlab_delete_branch($name) {
	global $gitlab_host, $gitlab_token, $project_id, $branch_deleted;

	$branch_deleted = true;
	$url = "https://$gitlab_host/api/v4/projects/$project_id/repository/branches/".urlencode($name);
	$opts = [
		'http' => [
			'method'=> "DELETE",
			'header'=> ["PRIVATE-TOKEN: $gitlab_token"],
		]
	];
	$context = stream_context_create($opts);
	echo "Deleting $name" . PHP_EOL;
	$ret = @file_get_contents($url, false, $context);
	if (false === $ret)
		echo "==>> failed" . PHP_EOL;
}
