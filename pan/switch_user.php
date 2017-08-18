<?php
require 'includes/common.php';
loginRequired($_SERVER['PHP_SELF']);

if (isset($_GET['switch_user'])) {
  if (!is_numeric($_GET['switch_user'])) {
		addMessage('用户ID错误', 'danger');
  }
  $result = loginFromDatabase($_GET['switch_user'], $_SESSION['siteuser_id']);
  if ($result === -1) {
		addMessage('找不到用户', 'danger');
  } else if ($result === false) {
		addMessage('cookie失效，或者百度封了IP！', 'danger');
  } else {
		unset($_SESSION['file_can_add'], $_SESSION['folder']);
		$_SESSION['uid'] = $uid;
		wlog('切换用户：['.$uid.']'.$username);
		header('Location: browse.php');
		exit;
	}
} elseif (isset($_REQUEST['remove_user'])) {
  if (!is_numeric($_REQUEST['userId'])) {
		addMessage('用户ID错误', 'danger');
  } else {
		$user = $database->get('users', 'username', array('AND' => array('ID' => $_REQUEST['userId'], 'siteu_id' => $_SESSION['siteuser_id'])));
    if (empty($user)) {
			addMessage('找不到用户', 'danger');
    } else {
      if (isset($_POST['confirm'])) {
				$database->delete('users', array('ID' => $_POST['userId']));
        wlog('删除用户成功：['.$_POST['userId'].']'.$_POST['name'], 1);
				addMessage('用户“'.$_POST['name'].'”删除成功。', 'success');
      } else {
        print_header('确认删除'); ?>
        <h1 class="page-header">删除用户</h1>
				<div class="panel panel-danger">
				<div class="panel panel-heading"><h3 class="panel-title">确定要删除用户“<?=$user?>”？</h3></div>
				<div class="panel panel-body">
				<p>警告：删除用户将同时删除此用户的全部补档记录。<br />确认要继续吗？</p>
				<form method="post" action="">
          <input type="hidden" name="remove_user" value="1" />
          <input type="hidden" name="userId" value="<?=$_REQUEST['userId']?>" />
          <input type="hidden" name="name" value="<?=$user?>" />
          <input class="btn btn-danger" type="submit" name="confirm" value="确认删除" />
				</form></div></div></body></html>
        <?php
        exit;
      }
    }
  }
} elseif (isset($_GET['add_user'])) {
  print_header('添加用户');
  if (isset($_POST['create_user'])) {
    if (!isset($_POST['name']) || $_POST['name'] == '') {
      addMessage('错误：请输入用户名', 'danger');
    } else if (!isset($_POST['password']) || $_POST['password']=='') {
      addMessage('错误：请输入密码', 'danger');
    } else {
      if (isset($_POST['code_string'])) {
        $result = login($_POST['name'], $_POST['password'], $_POST['code_string'], $_POST['captcha']);
      } else {
        $result = login($_POST['name'], $_POST['password']);
      }
      if (!$result['errno']) {
        global $bduss;
				# medoo 不滋瓷原来的 INSERT ON DUPLICATE KEY 语法
				if ($database->has('users', array('username' => $_POST['name'])))
					$database->update('users', array('cookie' => get_cookie()), array('AND' => array('username' => $_POST['name'], 'siteu_id' => $_SESSION['siteuser_id'])));
				else $database->insert('users', array('username' => $_POST['name'], 'cookie' => get_cookie(), 'siteu_id' => $_SESSION['siteuser_id'], 'newmd5' => ''));
        wlog('添加用户：'.$_POST['name']);
        $check = validateCookieAndGetBdstoken(); //应对百度的新登录机制
        if (!$check) addMessage('登录成功，但访问百度云失败，可能百度改了验证机制，请联系开发者！', 'warning');
        addMessage('已添加或更新用户“'.$_POST['name'].'”。', 'success');
      } else {
				switch ($result['errno']) {
					case 4:			addMessage('密码错误', 'danger'); break;
					case 257:		addMessage('请输入验证码', 'danger'); break;
					case 6:			addMessage('验证码错误', 'danger'); break;
					case 120021:	addMessage('请验证登录（使用本服务器所在地IP登录百度云，按照提示操作）', 'danger'); break;
					default:		addMessage('错误编号：'.$result['errno'], 'danger'); break;
				}
			}
    }
  } elseif (isset($_POST['create_cookie'])) {
    if (!isset($_POST['name']) or $_POST['name'] == '') {
			addMessage('错误：请输入用户名', 'danger');
    } elseif (!isset($_POST['login_cookie']) or $_POST['login_cookie'] == '') {
			addMessage('错误：请输入Cookies', 'danger');
    } else {
      set_cookie($_POST['login_cookie']);
			if ($database->has('users', array('username' => $_POST['name'])))
				$database->update('users', array('cookie' => $_POST['login_cookie']), array('AND' => array('username' => $_POST['name'], 'siteu_id' => $_SESSION['siteuser_id'])));
			else $database->insert('users', array('username' => $_POST['name'], 'cookie' => $_POST['login_cookie'], 'siteu_id' => $_SESSION['siteuser_id'], 'newmd5' => ''));
      wlog('添加用户：'.$_POST['name']);
      $check = validateCookieAndGetBdstoken();
			if (!$check) addMessage('访问百度云失败！Cookies可能已经失效。', 'warning');
			addMessage('已添加或更新用户“'.$_POST['name'].'”。', 'success');
      }
    }
    ?>
		<h1 class="page-header">添加用户</h1>
		<?php showMessage(); ?>
		<div class="panel panel-primary">
		<div class="panel-heading"><h3 class="panel-title">使用百度账号密码</h3></div>
		<div class="panel-body"><form method="post" action="switch_user.php?add_user=1">
		用户名：
		<input class="form-control" style="max-width: 330px;" type="text" name="name" value="<?php echo isset($_POST['name'])?$_POST['name']:(isset($_GET['name'])?$_GET['name']:''); ?>"/>
		密码：<input class="form-control" style="max-width: 330px;" type="password" name="password" />
    <?php if (isset($result['code_string'])) { ?>
		验证码：<input class="form-control" style="max-width: 330px;" type="text" name="captcha" /><img src="<?php echo $result['captcha']; ?>" />
    <input type="hidden" name="code_string" value="<?php echo $result['code_string']; ?>" />
    <?php } ?>
		<br /><input class="btn btn-primary" type="submit" name="create_user" value="登录" />
		</form></div></div>
		<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">使用Cookie登录账号</h3></div>
		<div class="panel-body">
			<p>
				使用Cookies登录需要您提取您的浏览器中的部分Cookies内容。<br />
				现在，请使用您的浏览器打开一个隐私窗口（Chrome浏览器快捷键Ctrl + Shift + N；Firefox快捷键Ctrl + Shift + P）。<br />
				访问网址<a href="https://pan.baidu.com/" target="_blank">https:pan.baidu.com</a>。然后登录您的百度账号。<br />
				在浏览器菜单中找到“查看元素”打开开发者工具（快捷键F12）。<br />
				在开发者工具中找到“网络”选项卡，按F5刷新页面，找到一个指向域名pan.baidu.com的请求并复制请求头中的Cookie列<br />
				将您刚才复制的内容粘贴在下面的文本框内。<br />
				一个可用的Cookie中必定包含项：BAIDUID、BDUSS、STOKEN、PANPSC 4项值。
			</p>
			<form method="post" action="switch_user.php?add_user=1">
			用户名：<input class="form-control" style="max-width: 330px;" type="text" name="name" value="<?php echo isset($_POST['name'])?$_POST['name']:(isset($_GET['name'])?$_GET['name']:''); ?>" />
			Cookies：<textarea class="form-control" name="login_cookie" rows="5" style="width: 330px;"></textarea><br />
			<input class="btn btn-primary" type="submit" name="create_cookie" value="提交" />
			</form>
		</div></div>
		<p><a href="switch_user.php">返回</a></p>
    </body></html>
    <?php
    exit;
  }
$users = $database->select('users', '*', array('siteu_id' => $_SESSION['siteuser_id']));
print_header('选择用户');
?>
<h1 class="page-header">选择百度用户</h1>
<?php showMessage(); ?>
<div class="list-group">
<?php
foreach ($users as $k => $v) {
	?>
	<span class="list-group-item">
	<a class="label label-primary" href="switch_user.php?switch_user=<?php echo $v['ID']; ?>"><?php echo $v['username']; ?></a>
	<a class="label label-danger" href="switch_user.php?remove_user&amp;userId=<?php echo $v['ID']; ?>">删除用户</a>
	</span>
	<?php
}
?>
</div>
<p><a class="btn btn-success" href="switch_user.php?add_user=1">添加用户/修复失效cookie</a></p>
</body></html>