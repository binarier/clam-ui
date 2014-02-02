<?php
$network_ini='/opt/clam/etc/network.ini';
$resolv_file='/opt/clam/etc/resolv.conf';
switch ($_REQUEST['command'])
{
	case "restart":
		exec('/usr/bin/systemctl restart miner');
		echo json_encode(['STATUS' => ['STATUS' => 'S']]);
		break;
	case "network_load":
		$ini = parse_ini_file($network_ini);
		$addr = split('/', $ini['address']);
		unset($ini['address']);
		$ini['ipaddress'] = $addr[0];
		$mask = 0;
		for ($i=0;$i<32;$i=$i+1)
		{
			$mask = $mask << 1;
			if ($i < $addr[1])
				$mask = $mask | 1;
		}
		$ini['netmask'] = long2ip($mask);
		
		echo json_encode($ini);
		break;
	case "network_save":
	
		$mask = ip2long($_REQUEST['netmask']);
		$n = 32;
		while ($n > 0 && !($mask & 1))
		{
			$mask >>= 1;
			$n--;
		}
		$fh = fopen($network_ini, 'w');
		fwrite($fh, "#ip address\n");
		fwrite($fh, "type=" . $_REQUEST['type'] . "\n");
		fwrite($fh, "address=" . $_REQUEST['ipaddress'] . "/" . $n . "\n");
		fwrite($fh, "gateway=" . $_REQUEST['gateway'] . "\n");
		fwrite($fh, "dns1=" . $_REQUEST['dns1'] . "\n");
		fwrite($fh, "dns2=" . $_REQUEST['dns2'] . "\n");
		fclose($fh);
		
		$dns1 = $_REQUEST['dns1'];
		$dns2 = $_REQUEST['dns2'];
		$fh = fopen($resolv_file, 'w');
		if (!empty($dns1))
			fwrite($fh, "nameserver " . $dns1 . "\n");
		if (!empty($dns2))
			fwrite($fh, "nameserver " . $dns2 . "\n");
		if (empty($dns1) && empty($dns2))
			fwrite($fh, "nameserver 8.8.8.8");
		fclose($fh);

		if ($_REQUEST['type'] == 'dhcp')
		{
			exec('/usr/bin/systemctl disable network.service > /dev/null 2>&1');
			exec('/usr/bin/systemctl enable dhcpcd@eth0.service > /dev/null 2>&1');
		}
		else
		{
			exec('/usr/bin/systemctl enable network.service > /dev/null 2>&1');
			exec('/usr/bin/systemctl disable dhcpcd@eth0.service > /dev/null 2>&1');
		}

		exec('/usr/bin/sync');
		exec('/usr/bin/sudo /usr/bin/reboot > /dev/null 2>&1 &');

		break;
	case "pass":
		exec("/usr/bin/htpasswd -b /opt/clam/etc/uipassword clam " . $_REQUEST['pass']);

		break;
	case "cgminer":
		header('Content-type: text/html; charset=utf-8');
		$f = $_FILES["cgminer_bin"];
		if ($f["error"] > 0)
		{
		  echo "上传出错: " . $f["error"];
		}
		else
		{
			$target_file = "/opt/clam/bin/cgminer";
			echo "cgminer 上传 OK<br>";
		  echo "文件大小: " . $f["size"] . " 字节<br>";
		  echo "MD5: " . md5_file($f['tmp_name']) . "<br>";
		  echo "关闭服务...<br>";
			system('/usr/bin/systemctl stop miner');
			echo "更新cgminer...<br>";
			move_uploaded_file($f['tmp_name'], $target_file);
			chmod($target_file, 0755);
			echo "重新启动服务...<br>";
			system('/usr/bin/systemctl start miner');
			echo "完成";
		}
		break;  	
}
?>
