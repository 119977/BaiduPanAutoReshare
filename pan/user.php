<?php
require_once 'includes/common.php';
session_start();
if (!isset($_REQUEST['action'])) $_REQUEST['action'] = 'profile';

switch ($_REQUEST['action']) {
	case 'login':
		if (isset($_POST['username']) and isset($_POST['password'])) {
			$password = md5($_POST['password']);
			$siteuser = $database->get('siteusers', '*', array('AND' => array('name' => $_POST['username'], 'passwd' => $password)));
			if (!empty($siteuser)) {
				$_SESSION['siteuser_id'] = $siteuser['ID'];
				setcookie('siteuser_id', $siteuser['ID'], time() + 15552000);
				setcookie('siteuser_hash', $siteuser['hash'], time() + 15552000);
				if (isset($_POST['ref'])) header('Location: '.$_POST['ref']);
				else header('Location: user.php?action=profile');
				exit;
			} else { $errInfo = '输入的用户名或密码不正确'; }
		}
		print_header('用户登录');
		?>
		<h1 class="page-header">度娘盘分享守护程序 - 登录</h1>
		<div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">管理员登录</h3></div>
		<div class="panel-body"><form action="" method="post">
			<?php if (isset($errInfo)) {echo '<div class="alert alert-danger">', $errInfo, '</div>';} ?>
			用户名：<input class="form-control" style="max-width: 330px;" type="text" name="username" />
			密码：<input class="form-control" style="max-width: 330px;" type="password" name="password" />
			<input type="hidden" name="action" value="login" />
			<?php if (isset($_REQUEST['ref']) and $_REQUEST['ref'] != '') { ?>
				<input type="hidden" name="ref" value="<?=$_REQUEST['ref']?>" />
			<?php } ?>
			<br /><input class="btn btn-primary" type="submit" value="登录" />
		</form></div></div></body></html>
		<?php
		break;
	case 'register':
		print_header('管理员账号注册');
		?>
		<h1 class="page-header">度娘盘分享守护程序 - 管理员注册</h1>
		<div class="panel panel-primary">
		<div class="panel-heading"><h3 class="panel-title">填写注册信息</h3></div>
		<div class="panel-body">
		<?php
		if ($registCode !== FALSE) {
			$e_msg = array();
			if (isset($_POST['username'])) {
				if (!preg_match('/[0-9a-z]{3,16}/i', $_POST['username'])) $e_msg[] = '用户名必须是3~16位的数字和（或）字母组合';
				if (!isset($_POST['password']) or strlen($_POST['password']) < 5) $e_msg[] = '密码长度必须大于5个字符';
				elseif (!isset($_POST['password_c']) or $_POST['password'] !== $_POST['password_c']) $e_msg[] = '两次密码输入不匹配';
				if ($registCode !== NULL) {
					if (!isset($_POST['reg_code']) or $_POST['reg_code'] !== $registCode) $e_msg[] = '注册码不正确！';
				}
				if (!$e_msg) {
					if ($database->has('siteusers', array('name' => $_POST['username']))) $e_msg[] = '相同的用户名已经存在';
				}
				if (!$e_msg) {
					$userHash = md5($_POST['username'].time().mt_rand(0, 65535));
					$database->insert('siteusers', array('name' => $_POST['username'], 'passwd' => md5($_POST['password']), 'hash' => $userHash));
					echo '<div class="alert alert-success">注册成功！<a href="user.php?action=login">前往登录</a></div>';
				}
			}
			if ($e_msg) echo '<div class="alert alert-danger">', implode('<br />', $e_msg), '</div>';
			?>
			<form action="" method="post">
				<input type="hidden" name="action" value="register" />
				用户名：<input class="form-control" style="max-width: 330px;" type="text" name="username" />
				密码：<input class="form-control" style="max-width: 330px;" type="password" name="password" />
				确认密码：<input class="form-control" style="max-width: 330px;" type="password" name="password_c" />
				<?php if ($registCode !== NULL) { ?>注册码：<input class="form-control" style="max-width: 330px;" type="text" name="reg_code" /><?php } ?>
				<br /><span><input class="btn btn-primary" type="submit" value="注册" /></span>
				<span><a class="btn btn-default" href="user.php?action=login">返回登录</a></span>
			</form></div></div>
			<?php
		} else {
			?>
			<div class="alert alert-warning"><p>当前网站管理员不允许注册。</p>
			<p><small>要变更此项配置，请编辑本目录下的config.php文件。</small></p></div></div></div>
			<?php
		}
		break;
	case 'profile':
		loginRequired('user.php?action=profile');
		$siteuser = $database->get('siteusers', '*', array('ID' => $_SESSION['siteuser_id']));
		if (isset($_POST['update'])) {
			if (isset($_POST['c_cp'])) {
				if (strlen($_POST['c_np']) >= 5 and $_POST['c_np'] === $_POST['c_cf']) {
					if (md5($_POST['c_cp']) === $siteuser['passwd']) {
						$newPassHash = md5($_POST['c_np']);
						$newUserHash = md5($siteuser['name'].time().mt_rand(0, 65535));
						$database->update('siteusers', array('passwd' => $newPassHash, 'hash' => $newUserHash), array('ID' => $_SESSION['siteuser_id']));
					} else $msg = '密码错误！';
				} else {
					$msg = '密码长度不够或两次输入密码不匹配！';
				}
			}
		}
		print_header('修改用户数据');
		?>
		<h1 class="page-header">管理员账号管理</h1>
		<p><a class="btn btn-link" href="index.php">返回补档列表</a></p>
		<div class="panel panel-default">
			<div class="panel-heading"><h3>用户信息</h3></div>
			<div class="panel-body">您的用户名：<?=$siteuser['name']?><br />您的用户ID：<?=$siteuser['ID']?></div>
		</div>
		<div class="panel panel-warning">
			<div class="panel-heading"><h3>修改用户密码</h3></div>
			<div class="panel-body">
			<div class="alert alert-info">修改密码后，您可能需要重新登陆。</div>
			<form action="" method="post">
				<?php if (isset($msg)) echo '<p>', $msg, '</p>'; ?>
				当前密码：<input class="form-control" style="max-width: 330px;" type="password" name="c_cp" />
				新密码：<input class="form-control" style="max-width: 330px;" type="password" name="c_np" />
				确认密码：<input class="form-control" style="max-width: 330px;" type="password" name="c_cf" />
				<input type="hidden" name="action" value="profile" />
				<br /><input class="btn btn-primary" type="submit" name="update" value="修改" />
			</form></div>
		</div>
		<?php
		break;
	case 'logout':
		loginRequired();
		if (isset($_POST['confirm'])) {
			$newUserHash = md5($_COOKIE['siteuser_hash'].time().mt_rand(0, 65535));
			$database->update('siteusers', array('hash' => $newUserHash), array('ID' => $_SESSION['siteuser_id']));
			unset($_COOKIE['siteuser_id']);
			unset($_COOKIE['siteuser_hash']);
			session_destroy();
			print_header('登出成功');
			?>
			<div class="alert alert-info">您已登出</div>
			<p><a class="btn btn-link" href="index.php">返回</a></p>
			<?php
		} else {
			print_header('登出');
			?>
			<h1 class="page-header">登出</h1>
			<div class="panel panel-info">
			<div class="panel-heading"><h3 class="panel-title">登出确认操作</h3></div>
			<div class="panel-body">
			<div class="alert alert-info">
				登出后，除非已经建立会话（本会话除外），所有登录过的设备都将被强制登出。<br />
				您需要重新使用您的用户名和密码来登录。
			</div>
			<form action="" method="post">
				<input type="hidden" name="action" value="logout" />
				<br /><input class="btn btn-warning" type="submit" name="confirm" value="继续登出" />
				<span><a class="btn btn-link" href="index.php">返回补档列表</a></span>
			</form></div></div>
			<?php
		}
		break;
	default:
		break;
}
?>
</body></html>