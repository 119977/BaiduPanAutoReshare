<?php
$sqls_mysql = array(
	'SET FOREIGN_KEY_CHECKS=0',
	'DROP TABLE IF EXISTS `log_new`',
	'DROP TABLE IF EXISTS `block_list`',
	'DROP TABLE IF EXISTS `watchlist`',
	'DROP TABLE IF EXISTS `users`',
	'DROP TABLE IF EXISTS `siteusers`',
	'SET FOREIGN_KEY_CHECKS=1',
	'CREATE TABLE `siteusers` (
		`ID` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`name` VARCHAR(16) NOT NULL UNIQUE,
		`passwd` VARCHAR(32) NOT NULL,
		`hash` VARCHAR(32) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	'CREATE TABLE `log_new` (
		`ID` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`IP` VARCHAR(15) NOT NULL,
		`level` TINYINT(4) NOT NULL,
		`content` TEXT NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	'CREATE TABLE `users` (
		`ID` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`siteu_id` INT(11) NOT NULL,
		`username` VARCHAR(255) NOT NULL UNIQUE,
		`cookie` TEXT NOT NULL,
		`newmd5` TEXT NOT NULL,
		INDEX (`siteu_id`),
		FOREIGN KEY (`siteu_id`) REFERENCES `siteusers` (`ID`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	'CREATE TABLE `watchlist` (
		`id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`fid` TINYTEXT NOT NULL,
		`name` TEXT NOT NULL,
		`link` TINYTEXT NOT NULL,
		`count` INT(11) NOT NULL,
		`pass` VARCHAR(4) DEFAULT NULL,
		`user_id` INT(11) NOT NULL,
		`siteu_id` INT(11) NOT NULL,
		`failed` TINYINT(1) NOT NULL DEFAULT 0,
		INDEX (`user_id`),
		INDEX (`siteu_id`),
		FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE,
		FOREIGN KEY (`siteu_id`) REFERENCES `siteusers` (`ID`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	'CREATE TABLE `block_list` (
		`ID` int(11) NOT NULL PRIMARY KEY,
		`block_list` LONGTEXT NOT NULL,
		FOREIGN KEY (`ID`) REFERENCES `watchlist` (`ID`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8'
);
$sqls_sqlite = array(
	'DROP TABLE IF EXISTS `log_new`',
	'DROP TABLE IF EXISTS `block_list`',
	'DROP TABLE IF EXISTS `watchlist`',
	'DROP TABLE IF EXISTS `users`',
	'DROP TABLE IF EXISTS `siteusers`',
	'CREATE TABLE `siteusers` (
		`ID` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		`name` TEXT NOT NULL UNIQUE,
		`passwd` TEXT NOT NULL,
		`hash` TEXT NOT NULL )',
	'CREATE TABLE `log_new` (
		`ID` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		`IP` TEXT NOT NULL,
		`level` TEXT NOT NULL,
		`content` TEXT NOT NULL )',
	'CREATE TABLE `users` (
		`ID` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		`siteu_id` INTEGER NOT NULL,
		`username` TEXT NOT NULL UNIQUE,
		`cookie` TEXT NOT NULL,
		`newmd5` TEXT NOT NULL,
		FOREIGN KEY (`siteu_id`) REFERENCES `siteusers` (`ID`) ON DELETE CASCADE )',
	'CREATE TABLE `watchlist` (
		`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		`fid` TEXT NOT NULL,
		`name` TEXT NOT NULL,
		`link` TEXT NOT NULL,
		`count` INTEGER NOT NULL,
		`pass` TEXT DEFAULT NULL,
		`user_id` INTEGER NOT NULL,
		`siteu_id` INTEGER NOT NULL,
		`failed` INTEGER NOT NULL DEFAULT 0,
		FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE,
		FOREIGN KEY (`siteu_id`) REFERENCES `siteusers` (`ID`) ON DELETE CASCADE )',
	'CREATE TABLE `block_list` (
		`ID` int(11) NOT NULL PRIMARY KEY,
		`block_list` LONGTEXT NOT NULL,
		FOREIGN KEY (`ID`) REFERENCES `watchlist` (`ID`) ON DELETE CASCADE )'
);

require_once 'includes/medoo.php';
session_start();
header('Content-Type: text/html; charset=utf-8');
$nocontinue = FALSE;
$_REQUEST['step'] = isset($_REQUEST['step']) ? intval($_REQUEST['step']) : 0;
if (file_exists('config.php')) {
	$nocontinue = TRUE;
	$_REQUEST['step'] = 0;
}
$titles = array('安装说明', '输入数据库信息', '导入数据库表', '创建初始用户', '完成');
?><!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8" />
	<title>安装：度娘盘分享守护程序</title>
</head>
<body>
<h1>Step <?=$_REQUEST['step']?>/4：<?=$titles[$_REQUEST['step']]?></h1>
<?php
function reportError($info) {
	?>
	<p>错误：<?=$info?></p>
	<p><a href="install.php?step=<?php echo $_SERVER['REQUEST_METHOD'] == 'POST' ? $_REQUEST['step'] : $_REQUEST['step'] - 1; ?>">返回</a></p>
	</body></html>
	<?php
	exit;
}
$db = NULL;
function connect_db() {
	global $db;
	try {
		$db = new medoo(array(
			'database_type' => $_SESSION['db_type'],
			'database_name' => $_SESSION['db_name'],
			'database_file' => $_SESSION['db_path'],
			'server' => $_SESSION['db_host'],
			'username' => $_SESSION['db_user'],
			'password' => $_SESSION['db_pass'],
			'charset' => 'utf8'
		));
		$_SESSION['db_checked'] = TRUE;
		return TRUE;
	} catch (Exception $e) {
		$_SESSION['db_checked'] = FALSE;
		return FALSE;
	}
}
switch ($_REQUEST['step']) {
	case 0:
		?>
		<p>欢迎使用度娘盘分享守护程序，本安装引导程序将带领您完成本程序的初始化。</p>
		<p>
			<?php if ($nocontinue) { ?>
				在开始之前，请删除本目录下的config.php文件，然后刷新本页面。
			<?php  } else { ?>
				<a href="install.php?step=1">下一步</a>
			<?php } ?>
		</p>
		<?php
		break;
	case 1:
		if (isset($_POST['update']) and isset($_POST['db_type'])) {
			if ($_POST['db_type'] === 'sqlite') {
				if (!isset($_POST['db_path']) or $_POST['db_path'] === '') reportError('数据库文件路径不能为空');
				if (!file_exists($_POST['db_path'])) @file_put_contents($_POST['db_path'], '');
				if (!is_writable($_POST['db_path'])) reportError('数据库文件路径无写入权限');
				$_SESSION['db_type'] = 'sqlite';
				$_SESSION['db_path'] = $_POST['db_path'];
				$_SESSION['db_host'] = $_SESSION['db_user'] =  $_SESSION['db_pass'] =  $_SESSION['db_name'] = '';
				if (!connect_db()) reportError('数据库连接失败！');
			} elseif ($_POST['db_type'] === 'mysql') {
				$_SESSION['db_type'] = 'mysql';
				$_SESSION['db_host'] = $_POST['db_host'];
				$_SESSION['db_user'] = $_POST['db_user'];
				$_SESSION['db_pass'] = $_POST['db_pass'];
				$_SESSION['db_name'] = $_POST['db_name'];
				$_SESSION['db_path'] = '';
				if (!connect_db()) reportError('数据库连接失败！');
			} else reportError('无法识别的数据库！');
			?>
			<p>连接数据库成功</p>
			<p>接下来将会重新创建数据库表，您当前数据库中的一些数据将可能丢失，请做好备份！</p>
			<p><a href="install.php?step=2">继续</a></p>
			<?php
		} else {
			?>
			<p>请填写您的服务器数据库信息：</p>
			<form action="" method="post">
				<input type="hidden" name="step" value="1" />
				数据库类型：<select name="db_type" onchange="db_switch(this.value);">
					<option value="mysql">MySQL</option>
					<option value="sqlite">SQLite</option>
				</select>
				<div id="sdb_a">
					地址：<input type="text" name="db_host" value="localhost" /><br />
					用户名：<input type="text" name="db_user" value="root" /><br />
					密码：<input type="password" name="db_pass" value="" /><br />
					数据库名：<input type="text" name="db_name" value="mysql" />
				</div>
				<div id="sdb_b" style="display: none;">
					<font color="red"><div id="warn">
						要使用SQLite，文件路径必须有读写权限！<br />
						SQLite数据库性能一般不如MySQL，如果您的服务器安装有MySQL，建议使用MySQL而不是SQLite。<br />
						为了数据安全，文件路径所在目录不应该能被HTTP访问。<br />
						文件路径建议填写绝对路径。
					</div></font>
					文件路径：<input type="text" name="db_path" value="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/db.sqlite" size="32" />
				</div>
				<input type="submit" name="update" value="确定" />
			</form>
			<script type="text/javascript">
				function db_switch(dbtype) {
					if (dbtype == "sqlite") {
						document.getElementById("sdb_a").style.display = "none";
						document.getElementById("sdb_b").style.display = "block";
					} else if (dbtype == "mysql") {
						document.getElementById("sdb_a").style.display = "block";
						document.getElementById("sdb_b").style.display = "none";
					} else {alert("错误的参数！")}
				}
			</script>
			<?php
		}
		break;
	case 2:
		if (!connect_db()) header('Location: install.php?step=1');
		else {
			if ($_SESSION['db_type'] === 'sqlite') {
				foreach ($sqls_sqlite as $sql) $db->query($sql);
			} elseif ($_SESSION['db_type'] === 'mysql'){
				foreach ($sqls_mysql as $sql) $db->query($sql);
			}
			?>
			<p>数据库表已经建立</p>
			<p><a href="install.php?step=3">下一步</a></p>
			<?php
		}
		break;
	case 3:
		if (!connect_db()) header('Location: install.php?step=1');
		else {
			if (isset($_POST['update'])) {
				if (!isset($_POST['u_name']) or $_POST['u_name'] == '') reportError('用户名不能为空');
				if (!isset($_POST['u_pass']) or strlen($_POST['u_pass']) < 5) reportError('用户密码不能少于5个字符');
				if (!isset($_POST['u_cfim']) or $_POST['u_pass'] !== $_POST['u_cfim']) reportError('两次密码输入不匹配');
				if (!preg_match('/[0-9a-z]{3,16}/i', $_POST['u_name'])) reportError('用户名必须是3~16位的字母和（或）数字组合');
				$userhash = md5($_POST['u_name'].time().mt_rand(0, 65535));
				$db->insert('siteusers', array('name' => $_POST['u_name'], 'passwd' => md5($_POST['u_pass']), 'hash' => $userhash));
				?>
				<p>已创建用户<?=$_POST['u_name']?>，请牢记您设置的密码！</p>
				<p>接下来，将储存本程序的配置。</p>
				<p><a href="install.php?step=4">下一步</a></p>
				<?php
			} else {
				?>
				<p>请创建一个管理用户（非百度账号）：</p>
				<form action="" method="post">
					<input type="hidden" name="step" value="3" />
					用户名：<input type="text" name="u_name" value="user" /><br />
					密码：<input type="password" name="u_pass" value="" /><br />
					确认密码：<input type="password" name="u_cfim" value="" /><br />
					<input type="submit" name="update" value="确定" />
				</form>
				<p><a href="install.php?step=4">跳过此步骤</a></p>
				<?php
			}
		}
		break;
	case 4:
		if (!connect_db()) header('Location: install.php?step=1');
		$jumpath = dirname($_SERVER['PHP_SELF']);
		if ($jumpath === '/') $jumpath = '';
		$rc = md5(time().mt_rand(0, 65535));
		$configFileContent = <<<EOT
<?php
\$dbtype = '${_SESSION['db_type']}';
\$host = '${_SESSION['db_host']}';
\$user = '${_SESSION['db_user']}';
\$pass = '${_SESSION['db_pass']}';
\$db = '${_SESSION['db_name']}';
\$dbpath = '${_SESSION['db_path']}';
\$ua='netdisk;4.6.1.0;PC;PC-Windows;6.2.9200;WindowsBaiduYunGuanJia';
\$jumper = 'http://${_SERVER['HTTP_HOST']}$jumpath/jump.php?';
\$enable_direct_link = TRUE;
\$enable_direct_video_play = FALSE;
\$force_direct_link = FALSE;
\//生成新文件名，不含扩展名
\function generateNewName() {
\	return '[GalACG]EX' . str_pad((time() - 1402761600),9,'0',STR_PAD_LEFT);
\}
\$registCode = '$rc';
EOT;
		file_put_contents('config.php', $configFileContent);
		?>
		<p>感谢您选择本程序！您的程序已经成功安装。</p>
		<p>
			如果一切顺利的话，您的网站现已可用。<br />
			如果要添加新管理员用户，请使用下框内的“注册码”，到<a href="user.php?action=register" target="_blank">此页面</a>注册。<br />
			<input type="text" value="<?=$rc?>" size="32" /><br />
			如果在使用中遇到什么问题，可以到<a href="https://github.com/slurin/BaiduPanAutoReshare/issue" target="_blank">Github</a>提出。<br />
			本程序原作者 虹原翼 ，<a href="https://github.com/NijiharaTsubasa/BaiduPanAutoReshare" target="_blank">原Github地址</a>，经Slurin修改。
		</p>
		<p><a href="index.php" target="_blank">前往本工具地址</a></p>
		<?php
		break;
}
?></body></html>
