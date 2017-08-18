<?php
require(dirname(__FILE__).'/../includes/common.php');
loginRequired($_SERVER['PHP_SELF']);
print_header('创建分享');

if(!isset($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
	alert_error('未选择用户', false);
}

if (!loginFromDatabase($_SESSION['uid'])) {
  alert_error('cookie失效，或者百度封了IP！', false);
}

if(isset($_POST['submit']) && $_POST['submit']=='创建' && isset($_POST['type'])) {
	if ($_POST['type'] == 0 && strlen($_POST['code'])!=4) {
		echo '<h1>错误：提取码位数不对。请输入4个半角字符，或者1个全角字符和1个半角字符的组合。</h1>';
	} else if ($_POST['type'] < 0 || $_POST['type'] > 2) {
		echo '<h1>错误：无效参数</h1>';
	} else {
		if ($_POST['type'] == 0) {
			$result = share($_POST['fid'],$_POST['code'], true);
		} elseif ($_POST['type'] == 1) {
			$result = share($_POST['fid'],'无',true);
		} elseif ($_POST['type'] == 2) {
			alert_error('暂不支持此种分享的创建！', false);
		}
    if (!$result) {
      alert_error('分享创建失败！', false);
    }
		die();
	}
} else {
	if(!isset($_SERVER['QUERY_STRING']) || !isset($_SESSION['file_can_add'][$_SERVER['QUERY_STRING']])) {
		alert_error('请勿直接访问本页。','../browse.php');
	}
}
echo "<h2>创建分享</h2>";
?>
<form method="post" action="share.php">
<input type="hidden" name="fid" value="<?php echo $_SERVER['QUERY_STRING'] ?>" />
分享选项：<br />
<input type="radio" name="type" value="0" checked="checked" />私密分享（有提取码：<input type="text" name="code" />）<br />
<input type="radio" name="type" value="2" disabled="disabled" />私密分享（无提取码）（开发中！）<br />
<input type="radio" name="type" value="1" />公开分享<br />
<br />
<input type="submit" name="submit" value="创建" />
</form>
</body></html>
