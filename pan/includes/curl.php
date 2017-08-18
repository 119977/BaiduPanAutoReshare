<?php
$cookie_jar = array();

function set_cookie($cookie) {
  global $cookie_jar;
  $cookie = explode(';', trim($cookie, "; \t\n\r\0\x0B"));
  foreach ($cookie as $splited) {
    if (!strpos($splited, '=')) {
      continue;
    }
    $splited = explode('=', $splited, 2);
    $cookie_jar[trim($splited[0])] = trim($splited[1]);
  }
}

function get_cookie() {
  global $cookie_jar;
  $ret = array();
  foreach ($cookie_jar as $k => $v) {
    $ret[] = $k.'='.$v;
  }
  return implode('; ', $ret);
}

$curl = false;

function init_curl() {
  global $curl;
  if (!$curl) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $GLOBALS['ref_scurl'] = &$curl;
    function SF() { curl_close($GLOBALS['ref_scurl']); }
    register_shutdown_function('SF');
  }
}

function get_curl($url) {
  global $curl, $ua;
  if (!$curl) {
    init_curl();
  }
  curl_setopt($curl, CURLOPT_POSTFIELDS, null);
  curl_setopt($curl, CURLOPT_POST, false);
  curl_setopt($curl, CURLOPT_USERAGENT, $ua);
  curl_setopt($curl, CURLOPT_URL, $url);
  return $curl;
}

$redirect_cnt = 0;

function request($url, $postData=NULL, $followLocation = true) {
	$curl = get_curl($url);
	if ($postData !== NULL) {
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
	}
  curl_setopt($curl, CURLOPT_COOKIE, get_cookie());
	$response = curl_exec($curl);
	$ret = array('header' => array());
	$head_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$headerRaw = explode("\r\n", substr($response, 0, $head_size));
	array_shift($headerRaw);
  foreach($headerRaw as $line) {
		$exp = explode(': ', $line, 2);
    if (!isset($exp[1])) {
      continue;
    }
    if (strtolower($exp[0]) == 'set-cookie') {
      set_cookie($exp[1]);
    }
    $ret['header'][strtolower($exp[0])] = $exp[1];
	}
	$body = substr($response, $head_size);
	$ret['body'] = $body;
	$ret['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$ret['error'] = curl_error($curl);
  $ret['real_url'] = $url;
  if (isset($ret['header']['location']) && $followLocation) {
    global $redirect_cnt;
    $redirect_cnt += 1;
    if ($redirect_cnt > 10) {
      echo 'Max redirect count excceed!<br />Last url: ' . $url . '<br />Request result: <br />';
      print_r($ret);
      die();
    }
    $redirect_cnt = 0;
    $location = $ret['header']['location'];
    if (substr($location, 0, 4) == 'http') {
      return request($location);
    } else if ($location[0] === '/') {
      return request(substr($url, 0, strpos($url, '/', 8) + 1) . $location);
    } else {
      return request(substr($url, 0, strrpos($url, '/') + 1) . $location);
    }
  }
	return $ret;
}



function request2($url, $ua,$cookie,$postData=NULL, $followLocation = true) {
	$curl = get_curl($url);
	if ($postData !== NULL) {
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $ua);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
	}
  curl_setopt($curl, CURLOPT_COOKIE, get_cookie());
	$response = curl_exec($curl);
	$ret = array('header' => array());
	$head_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$headerRaw = explode("\r\n", substr($response, 0, $head_size));
	array_shift($headerRaw);
  foreach($headerRaw as $line) {
		$exp = explode(': ', $line, 2);
    if (!isset($exp[1])) {
      continue;
    }
    if (strtolower($exp[0]) == 'set-cookie') {
      set_cookie($exp[1]);
    }
    $ret['header'][strtolower($exp[0])] = $exp[1];
	}
	$body = substr($response, $head_size);
	$ret['body'] = $body;
	$ret['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$ret['error'] = curl_error($curl);
  $ret['real_url'] = $url;
  if (isset($ret['header']['location']) && $followLocation) {
    global $redirect_cnt;
    $redirect_cnt += 1;
    if ($redirect_cnt > 10) {
      echo 'Max redirect count excceed!<br />Last url: ' . $url . '<br />Request result: <br />';
      print_r($ret);
      die();
    }
    $redirect_cnt = 0;
    $location = $ret['header']['location'];
    if (substr($location, 0, 4) == 'http') {
      return request($location);
    } else if ($location[0] === '/') {
      return request(substr($url, 0, strpos($url, '/', 8) + 1) . $location);
    } else {
      return request(substr($url, 0, strrpos($url, '/') + 1) . $location);
    }
  }
	return $ret;
}