<?php
require 'includes/common.php';
loginRequired($_SERVER['PHP_SELF']);

if (isset($_POST['delete'])) {
	$data = $database->get('watchlist', '*', array('AND' => array('id' => $_POST['delete'], 'siteu_id' => $_SESSION['siteuser_id'])));
	if (empty($data)) {
		echo '{"ret":"找不到要删除的记录！"}';
		die();
	}
	$database->delete('watchlist', array('id' => $_POST['delete']));
	wlog('删除记录：'.$_POST['delete'], 1);
	echo '{"ret":"删除成功！"}';
	die();
}
print_header('一键补档管理后台');
wlog('访问主页');
?>
<script>
function dlt(id) {
	if (confirm('确认要删除这条记录吗？')) {
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState == 4){ 
				var ret;
				try {
					ret = JSON.parse(xmlHttp.responseText);
				} catch (e) {
					alert('后台返回错误，请重试');
				}
				if(ret !== false) {
					alert(ret.ret);
					document.getElementById('TABLE').deleteRow(document.getElementById('ROW' + id).rowIndex);
				}
			}
		}
		xmlHttp.open("POST","index.php",true);
		xmlHttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
		xmlHttp.send('delete=' + id);
	}
}
</script>
<h1 class="page-header">一键补档管理后台</h1>
<ul class="nav nav-pills">
	<li><a href="addlink.php" target="_blank">添加记录</a></li>
	<li><a href="browse.php" target="_blank">浏览文件</a></li>
</ul>
<table class="table table-striped" id="TABLE">
<thead><tr>
	<th width="12%">模式</th>
	<th width="5%">ID</th>
	<th width="10%">fs_id</th>
	<th width="29%">文件名</th>
	<th width="8%">访问地址</th>
	<th width="5%">提取</th>
	<th width="10%">百度用户名</th>
	<th width="5%">补档次数</th>
	<th width="5%">删除</th>
</tr></thead><tbody>
<?php
$perPageCount = 30;
$offset = isset($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0;
$itemCount = $database->count('watchlist', array('siteu_id' => $_SESSION['siteuser_id']));
if ($offset < 0) $offset = 0;
elseif ($offset >= $itemCount) $offset = $itemCount - 1;
$list = $database->select('watchlist', array('[>]users' => array('user_id' => 'ID')),
	array('watchlist.id', 'watchlist.fid', 'watchlist.name',
		'watchlist.link', 'watchlist.count', 'watchlist.pass', 'watchlist.user_id',
		'watchlist.failed', 'username', 'cookie'),
	array('watchlist.siteu_id' => $_SESSION['siteuser_id'],
		'ORDER' => array('watchlist.failed' => 'DESC', 'watchlist.id' => 'DESC'),
		'LIMIT' => array($offset, $perPageCount)));

foreach($list as $k=>$v) {
	echo '<tr id="ROW'.$v['id'].'"><td>';
	if ($v['failed'] == 1) {
		echo '<font color="red">补档失败，可能是网络问题，如果持续出现，请检查文件</font>';
	} else if ($v['failed'] == 2) {
		echo '<font color="red">这个文件被温馨提示掉了，请在跳转页中进行提取来尝试修复</font>';
	} else if ($v['failed'] == 3) {
		echo '<font color="red">文件不存在</font>';
	} else {
		echo '<font color="green">自动补档保护中</font>';
	}
	?>
	</td>
	<td><?=$v['id']?></td>
	<td><?=$v['fid']?></td>
	<td><?php echo htmlspecialchars($v['name']); ?></td>
	<td><a class="btn btn-default" href="jump.php?<?php echo $v['id']; ?>"  target="_blank">[<?php echo $v['id']; ?>]</a></td>
	<td><?=$v['pass']?></td>
	<td><?=$v['username']?></td>
	<td><?=$v['count']?></td>
	<td><button class="btn btn-danger" onclick="dlt(<?php echo $v['id']; ?>);">删除</button></td>
	</tr>
	<?php
	$id = $v['id'];
}
?>
</tbody></table><div>
<?php
if ($offset > 0) {
	$prevOffset = $offset - $perPageCount;
	$prevOffset = $prevOffset < 0 ? 0 : $prevOffset;
	echo '<a class="btn btn-default" type="button" href="?offset=', $prevOffset, '">&lt;&lt;上一页</a>&nbsp;';
}
if ($itemCount - $offset > $perPageCount)
	echo '&nbsp;<a class="btn btn-default" type="button" href="?offset=', $offset + $perPageCount, '">下一页&gt;&gt;</a>';
?>
</div></body></html>
