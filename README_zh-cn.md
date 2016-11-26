# 系统要求
这是一个UOJ的docker版本。在安装之前，请确认[Docker](https://www.docker.com/)已经安装在您的操作系统中。    
这个docker的映像是64位的版本，在32位的系统上安装可能会出现错误。

# 安装过程

## 安装
请先下载 [JDK7u76](http://www.oracle.com/technetwork/java/javase/downloads/java-archive-downloads-javase7-521261.html#jdk-7u76-oth-JPR) 和 [JDK8u31](http://www.oracle.com/technetwork/java/javase/downloads/java-archive-javase8-2177648.html#jdk-8u31-oth-JPR)，并把他们放置在`docker/jdk-7u76-linux-x64.tar.gz`与`docker/jdk-8u31-linux-x64.tar.gz`中。 这两个压缩文件会被用在 judge\_client 来测试Java的程序。如果你不喜欢下载那么大的文件的话，你可以放两个空vfk在那里充数(前提是文件名一样)。

然后，回到clone的目录(也就是docker的上级目录)运行以下指令
```
./install
```
如果运气够好，而且vfk大的话，你将会看见`Successfully built <image-id>`在最后一行.

## 运行
如果你要启动UOJ的主服务器，你需要运行如下指令
```
docker run -it -p 80:80 -p 3690:3690 <image-id>
```
如果你运行docker在Mac OS上，或者在上传题目数据的时候遇到了形如:`std: compile error. no comment`的信息，你可能需要用以下指令来替代上一个指令:
```
docker run -it -p 80:80 -p 3690:3690 --privileged --cap-add SYS_PTRACE <image-id>
```

## 小配置
默认的主机名是`local_uoj.ac`，所以你需要在您服务器的hosts文件中添加以下行:
```
127.0.0.1 local_uoj.ac
::1 local_uoj.ac
```
或使用Menci的玄学大法(replace from XXX to YYY)
```
find . | xargs sed -i 's/XXX/YYY/g'
```
当你做完了这些 您就可以直接从浏览器访问您自己的uoj辣！

## 超级用户
如果你需要一个超级用户，请注册一个账户，并在table`user_info`将其的`usergroup`改成"<samp>S</samp>"。
请运行
```
mysql app_uoj233 -u root -p
```
来登录您服务器的mysql(默认密码是root)


**如果您只需要一个判题的客户端，那您已经完成了这个事情！vfk向您发来贺电！恭喜！**

## 安装其他判题客户端

如果你想要安装其他判题的客户端，你需要逐一设置他们。    
首先运行:
```
./config_judge_client
```
然后回答vfk提出的问题
* uoj container id: 主服务器的容器id。
* uoj ip: 主服务器的ip地址
* judger name: 你可以取一个能让你开心(喜欢)的名字,例如 judger, judger\_2, very\_strong\_judger 。但是，包含特殊字符可能会导致vfk不可预料的后果。
    
做完了这些之后，程序会给你一个sql指令，我们稍后再讨论这个指令有什么作用。

下一步，我们需要运行
```
./install_judge_client
```
来构建一个docker镜像。如果你想要运行判题客户端在同一台服务器上，你只要运行以下命令即可
```
docker run -it <image-id>
```    
你需要用的判题客户端docker镜像的ip地址来完成刚给出的sql命令以及数据库。    
对于那些不知道怎么获得docker容器的ip地址的人，这是解决方法:
```
docker inspect --format '{{ .NetworkSettings.IPAddress }}' <container-id>
```

或者，你想要运行判题客户端在另一台服务器上，你需要复制判题客户端的docker镜像到另一台服务器，接着运行:
```
docker run -p 2333 -it <image-id>
```
同样的，你需要完成sql命令来修改数据库。在这个时候，你需要填写运行判题客户端的镜像主机的IP地址。


# 参考数据

mysql默认密码: root

local\_main\_judger 密码: judger

你可以在`/var/www/uoj/app/.config.php`改变默认主机名或者其他选项，但是并不是所有的配置选项都在这个文件中。

# 更多文档:

[点我](https://vfleaking.github.io/uoj/)

# 许可协议

MIT License.
