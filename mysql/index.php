<?php 
header("Content-type:text/html;charset=utf-8");//字符编码设置 
//对应rustdesk 1.2.2以上版本
//原生的php操作mysql，
// mysqli_connect(host,username,password,dbname,port,socket);
//连接数据库，host为数据库服务器IP或域名,username为数据库用户名,password为数据库密码为数据库名,port数据库连接端口，默认3306,socket忽略即可
//下面这行必须改为自己的服务器上对应的信息，否则，你懂的。。。
$conn=mysqli_connect("localhost","rustdesk","AK8CaxzPKM7Diyy7","rustdesk",3306);
if(!$conn){ 
　　die("数据库连接出错了: " . mysqli_connect_error());//如果连接失败输出一条消息，并退出当前脚本
}
//获取访问的啥方法
$action = $_GET['s'];
$raw_post_data = file_get_contents('php://input');//这个就是提交过来的数据集合
$auth_token = $_SERVER['HTTP_AUTHORIZATION'] ;//这个访问时需要验证的token
if($auth_token){
    $_auth = explode(' ',$auth_token);
    $auth_token = $_auth[1];
}
$ac = $_GET['ac'];
if($ac=='add'){
    $username = $_GET['u'];
    $pwd = $_GET['p'];
    $sql = "select count(1) from rustdesk_users where username ='".$username."'";
    $ret = mysqli_query($conn,$sql);
    if(mysqli_num_rows($ret)==0){
        $pwd2 = md5($pwd.'rustdesk');
        $sql ="INSERT INTO rustdesk_users (username,password,create_time) VALUES ('".$username."','".$pwd2."',".time().");";
        mysqli_query($conn,$sql);
        print_r("添加用户". $username."成功~！");exit();
    }else{
        print_r('<span style="color:red">'.$username."已存在，无需重复添加。</span>");exit();
    } 
}
if($ac=='del'){
    $username = $_GET['u'];
    $pwd = $_GET['p'];
    $pwd2 = md5($pwd.'rustdesk');
    $sql = "select * from rustdesk_users where username='".$username."' and password='".$pwd2."'"; 
    $ret = mysqli_query($conn,$sql);
    //if($ret){ //这里有可能与mysql版本有关，以下为网友修改，本人未测试。
    if($ret = mysqli_fetch_assoc($ret)){     
	#删除用户对应的tag
	$sql = "delete from rustdesk_tags where uid=".$ret['id'];
	mysqli_query($conn,$sql);
	#删除用户对应的设备信息
	$sql = "delete from rustdesk_peers where uid=".$ret['id'];
	mysqli_query($conn,$sql);
        $sql ="delete  from rustdesk_users where username='".$username."'";
        mysqli_query($conn,$sql);
        print_r("删除用户". $username."成功~！");exit();
    }else{
        print_r('<span style="color:red">用户'.$username."不存在，或密码错误。</span>");exit();
    }
}
#登录
if($action =='/api/login'){
    //获取提交过来数据
    $data = json_decode($raw_post_data,true);
    //计算密码
    $pwd = md5($data['password'].'rustdesk');
    //sql语句
    $sql = "select *from rustdesk_users where `username` ='".$data['username']."' and `password` = '".$pwd."' and `delete_time`=0";
    //执行sql语句并取得记录集
    $result=mysqli_query($conn,$sql);
    //获取一条记录
    $info = mysqli_fetch_assoc($result);
    //有记录就是密码用户名是对的，反之不正确
    //定义返回信息
    $res = array();
    //判断是否有记录，有则正确，否则无记录
    if($info){
        $time = time();
        //加密因子
        $key = 'rustdesk'.strtotime('now');
        //这是通讯验证串
        $token = md5($key);
        $res = array(
			'type' => 'access_token',
            'access_token' => $token,
            'user' => array('name'=> $info['username'])
        );
        $sql = "insert into `rustdesk_token` (`username`, `uid`,`id`, `uuid`, `access_token`, `login_time`) VALUES ('".$info['username']."', ".$info['id'].",'".$data['id']."','".$data['uuid']."', '".$token."', ".$time.") ON DUPLICATE KEY UPDATE `access_token` = values(`access_token`)";
        mysqli_query($conn,$sql);
    }else {
        //数据库里查不到数据
        $res = array('error'=>'用户名或密码错误');
    }
    
    //返回json数据
    $json_string = json_encode($res);
    //出现中文乱码是用下面这行
    //$json_string = json_encode($res,JSON_UNESCAPED_UNICODE);
    //输出结果json字符串
    echo $json_string;
}

#获取当前登录用户的所有信息
if($action =='/api/currentUser'){
    //获取提交过来数据
    $data = json_decode($raw_post_data,true);
    //去数据库查此用户的sql语句
    $sql = "select * from `rustdesk_token` where `id` = '".$data['id']."' and `uuid` = '".$data['uuid']."' and `access_token` = '".$auth_token."'";
    //print_r($sql);exit();
    //执行sql语句并取得记录集
    $result=mysqli_query($conn,$sql);
    //获取一条记录
    $info = mysqli_fetch_assoc($result);
    //有记录就是等成功的用户
    //定义返回信息
    $res = array();
    //判断是否有记录，有则正确，否则无记录
    if($info){
        $res = array('name'=> $info['username']);
    }else {
        //数据库里查不到数据
        $res = array('error'=>'查无此人或登录超时了');
    }
    //返回json数据
    $json_string = json_encode($res);
    //出现中文乱码是用下面这行
    //$json_string = json_encode($res,JSON_UNESCAPED_UNICODE);
    //输出结果json字符串
    echo $json_string;
}
//更新地址簿，一下子更新所有的信息，包括标签、地址簿等
//1.2.2以上用POST更新地址簿，GET获取地址簿
if($action =='/api/ab'){
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		//获取提交过来数据
		$p_data =json_decode($raw_post_data)->data;
		//解析json里的字段
		$tp_data = json_decode($p_data);
		//解析出标签json
		$tags = $tp_data->tags;
		//解析出设备列表json
		$peers = $tp_data->peers;
		//根据用户登录的token去数据库Rustdesk_Token查询登录的用户相关信息的SQL语句
		$sql ="select * from `rustdesk_token` where `access_token` = '".$auth_token."'";
		//执行sql语句
		$result=mysqli_query($conn,$sql);
		//获取一条记录
		$info = mysqli_fetch_assoc($result);
		//标签集合(因为是原生的，只能拼接字符串了，大家凑合着用吧)
		$tags_data = [];
		foreach ($tags as $k => $v) { 
			$item = [];
			$item['uid'] = $info['uid'];
			$item['tag'] = $v;
			$tags_data[] =  $item ;
		}		
		//如果tag集合不为空，则删除原来现有的记录
		if($tags_data){
			//删除数据sql
			$del_tag_sql = "delete from rustdesk_tags where `uid` = ".$info['uid'];
			//执行删除
			mysqli_query($conn,$del_tag_sql);
			//插入新的数据集合,拼接批量插入sql语句 
			$insert_tag_sql = "insert into `rustdesk_tags` (`uid`,`tag`) VALUES ";
			foreach($tags_data as $item) {
			  $itemStr = '( ';
			  $itemStr .= sprintf("%d, '%s'",(int)$item['uid'],$item['tag']);
			  $itemStr .= '),';
			  $insert_tag_sql .= $itemStr;
			}
			 // 去除最后一个逗号，并且加上结束分号
			$insert_tag_sql = rtrim($insert_tag_sql, ',');
			$insert_tag_sql .= ';';
			//print_r($insert_tag_sql);exit();
			//执行插入数据
			mysqli_query($conn,$insert_tag_sql); 
		}
		//设备集合（地址簿）
		$peers_data = [];
		foreach ($peers as $k => $v) {
			$item = [];
			$item['uid'] = $info['uid'];
			$item['id'] = $v->id;
			$item['username'] = $v->username ;
			$item['hostname'] = $v->hostname ;
			$item['alias'] = $v->alias ;
			$item['platform'] = $v->platform ;
			if($v->tags){
			  $item['tags'] = implode(',',$v->tags);
			}else{
				$item['tags'] = '';
			}
			$item['hash'] = $v->hash;
			$peers_data[] = $item;
		}
		//如果peers集合不为空，则删除原来现有的记录
		if($peers_data){
			//删除数据sql
			$del_peers_sql = "delete from `rustdesk_peers` where `uid` = ".$info['uid'];
			//执行删除
			mysqli_query($conn,$del_peers_sql);
			//插入新的数据集合,拼接批量插入sql语句
			$insert_peers_sql = "insert into `rustdesk_peers` (`uid`, `id`, `username`, `hostname`, `alias`, `platform`, `tags`,`hash`) VALUES ";
			foreach($peers_data as $item) {
			  $itemStr = '( ';
			  $itemStr .= sprintf("%d, '%s','%s','%s','%s','%s','%s','%s'",(int)$item['uid'],$item['id'],$item['username'],$item['hostname'],$item['alias'],$item['platform'],$item['tags'],$item['hash']);
			  $itemStr .= '),';
			  $insert_peers_sql .= $itemStr;
			}
			// 去除最后一个逗号，并且加上结束分号
			$insert_peers_sql = rtrim($insert_peers_sql, ',');
			$insert_peers_sql .= ';';
			//执行插入数据
			mysqli_query($conn,$insert_peers_sql);
		}
	}else{
		//获取已登录的用户信息
		//去数据库查此用户的sql语句，主要是获取用户ID
		$sql = "select * from `rustdesk_token` where `access_token` = '".$auth_token."'";
		//执行sql语句并取得记录集
		$result=mysqli_query($conn,$sql);
		//获取一条登录的记录
		$info = mysqli_fetch_assoc($result);
		//print_r($info);exit();
		//分别去`rustdesk_tags`和`rustdesk_peers`表里取当前用户的所有信息，全部一次性返回到客户端就完事了
		//定义一个临时数组来存放这里两个表取出来的数据
		$_address_book = array();
		if($info){
		  //开始获取rustdesk_tags数据
		  $sql = "select `tag` from `rustdesk_tags` where `uid` = ".$info['uid'];
		  //执行sql语句并取得记录集
		  $o_tag=mysqli_query($conn,$sql);
		  $_tags = [];
		  while($row = mysqli_fetch_array($o_tag)){
			  $_tags[] = $row['tag'];
		  }
		  //开始获取rustdesk_peers数据
		  $sql = "select id,username,hostname,alias,platform,tags,hash from `rustdesk_peers` where `uid` = ".$info['uid'];
		  //执行sql语句并取得记录集
		  $o_peer=mysqli_query($conn,$sql);
		  $_peers =[];
		  while($row = mysqli_fetch_array($o_peer)){
			  $item=[];
			  $item['id'] = $row['id'];
			  $item['username'] = $row['username'];
			  $item['hostname'] = $row['hostname'];
			  $item['alias'] = $row['alias'];
			  $item['platform'] = $row['platform'];
			  $item['tags'] = explode(',',$row['tags']);
			  $item['hash'] = $row['hash'];
			  $_peers[] = $item;
		  }
		  $_address_book = array( 
			  'updated_at' => date('Y-m-d H:i:s', time()),
			  'data' => json_encode(array("tags"=>$_tags,"peers"=>$_peers))
		  );
		}else{
		  $_address_book = array("error" => "获取地址簿有误");
		}
		//转成json数据
		$address_book = json_encode($_address_book);
		//输出结果json字符串
		echo $address_book;
	}
} 
//心跳检测，基本用不到，不用关注
if($action =='/api/audit'){
    
    $_res = array(
        'data' =>'在线！！！'
    );  
    $result = json_encode($_res);
    echo $result;
}
//心跳检测，基本用不到，不用关注, audit 这个方法好像废弃了
if($action =='/api/heartbeat'){    
    $_res = array(
        'data' =>'在线！！！'
    );  
    $result = json_encode($_res);
    echo $result;
}
//这个暂时不知道有何用
if($action =='/api/login-options'){
	echo 'ok';
}
//这个是把设备信息提交到服务器，这个想用的自己加个表吧
if($action =='/api/sysinfo'){
	/* 提交的数据 
	{
   "cpu" : "Intel(R) Core(TM) i7-10700 CPU @ 2.90GHz, 2.84GHz, 16/8 cores",
   "hostname" : "win-10",
   "id" : "161****965",
   "memory" : "15.93GB",
   "os" : "windows / Windows 10 Pro for Workstations - 10 (19044)",
   "uuid" : "NjU1ZTk3MjUtYTQ3Mi00N****MWQ3YWZhNjY1N2Jj",
   "version" : "1.2.2"
	} 
	*/
	echo 'ok';
}
//退出登录，删除rustdesk_token数据库信息
if($action =='/api/logout'){
    //获取提交过来数据
    $data = json_decode($raw_post_data,true);
    //删除rustdesk_token表中记录
    $sql = "delete from `rustdesk_token` where `id` = '".$data['id']."' and `uuid` = '".$data['uuid']."' and `access_token` = '".$auth_token."'";
    //执行sql语句
    $result=mysqli_query($conn,$sql);
    //返回退出消息,反正客户端看不见，返回啥是啥
    $_logout = array(
        'data' =>date('Y-m-d H:i', time())
    );
    $logout = json_encode($_logout);
    echo $logout;
}
//关闭数据库连接
mysqli_close($conn);
?>
