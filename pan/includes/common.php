<?php
define('APP_ROOT', dirname(__FILE__));
if (file_exists(APP_ROOT.'/../config.php')) require_once dirname(__FILE__).'/../config.php';
else {
	header('Location: install.php');
	exit;
}
require_once APP_ROOT.'/curl.php' ;
require_once APP_ROOT.'/db.php';

function wlog($message, $level = 0) {
  global $database;
  if(getenv("HTTP_X_FORWARDED_FOR"))
    $ip = getenv("HTTP_X_FORWARDED_FOR");
  elseif(getenv("REMOTE_ADDR"))
    $ip = getenv("REMOTE_ADDR");
  $database->insert('log_new', array('IP' => $ip, 'level' => $level, 'content' => $message));
}

function findBetween($str, $begin, $end) {
  if (false === ($pos1 = strpos ($str, $begin))
    ||  false === ($pos2 = strpos($str, $end, $pos1 + strlen($begin)))
  ) return false;
  return substr($str, $pos1 + strlen($begin), $pos2 - $pos1 - strlen($begin));
}

function print_header($title) {
  header('Content-Type: text/html; charset=utf-8');
  ?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=1" />
<title><?php echo $title; ?></title>
<link href="https://cdn.bootcss.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container">
<?php
}

function showMessage() {
	if (isset($_SESSION['msg'])) {
		foreach ($_SESSION['msg'] as $v) {
			switch ($v[1]) {
				case 'success':
					echo '<div class="alert alert-success">';
					break;
				case 'info':
					echo '<div class="alert alert-info">';
					break;
				case 'warning':
					echo '<div class="alert alert-warning">';
					break;
				case 'danger':
					echo '<div class="alert alert-danger">';
					break;
			}
			echo $v[0], '</div>';
		}
		$_SESSION['msg'] = array();
	}
}

function addMessage($content, $type='info') {
	if (!isset($_SESSION['msg'])) $_SESSION['msg'] = array();
	$_SESSION['msg'][] = array($content, $type);
}

//部分代码参考自 https://github.com/ly0/baidupcsapi
$bdstoken = false;
$bduss = false;
$uid = false;
$username = false;
$md5 = false;

function loginRequired($ref = 'index.php') {
  global $database;
  session_start();
  $logedin = FALSE;
  if (isset($_SESSION['siteuser_id']) and $_SESSION['siteuser_id']) {
    $logedin = TRUE;
  } elseif (isset($_COOKIE['siteuser_hash']) and isset($_COOKIE['siteuser_id'])) {
		$siteuser = $database->get('siteusers', '*', array('ID' => intval($_COOKIE['siteuser_id'])));
    if (!empty($siteuser) and $siteuser['hash'] === $_COOKIE['siteuser_hash']) {
      $_SESSION['siteuser_id'] = intval($_COOKIE['siteuser_id']);
      $logedin = TRUE;
    }
  }
  if (!$logedin) {
    header('Location: user.php?action=login&ref='.urlencode($ref));
    exit;
  }
}

function validateCookieAndGetBdstoken() {
  $token = request('http://pan.baidu.com/disk/home');
  $bdstoken = findBetween($token['body'], '"bdstoken":"', '",');
  if (strlen($bdstoken) < 10) {
    return false;
  }
  return $bdstoken;
}

function loginFromDatabase($_uid, $siteu_id = 0) {
  global $database;
	if ($siteu_id) $user = $database->get('users', '*', array('AND' => array('ID' => $_uid, 'siteu_id' => $siteu_id)));
	else $user = $database->get('users', '*', array('ID' => $_uid));
  if (!$user) {
    return -1;
  }
  set_cookie($user['cookie']);
  global $cookie_jar, $bduss;
  if (!isset($cookie_jar['BDUSS'])) {
    return false;
  }
  $bduss = $cookie_jar['BDUSS'];
  //原本想把bdstoken存进数据库，想到需要检验cookie是否合法，还是改成动态获取
  global $bdstoken;
  $bdstoken = validateCookieAndGetBdstoken();
  if (!$bdstoken) {
    $bduss = false;
    return false;
  }
  global $uid, $username, $md5;
  $uid = $_uid;
  $username = $user['username'];
  $md5 = ($user['newmd5'] === '') ? false : $user['newmd5'];
  return true;
}

function GUID()
{
  if (function_exists('com_create_guid') === true)
  {
    return trim(com_create_guid(), '{}');
  }

  return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function getToken($guid) {
  global $bdstoken;
  if (!$bdstoken) {
    $_res = request('https://passport.baidu.com/v2/api/?getapi&tpl=netdisk&subpro=netdisk_web&apiver=v3&class=login&tt='.time().'&logintype=basicLogin&gid='.$guid);
    $res = json_decode(str_replace("'", '"', $_res['body']), true);
    if (!isset($res['data']['token'])) {
      echo '无法获取bdstoken！请联系开发者！服务器返回：'.$_res['body'];
      die();
    }
    $bdstoken = $res['data']['token'];
  }
}

function loginCheck($username) {
  $_res = request('https://passport.baidu.com/v2/api/?getapi&tpl=netdisk&subpro=netdisk_web&apiver=v3&sub_source=leadsetpwd&tt='.time().'&username='.$username.'&isphone=false');
  $res = json_decode(str_replace("'", '"', $_res['body']), true);
  if (isset($res['data']) && isset($res['data']['codeString'])) {
    return $res['data']['codeString'];
  }
  return false;
}

function login($username, $password, $codestring='', $captcha='') {
  global $bdstoken;
  request('http://pan.baidu.com/');
  if ($codestring == '') {
    $captcha = loginCheck($username);
    if ($captcha) {
      $ret['errno'] = 257;
      $ret['code_string'] = $captcha;
      $ret['captcha'] = 'https://passport.baidu.com/cgi-bin/genimage?'.$captcha;
      return $ret;
    }
  }
  $guid = GUID();
  getToken($guid);
  //加密登录不太灵，不知道怎么回事，抽风似的，不用了
  /*$_key = request('https://passport.baidu.com/v2/getpublickey?token='.$bdstoken);
  $key = json_decode(str_replace("'", '"', $_key['body']), true);
  if (!isset($key['pubkey']) || !isset($key['key'])) {
    echo '无法获取public key！请联系开发者！服务器返回：'.$_key['body'];
    die();
  }
  $pubkey = openssl_pkey_get_public($key['pubkey']);
  openssl_public_encrypt($password, $crypted_password, $pubkey);
  $crypted_password = base64_encode($crypted_password);
  $result = request('https://passport.baidu.com/v2/api/?login', "charset=utf-8&tpl=netdisk&subpro=netdisk_web&apiver=v3&tt=".time()."&token=$bdstoken&loginmerge=true&safeflg=0&codestring=$codestring&detect=1&foreignusername=&gid=&idc=&isPhone=&loglogintype=pc_loginBasic&logintype=basicLogin&mem_pass=on&password=$crypted_password&rsakey={$key['key']}&crypttype=12&quick_user=0&safeflag=0&staticpage=http://pan.baidu.com/res/static/thirdparty/pass_v3_jump.html&u=http://pan.baidu.com/&username=$username&countrycode=&verifycode=$captcha&gid=$guid&ppui_logintime=".mt_rand(10000, 15000));*/
  $result = request('https://passport.baidu.com/v2/api/?login', "charset=utf-8&tpl=netdisk&subpro=netdisk_web&apiver=v3&tt=".time()."&token=$bdstoken&loginmerge=true&safeflg=0&codestring=$codestring&detect=1&foreignusername=&gid=&idc=&isPhone=&loglogintype=pc_loginBasic&logintype=basicLogin&mem_pass=on&password=$password&quick_user=0&safeflag=0&staticpage=http://pan.baidu.com/res/static/thirdparty/pass_v3_jump.html&u=http://pan.baidu.com/&username=$username&countrycode=&verifycode=$captcha&gid=$guid&ppui_logintime=".mt_rand(10000, 15000));
  parse_str(findBetween($result['body'], 'href += "', '"'), $output);
  if (!isset($output['err_no'])) {
    echo '无法登录！请联系开发者！服务器返回：<xmp>'.$result['body'];
    die();
  }
  $ret['errno'] = $output['err_no'];
  if (strlen($output['codeString'])) {
    $ret['code_string'] = $output['codeString'];
    $ret['captcha'] = 'https://passport.baidu.com/cgi-bin/genimage?'.$output['codeString'];
  }
  return $ret;
}

function getFileList($folder) {
  global $bdstoken;
  $list = array();
  $page = 1;
  $size = -1;
  while ($size) {
    $ret = request("http://pan.baidu.com/api/list?channel=chunlei&clienttype=0&web=1&num=1000&page=$page&dir=$folder&order=time&desc=1&showempty=0&bdstoken=$bdstoken&channel=chunlei&clienttype=0&web=1&app_id=250528");
    $ret = json_decode($ret['body'], true);
    if (!isset($ret['list'])) {
      return array();
    }
    $size = count($ret['list']);
    $page++;
    foreach ($ret['list'] as $k => $v) {
      $list[] = array('fid' => number_format($v['fs_id'], 0, '', ''), 'name' => $v['path'], 'isdir' => $v['isdir']);
    }
  }
  return $list;
}

function share($fid, $code, $show_result = false) {
  global $bdstoken;
  if (strlen($code) != 4) {//我看你还抽不
    $post="fid_list=%5B$fid%5D&schannel=0&channel_list=%5B%5D";
  } else {
    $post="fid_list=%5B$fid%5D&schannel=4&channel_list=%5B%5D&pwd=$code";
  }
  $ret = request("http://pan.baidu.com/share/set?channel=chunlei&clienttype=0&web=1&bdstoken=$bdstoken&channel=chunlei&clienttype=0&web=1&app_id=250528", $post);
  $ret = json_decode($ret['body']);
//print_r($ret);
  //die();
  if ($show_result !== false) {
    if (!$ret->errno) {
      echo '<p>分享创建成功。<br />分享地址为：'.$ret->link.'<br />短地址为：'.$ret->shorturl.'<br />提取码为：'.$code.'</p>';
    }
  }
  if ($ret->errno || !isset($ret->shorturl) || !$ret->shorturl) {
    wlog('分享失败：'.print_r($ret, true), 2);
    return false;
  }
  return $ret->shorturl;
}
function checkShare($id, $link, $name) {
	global $database;
	if(!$link || $link  == '/s/fakelink') {
		$url='';
		$ret['conn_valid']=true;
		$ret['user_valid']=true;
		$ret['valid']=false;
	} else {
		$url='http://pan.baidu.com'.$link;
		$check=request($url);
		if(strpos($check['body'],'你所访问的页面不存在了。')) {
			$ret['conn_valid']=false;
		}else if(strpos($check['body'],'涉及侵权、色情、反动、低俗')===false && strpos($check['body'],'分享的文件已经被')===false && $link) {
			$ret['conn_valid']=true;
			$ret['user_valid']=true;
			$ret['valid']=true;
			$context=json_decode(findBetween($check['body'],'var _context = ',';'),true);
			$current_path=$context['file_list']['list'][0]['path'];
			if($current_path!=$name && $context) { //自动修复错误的路径
				$database->update('watchlist', array('name' => $current_path), array('id' => $id));
				$name = $current_path;
			}
		}elseif(strpos($check['body'],'加密分享了文件')!==false) {
			$ret['conn_valid']=true;
			$ret['user_valid']=false;
		} else {
			$ret['conn_valid']=true;
			$ret['user_valid']=true;
			$ret['valid']=false;
		}
	}
	$ret['url']=$url;
	return $ret;
}

function getWatchlist() {
  global $database, $uid;
	$list = $database->select('watchlist', array('[>]users' => array('watchlist.user_id' => 'ID')), array('watchlist.id', 'watchlist.fid', 'watchlist.name', 'watchlist.link'), array('watchlist.user_id' => $uid));
  $_list = array();
  $list_filenames = array();
  foreach($list as $k => $v) {
    $_list[$v['fid']] = array('id' => $v['id'], 'filename' => $v['name'], 'link' => $v['link']);
    $list_filenames[$v['fid']] = $v['name'];
  }
  return array('list' => $_list, 'list_filenames' => $list_filenames);
}

function getFileMetas($file) {
  global $ua, $bdstoken;
  $post = 'target=%5B%22'.urlencode($file).'%22%5D';
  $ret = request("http://pan.baidu.com/api/filemetas?blocks=1&dlink=1&bdstoken=$bdstoken&channel=chunlei&clienttype=0&web=1&app_id=250528", $post);
  $ret = json_decode($ret['body'], true);
  if ($ret['errno']) {
    wlog('文件 '.$file.' 获取分片列表失败：'.$ret['errno'], 2);
    return false;
  }
  return $ret;
}

function getDownloadLinkDownload($file) {
  $ret = request("http://d.pcs.baidu.com/rest/2.0/pcs/file?app_id=250528&method=download&check_blue=1&ec=1&path=".urlencode($file)."&err_ver=1.0&es=1&sup=1", NULL, false);
  $json = json_decode($ret['body'], true);
  if (!isset($json['error_code']) || $json['error_code'] != 302 || !isset($ret['header']['location'])) {
    wlog('文件 '.$file.' 获取下载地址失败[API: download] '.$ret['body'], 2);
    return false;
  }
  return $ret['header']['location'];
}

//下面的接口都被限速了
function getDownloadLinkLocatedownloadV40($file) {
  $ret = request("http://pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&check_blue=1&es=1&esl=1&app_id=250528&path=".urlencode($file).'&ver=4.0&dtype=1&err_ver=1.0');
  $ret = json_decode($ret['body'], true);
  if (!isset($ret['urls'])) {
    wlog('文件 '.$file.' 获取下载地址失败[API: locatedownload 4.0] '.json_encode($ret), 2);
    return false;
  }
  function F($e){ return $e['url']; }
  return array_map('F', $ret['urls']);
}

function getDownloadLinkLocatedownloadV10($file) {
  global $bdstoken;
  $ret = request("http://pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&bdstoken=$bdstoken&app_id=250528&path=".urlencode($file));
  $ret = json_decode($ret['body'], true);
  if (isset($ret['errno'])) {
    wlog('文件 '.$file.' 获取下载地址失败[API: locatedownload 1.0] '.$ret['errno'], 2);
    return false;
  }
  foreach($ret['server'] as &$v) {
    $v = 'http://' . $v . $ret['path'];
  }
  return $ret['server'];
}