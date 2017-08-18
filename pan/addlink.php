<?php
require 'includes/common.php';
loginRequired($_SERVER['PHP_SELF']);

print_header('添加记录');
if(isset($_POST['submit'])) {
	if($_POST['code']=='') $_POST['code']=0;
	if($_POST['code']!=='0' && strlen($_POST['code'])!=4)
		echo '<h1>错误：提取码位数不对。请输入4个半角字符，或者1个全角字符和1个半角字符的组合。</h1>';
	else {
		if(substr($_POST['link'],0,20)=='http://pan.baidu.com')
			$_POST['link']=substr($_POST['link'],20);
		elseif(substr($_POST['link'],0,13)=='pan.baidu.com')
			$_POST['link']=substr($_POST['link'],13);
		else {
			$_POST['link']=false;
			echo '<h1>错误：地址输入有误。</h1>';
		}
		if($_POST['link']) {
			$success=true;
			request('http://pan.baidu.com');
			$share_page = request('http://pan.baidu.com'.$_POST['link']);
			if(strpos($share_page['real_url'],'/share/init?')!==false) {
				$success=false;
				$share_info=substr($share_page['real_url'],strpos($share_page['real_url'],'shareid'));
				$verify=request('http://pan.baidu.com/share/verify?'.$share_info.'&t='.(time()*1000).'&channel=chunlei&clienttype=0&web=1','pwd='.$_POST['code'].'&vcode=');
				$verify_ret=json_decode($verify['body']);
				if($verify_ret->errno==0) {
					$share_page=request('http://pan.baidu.com/share/link?'.$share_info);
					$success=true;
				}elseif($verify_ret->errno==-9) {
					echo '<h1>错误：提取码错误。</h1>';
				}elseif($verify_ret->errno==-62) {
					echo '<h1>错误：韩度要求输入验证码。</h1>';
					$need_vcode=true;
				}else{
					echo '<h1>未知错误：'.$verify_ret->errno.'</h1>';
				}
			} else $_POST['code']=0;
			if($success) {
				$fileinfo=json_decode(findBetween($share_page['body'],'var _context = ',';'),true);
				if($fileinfo==NULL) {
					echo '<h1>错误：找不到文件信息，可能韩度修改了页面结构，请联系作者！</h1>';
				}else{
					foreach($fileinfo['file_list']['list'] as &$v) {
						$v['fs_id']=number_format($v['fs_id'],0,'','');
					}
					$check_user = $database->get('users', '*', array('AND' => array('username' => $fileinfo['linkusername'], 'siteu_id' => $_SESSION['siteuser_id'])));
					if(empty($check_user)) {
						echo '<h1>错误：用户【'.$fileinfo['linkusername'].'】未添加进数据库！</h1>';
					} elseif (count($fileinfo['file_list']['list'])>1) {
						echo '<h1>错误：该分享有多个文件。当前暂未支持多文件补档……</h1>';
					} else {
						 if($check_user['newmd5']=='')
							echo '<font color="red"><b>因为没有设置MD5，无法启用换MD5补档模式。</b>请在“浏览文件”模式添加一个小文件（几字节即可），并在添加时输入提取码为“md5”。</font><br />';
						$check_file = $database->get('watchlist', '*', array('fid' => $fileinfo['file_list']['list'][0]['fs_id']));
						if(!empty($check_file)) {
							echo '<h1>错误：此文件已添加过，地址是：<a href="'. $jumper.$check_file['id'].'" target="_blank">'. $jumper.$check_file['id'].'</a></h1>';
						} else {
							$id = $database->insert('watchlist', array(
								'fid' => $fileinfo['file_list']['list'][0]['fs_id'],
								'name' => $fileinfo['file_list']['list'][0]['path'],
								'link' => $_POST['link'], 'count' => 0,
								'pass' => $_POST['code'], 'user_id' => $check_user['ID'],
								'siteu_id' => $_SESSION['siteuser_id']
								));
							//这里因为没读block_list需要的相关内容，暂时先不写入block_list，第一次访问会自动写入
							wlog('添加链接记录：用户名：'.$fileinfo['linkusername'].'，文件完整路径：'.$fileinfo['file_list']['list'][0]['path'].'，文件fs_id：'.$fileinfo['file_list']['list'][0]['fs_id'].'，文件访问地址为：'. $jumper.$id);
							?>
							<div class="alert alert-success">添加成功！<br />用户名：<?php echo $fileinfo['linkusername']; ?>
							<br />文件完整路径：<?php echo htmlspecialchars($fileinfo['file_list']['list'][0]['path']); ?>
							<br />文件fs_id：<?php echo $fileinfo['file_list']['list'][0]['fs_id']; ?>
							<br />文件访问地址为：<a href="<?php echo $jumper, $id; ?>" target="_blank"><?php echo $jumper, $id; ?></a></div>
							<?php
						}
					}
				}
			}
		}
	}
}
?>
<h1 class="page-header">添加要补档的文件</h1>
<form method="post" action="addlink.php">
请输入分享链接，分享必须由已添加的用户创建：
<input class="form-control" type="text" name="link" style="max-width: 330px;" />
要添加用户，请在主页中选择“浏览文件”，在出现的“选择用户”页面中添加。<br />
请输入提取码，公开分享不用输入：
<input class="form-control" type="text" name="code" style="max-width: 330px;" />
现在换MD5补档模式为全局启用状态，所有文件强制换MD5补档。请不要添加txt等在结尾连接内容后影响使用的格式！<br />
<?php
if(isset($need_vcode)) {
	echo '请输入验证码：<input class="form-control" type="text" name="verify" style="max-width: 330px;" />';
	$vcode=request("http://pan.baidu.com/share/captchaip?web=1&t=0&$share_info&channel=chunlei&clienttype=0&web=1");
	$vcode=json_decode($vcode['body']);
	if($vcode->errno)
		echo '获取验证码出现错误<br />';
	else
		echo '<img src="', $vcode->captcha, '" /><br />';
}
?>
<input class="btn btn-primary" type="submit" name="submit" value="添加" />
</form>
</body></html>