<?php
$conf_file = "/opt/clam/etc/miner.conf";
$settings = json_decode(file_get_contents($conf_file), true);
switch ($_REQUEST['command'])
{
	case "loadpools":
		echo json_encode($settings['pools']);
		break;
	case "addpool":
		$settings['pools'][]=['url' => $_REQUEST['url'], 'user' => $_REQUEST['user'], 'pass' => $_REQUEST['pass']];
		file_put_contents($conf_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		echo json_encode(['STATUS' => ['STATUS' => 'S']]);
		break;
	case "removepool":
		$pool = intval($_REQUEST['pool']);
		$new_pool = [];
		foreach ($settings['pools'] as $k => $v)
		{
			if ($k != $pool)
				$new_pool[] = $v;
		}
		$settings['pools'] = $new_pool;
		file_put_contents($conf_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		echo json_encode(['STATUS' => ['STATUS' => 'S']]);
			
		break;
}
?>
