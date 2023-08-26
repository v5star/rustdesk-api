<?
//前奏知识点：
//php操作SQLite3
//https://www.php.net/manual/zh/book.sqlite3.php
//对文本数据库执行: CREATE,INSERT, UPDATE, DELETE 操作用SQLite3::exec()
//执行一个对文本数据库的查询用 SQLite3::query()
//执行一个查询并返回一个结果SQLite3::querySingle(string $query, bool $entireRow = false) $entireRow默认false,即首行首列，为true,则返回首行
//返回操作sqlite 时得到的关联或数字索引数组的结果行SQLite3Result::fetchArray() 结合SQLite3::query()
//关闭数据库连接SQLite3::close()

//指定一个数据库文件名，因db文件是可以下载的，所以前面加了个#，就可以防止下载了。
$db_name = "#rustdesk.db";
//声明一个sqlite3 文本数据库对像
$db = new SQLite3($db_name);
//判断是否可用
if(!$db){
    print_r('SQLite3有错误'.$db->lastErrorMsg());
}
//先建表
$sql =<<<EOF
CREATE TABLE IF NOT EXISTS rustdesk_peers 
      (deviceid INTEGER PRIMARY KEY AUTOINCREMENT,
       uid INTEGER NOT NULL DEFAULT 0, 
       id TEXT NOT NULL, 
       username TEXT NULL,
       hostname TEXT NULL,
       alias TEXT NULL,
       platform TEXT NULL,
       tags TEXT NULL,
	   hash TEXT NULL
      );
CREATE TABLE IF NOT EXISTS rustdesk_tags 
      (id INTEGER PRIMARY KEY AUTOINCREMENT,
       uid INTEGER NOT NULL,
       tag TEXT NOT NULL
      );
CREATE TABLE IF NOT EXISTS rustdesk_token 
      (access_token TEXT NOT NULL,
       username TEXT NOT NULL, 
       uid INTEGER NOT NULL DEFAULT 0, 
       id TEXT NOT NULL, 
       uuid TEXT NULL,
       login_time INTEGER NOT NULL DEFAULT 0,
       expire_time INTEGER NOT NULL DEFAULT 0
      );
CREATE TABLE IF NOT EXISTS rustdesk_users 
      (id INTEGER PRIMARY KEY AUTOINCREMENT,
       username TEXT NOT NULL,
       password TEXT NOT NULL,
       create_time INTEGER NOT NULL DEFAULT 0,
       delete_time INTEGER NOT NULL DEFAULT 0
      ); 
EOF;
//首先访问一次这个文件，如：htpp://www.xxx.com/index.php?ac=runonce
$ac = $_GET['ac'];
if($ac=='runonce'){
    $ret = $db->exec($sql);
    if($ret) {
        $sql = "select count(1) from rustdesk_users";
        $ret = $db->querySingle($sql);
        if($ret==0){
            $sql ="INSERT INTO rustdesk_users (username,password) VALUES ('admin','d3541a8746eb583a010c1157438a7ba1');";
            $db->exec($sql);
        }else{
            print_r('<span style="color:red">你已经运行过一次了，如需重新创建,请先删除'.$db_name."后再访问。</span>");exit();
        }
    }else{
        print_r("创建数据表有误：". $db->lastErrorMsg());exit();
    }
    print_r('完美的创建并初始化数据库，你可以愉快的玩耍Rustdesk了');exit();
}
//获取访问的啥方法
$action = $_GET['s'];
$raw_post_data = file_get_contents('php://input');//这个就是提交过来的数据集合
$auth_token = $_SERVER['HTTP_AUTHORIZATION'] ;//这个访问时需要验证的token
if($auth_token){
    $_auth = explode(' ',$auth_token);
    $auth_token = $_auth[1];
}

#登录
if($action =='/api/login'){
    //获取提交过来数据
    $data = json_decode($raw_post_data,true);
    //计算密码
    $pwd = md5($data['password'].'rustdesk');
    //sql语句
    $sql = "select * from rustdesk_users where username ='".$data['username']."' and password = '".$pwd."' and delete_time=0";
    //执行sql语句并取得记录集
    $info = $db->querySingle($sql,true);

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
        $sql = "insert into rustdesk_token (username, uid,id, uuid, access_token, login_time) VALUES ('".$info['username']."', ".$info['id'].",'".$data['id']."','".$data['uuid']."', '".$token."', ".$time.")";
        $db->exec($sql);
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
    $sql = "select * from rustdesk_token where id = '".$data['id']."' and uuid = '".$data['uuid']."' and access_token = '".$auth_token."'";
    //执行sql语句并取得一条记录
    $info = $db->querySingle($sql,true);
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
//POST更新地址簿，GET获取地址簿
if ($action == '/api/ab') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //获取提交过来数据
        $p_data = json_decode($raw_post_data)->data;
        //解析json里的字段
        $tp_data = json_decode($p_data);
        //解析出标签json
        $tags = $tp_data->tags;
        //解析出设备列表json
        $peers = $tp_data->peers;
        //根据用户登录的token去数据库Rustdesk_Token查询登录的用户相关信息的SQL语句
        $sql = "select * from rustdesk_token where access_token = '" . $auth_token . "'";
        //执行sql语句并获取一条记录
        $info = $db->querySingle($sql, true);
        //标签集合(因为是原生的，只能拼接字符串了，大家凑合着用吧)
        if ($tags) {
            $del_tag_sql = "delete from rustdesk_tags where uid = " . $info['uid'];
            $db->exec($del_tag_sql);
            //高效方式
            $insert_tag_sql = "insert into rustdesk_tags (`uid`,`tag`)";
            foreach ($tags as $k => $v) {
                if ($k == 0) {
                    $insert_tag_sql .= " SELECT " . $info['uid'] . " AS 'uid', '" . $v . "' AS 'tag' ";
                } else {
                    $insert_tag_sql .= " UNION SELECT " . $info['uid'] . ", '" . $v . "' ";
                }
            }
            $db->exec($insert_tag_sql);
        }
        //设备集合（地址簿）
        if ($peers) {
            $del_peers_sql = "delete from rustdesk_peers where uid = " . $info['uid'];
            $db->exec($del_peers_sql);
            //这里可以自己写一个对比的方法也行
            $insert_peers_sql = "insert into rustdesk_peers (uid, id, username, hostname, alias, platform, tags) ";
            foreach ($peers as $k => $v) {
                $_tag = '';
                if ($v->tags) {
                    $_tag = implode(',', $v->tags);
                }
                if ($k == 0) {
                    $insert_peers_sql .= " SELECT " . $info['uid'] . " AS 'uid', '" . $v->id . "' AS 'id','" . $v->username . "' as  'username', '" . $v->hostname . "' as 'hostname', '" . $v->alias . "' as 'alias', '" . $v->platform . "' as 'platform', '" . $_tag . "' as 'tags' ";
                } else {
                    $insert_peers_sql .= " UNION SELECT " . $info['uid'] . ", '" . $v->id . "','" . $v->username . "' as 'username','" . $v->hostname . "' as 'hostname','" . $v->alias . "' as 'alias','" . $v->platform . "' as 'platform','" . $_tag . "' as 'tags'";
                }
            }
            $db->exec($insert_peers_sql);
        }
    } else {
        //获取已登录的用户信息
        //去数据库查此用户的sql语句，主要是获取用户ID
        $sql = "select * from rustdesk_token where access_token = '" . $auth_token . "'";
        //执行sql语句并获取一条登录的记录
        $info = $db->querySingle($sql, true);
        //print_r($info);exit();
        //分别去`rustdesk_tags`和`rustdesk_peers`表里取当前用户的所有信息，全部一次性返回到客户端就完事了
        //定义一个临时数组来存放这里两个表取出来的数据
        $_address_book = array();
        if ($info) {
            //开始获取rustdesk_tags数据
            $sql = "select tag from rustdesk_tags where uid = " . $info['uid'];
            //执行sql语句并取得记录集
            $res = $db->query($sql);
            $_tags = [];
            while ($row = $res->fetchArray()) {
                $_tags[] = $row['tag'];
            }
            //开始获取rustdesk_peers数据
            $sql = "select * from rustdesk_peers where uid = " . $info['uid'];
            //执行sql语句并取得记录集
            $res = $db->query($sql);
            $_peers = [];
            while ($row = $res->fetchArray()) {
                $item = [];
                $item['id'] = $row['id'];
                $item['username'] = $row['username'];
                $item['hostname'] = $row['hostname'];
                $item['alias'] = $row['alias'];
                $item['platform'] = $row['platform'];
				$item['hash'] = $row['hash'];
                $item['tags'] = explode(',', $row['tags']);
                $_peers[] = $item;
            }
            $_address_book = array(
                'error' => false,
                'updated_at' => date('Y-m-d H:i:s', time()),
                'data' => json_encode(array("tags" => $_tags, "peers" => $_peers))
            );
        } else {
            $_address_book = array("error" => "获取地址簿有误");
        }
        //转成json数据
        $address_book = json_encode($_address_book);
        //输出结果json字符串
        echo $address_book;

    }
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
    $sql = "delete from rustdesk_token where id = '".$data['id']."' and uuid = '".$data['uuid']."' and access_token = '".$auth_token."'";
    //执行sql语句
    $result = $db->exec($sql);
    //返回退出消息,反正客户端看不见，返回啥是啥
    $_logout = array(
        'data' =>date('Y-m-d H:i', time())
    );
    $logout = json_encode($_logout);
    echo $logout;
}
//关闭数据连接
$db->close();
?>