<?php

// Set username, password and IP address of gateway
define('USERNAME', 'cusadmin');
define('PASSWORD', 'password');
define('IPADDRESS','10.1.10.1');
define('STATUSFILE','./SMCscrape.out");

// Shouldn't have to change anything below
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36');
define('COOKIE_FILE', 'cookie.txt');
define('LOGIN_FORM_URL', "http://$IPADDRESS/login.asp");
define('LOGIN_ACTION_URL', "http://$IPADDRESS/goform/login");

$postValues = array(
    'user' => USERNAME,
    'pws' => PASSWORD
);

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, LOGIN_ACTION_URL);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postValues));
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_REFERER, LOGIN_FORM_URL);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_exec($curl);

if(curl_errno($curl)){
    throw new Exception(curl_error($curl));
}

curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

function FeatGatewayModem() {
  global $fp, $curl;

  $vars = array('CmDownstreamFrequencyBase','CmDownstreamDSLockStatusBase','CmDownstreamQamBase','CmDownstreamChannelPowerdBmVBase','CmDownstreamSnrBase');

  $vars2 = array('CmUpstreamFrequencyBase','CmUpstreamLockStatusBase','CmUpstreamModuBase','CmUpstreamChannelPowerBase','CmUpstreamChannelIdBase');

  curl_setopt($curl, CURLOPT_URL, "http://$IPADDRESS/user/feat-gateway-modem.asp");
  $output = curl_exec($curl);

  $output = str_replace("|\";","\";",$output);

  preg_match("/var cable_status=(.+)/",$output, $tmp);
  $cstatus = $tmp[1];
  $cstatus &= 0xffff;
  
  fwrite($fp,"InitHardware|");
  if ($cstatus <= 2) fwrite($fp,"process\n");
  if ($cstatus >= 3) fwrite($fp,"complete\n");

  fwrite($fp,"AcquireDownChan|");
  if ($cstatus == 3) fwrite($fp,"process\n");
  if ($cstatus >= 4) fwrite($fp,"complete\n");

  fwrite($fp,"UpRanging|");
  if ($cstatus == 4 || $cstatus == 5) fwrite($fp,"process\n");
  if ($cstatus >= 6) fwrite($fp,"complete\n");

  fwrite($fp,"DhcpBound|");
  if ($cstatus == 6) fwrite($fp,"process\n");
  if ($cstatus >= 7 && $cstatus != 15) fwrite($fp,"complete\n");

  fwrite($fp,"SetTod|");
  preg_match("/var TodSuccess = (.+);/",$output, $tmp);
  if ($cstatus == 7) fwrite($fp,"process\n");
  if ($cstatus >=8 && $cstatus <= 12) {
        if ($tmp[1] == 1) { fwrite($fp,"complete\n");
        } else { fwrite($fp,"ToD Server Not Found\n"); }
  }

  fwrite($fp,"DownloadCMConfigurationFile|");
  if ($cstatus == 8 || $cstatus == 9) fwrite($fp,"process\n");
  if ($cstatus >= 10 && $cstatus <= 12) fwrite($fp,"complete\n");

  fwrite($fp,"Registration|");
  if ($cstatus == 10) fwrite($fp,"process\n");
  if ($cstatus >= 11 && $cstatus <= 12) fwrite($fp,"complete\n");

 fwrite($fp,"OnlineStatus|");
  if ($cstatus == 12) fwrite($fp,"traffic enabled\n");
  if ($cstatus == 13) fwrite($fp,"refused by cmts\n");

  fwrite($fp,"CableStatus|$tmp[1]\n");
  preg_match("/var TodSuccess = (.+);/",$output, $tmp);
  fwrite($fp,"TodSuccess|$tmp[1]\n");

  for ($i = 0; $i < count($vars); ++$i) {
        preg_match("/.+$vars[$i].+\"\s*(.+)\|\s*(.+)\|\s*(.+)\|\s*(.+)\";/", $output, $tmp);
        fwrite($fp,"$vars[$i]-a|$tmp[1]\n$vars[$i]-b|$tmp[2]\n$vars[$i]-c|$tmp[3]\n$vars[$i]-d|$tmp[4]\n");
  }

  for ($i = 0; $i < count($vars2); ++$i) {
        preg_match("/.+$vars2[$i].+\"\s*(.+)\|\s*(.+)\|\s*(.+)\|\s*(.+)\";/", $output, $tmp);
        fwrite($fp,"$vars2[$i]-a|$tmp[2]\n$vars2[$i]-b|$tmp[3]\n$vars2[$i]-c|$tmp[4]\n");
  }

}

function FeatGatewayStatus() {
  global $fp, $curl;

  curl_setopt($curl, CURLOPT_URL, "http://$IPADDRESS/user/feat-gateway-status.asp");
  $output = curl_exec($curl);

  preg_match("/Vendor Name.+\n<td>(.+)<\/td>/", $output, $tmp);
  fwrite($fp, "VendorName|$tmp[1]\n");

  preg_match("/Hardware Version.+\n<td>(.+)<\/td>/", $output, $tmp);
  fwrite($fp, "HardwareVersion|$tmp[1]\n");
  
  preg_match("/Serial Number.+\n<td>(.+)<\/td>/", $output, $tmp);
  fwrite($fp, "SerialNumber|$tmp[1]\n");

  preg_match("/Firmware Version.+\n<td>(.+)<\/td>/", $output, $tmp);
  fwrite($fp, "FirmwareVersion|$tmp[1]\n");

  preg_match("/RGStatus.=.\"(.+)\"/", $output, $tmp);
  if ($tmp[1] != '0') {$tmp1 = "RG";} else {$tmp1 = "Bridge-Only";};
  fwrite($fp, "OperatingMode|$tmp1\n");

  preg_match("/System Uptime<\/td>\n<td>0*(\d+)\s+days\s+(.+)<\/td>/", $output, $tmp);
  fwrite($fp, "SystemUptime|$tmp[1]d:$tmp[2]\n");

  preg_match("/var systimebase = \"(\w+)\s+(\w+)\s+(\d+)\s+(.+)\s+(\d+)\";/", $output, $tmp);
  fwrite($fp, "SystemDate|$tmp[1] $tmp[2] $tmp[3] $tmp[5]\n");
  fwrite($fp, "SystemTime|$tmp[4]\n");
}

function FeatGatewayNetwork() {
  global $fp, $curl;

  curl_setopt($curl, CURLOPT_URL, "http://$IPADDRESS/user/feat-gateway-network.asp");
  $output = curl_exec($curl);
}

$fp = fopen($STATUSFILE, 'w');

FeatGatewayStatus();
// FeatGatewayNetwork();
FeatGatewayModem();

fclose($fp);
