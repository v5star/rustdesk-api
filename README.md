# rustdesk-api
rustdesk远控软件自建API服务器，rustdesk地址薄服务接口，自己架设后，可以方便的管理自己设备ID以及密码

感谢[rustdesk](https://github.com/rustdesk/rustdesk/releases/) 提供这么好的软件，更多详情请关注[rustdesk](https://github.com/rustdesk)。

# 优点：
- 不用记哪些烦人的ID了
- 新增，可以一键登录，设备连接密码也存到服务器上了
- 可以看设备状态

# 食用方法
1. 在php环境的服务器上新增一个网站。
2. 把数据库对应的版本的php文件拷贝到根目录。如：SQLite版的在``` sqlite ```文件夹下
3. 在客户端ID/中继服务器里设置API服务器为：http://你到域名或IP:端口/index.php?s=
   ```
    如：http://192.168.0.1/index.php?s=
   ```
4. 首次运行先访问http://你到域名或IP:端口/index.php?ac=runonce 创建数据库以及用户名密码。
   ```
   如：htpp://www.xxx.com/index.php?ac=runonce
   ```
6. 默认的登录用户名和密码都是：``` admin ```
   
![设置](./Snapshots/20230826163152.png)
![首页](./Snapshots/index.png)
![登录](./Snapshots/login.png)
![地址簿](./Snapshots/20230826163000.png)




# 关于用户名和密码的生成：
1. 去各大搜索引擎里搜索【MD5】，随便点一个进去，找加密的地方，输入 你要的设置的密码，进行加密。
2. 密码规则：密码+rustdesk，如：你要设置admin为密码，那么你要在上面加密的输入框里输入：adminrustdesk ，结果都是32位的，大致像这样：``` d3541a8746eb583a010c1157438a7ba1 ```
3. 生成密码后用phpmyadmin在rustdesk_users表里照葫芦画瓢加一条记录。

# 已知BUG
- 网络里设置KEY (id_ed25519.pub),即填写那个公钥字符串后，连接远程设备，需要等很长时间，不填这个串，就秒连了，起作用就是就是加密连接，不填会显示一个红叉，不影响使用。（上面第一张图里的Key那里空着就行）

# 客户端下载
   https://github.com/rustdesk/rustdesk/releases/

## 致谢
- [rustdesk](https://github.com/rustdesk)
