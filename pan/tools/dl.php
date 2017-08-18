<?php
require(dirname(__FILE__).'/../includes/common.php');
loginRequired($_SERVER['PHP_SELF']);
print_header('下载文件');
if (!isset($_SERVER['QUERY_STRING']) || !isset($_SESSION['uid'])) {
	alert_error('找不到文件', false);
}

if (!loginFromDatabase($_SESSION['uid'])) {
  alert_error('cookie失效，或者百度封了IP！', false);
}

$link = getDownloadLinkLocatedownloadV40(urldecode($_SERVER['QUERY_STRING']));
$link2 = getDownloadLinkLocatedownloadV10(urldecode($_SERVER['QUERY_STRING']));
$link3 = getDownloadLinkDownload(urldecode($_SERVER['QUERY_STRING']));

if (!$link) {
	alert_error('找不到文件', false);
}
echo '<p>高速下载地址，如下载不走，请刷新几次直到出现新的地址：<br /><br />';
echo '<a target="_blank" rel="noreferrer" href="'.$link3.'">'.$link3.'</a><br /><br />';
echo '旧接口下载地址1（限速，可能限IP）：<br />';
foreach ($link as $v) {
	echo '<br /><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a><br />';
}
echo '</p><p>旧接口下载地址2（限速，不限IP）：<br />';
foreach ($link2 as $v) {
	echo '<br /><a target="_blank" rel="noreferrer" href="'.$v.'">' . $v . '</a><br />';
}
?></p>
</body>
</html>
