# rustdesk-api
rustdesk远控软件自建API服务器，rustdesk地址薄服务接口，自己架设后，可以方便的管理自己设备ID以及密码

感谢[rustdesk](https://github.com/rustdesk/rustdesk/releases/) 提供这么好的软件，更多详情请关注[rustdesk](https://github.com/rustdesk)。

# 优点：
- 不用记哪些烦人的ID了
- 新增，可以一键登录，设备连接密码也存到服务器上了
- 可以看设备状态

# 食用方法
1. 在php环境的服务器上新增一个网站。
2. 把数据库对应的版本的php文件拷贝到根目录
3. 在客户端ID/中继服务器里设置API服务器为：http://你到域名或IP:端口/index.php?s=
   ```
    如：http://192.168.0.1/index.php?s=
   ```
![设置](https://raw.githubusercontent.com/v5star/rustdesk-api/main/Snapshots/20230826155800.png)
![设置](https://raw.githubusercontent.com/v5star/rustdesk-api/main/Snapshots/20230826163000.png)


# 关于用户名和密码的生成：
1. 去各大搜索引擎里搜索【MD5】，随便点一个进去，找加密的地方，输入 你要的设置的密码，进行加密。
2. 密码规则：密码+rustdesk，如：你要设置admin为密码，那么你要在上面加密的输入框里输入：adminrustdesk ，结果都是32位的，大致像这样：``` d3541a8746eb583a010c1157438a7ba1 ```
3. 生成密码后用phpmyadmin在rustdesk_users表里照葫芦画瓢加一条记录。

# 客户端下载
   https://github.com/rustdesk/rustdesk/releases/

## 致谢
- [rustdesk](https://github.com/rustdesk)
