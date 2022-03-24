# 维护

## 一、docker 基本操作

每次想要启动 UOJ 容器，只要像安装时一样输入下列命令即可：
```bash
sudo docker-compose up
```
不过你可能已经注意到了，如果是 `docker-compose up` 这样启动 UOJ，可能手一抖，按个 ++ctrl+c++，就被掐掉了。所以你可以选择用如下命令在后台启动 UOJ：
```bash
sudo docker-compose up -d
```
当你想关掉 UOJ 的时候，可以使用 `up` 的反义词 `down`：
```bash
sudo docker-compose down
```
如果 UOJ 出 bug 了，想切进容器看看，可以使用如下命令（这里假设你的容器名字是 `uoj_all`，并且已经通过 docker compose 启动了）：
```bash
sudo docker exec -it uoj_all /bin/bash
```
更多 docker 相关操作请查阅 docker 的文档。UOJ 容器还使用了**数据卷**（volume）来存储数据，学习下 docker 中数据卷的使用方法可能会有助于你了解如何定期进行备份。

下面是一些比较重要的文件或文件夹，可能有助于你 debug：

* Apache 的 log 目录：`/var/log/apache2/`
* MySQL 的 log 目录：`/var/log/mysql/`
* 测评机的 log 目录：`/opt/uoj/judger/judge_client/1/log/`
* 测评机测评的上一个记录的相关信息：`/tmp/main_judger/<core-id>/`

## 二、手动启动或停止网页端

启动网页端：

```bash
service mysql start
service apache2 start
```

停止网页端：

```bash
service mysql stop
service apache2 stop
```

## 三、手动启动或停止测评端

首先，运行如下命令切换到 `local_main_judger` 用户：

```bash
su local_main_judger
```

启动测评端：

```bash
~/judge_client/judge_client start
```

停止测评端：

```bash
~/judge_client/judge_client stop
```

如果卡住了说明测评端正在测评一个提交记录，测评完成后才会退出。

## 四、更新方法

当 github 上有更新的时候，如何更新自己本地的版本呢？

据我们观察，大家使用 UOJ 通常都会进行一番魔改。如果你已经魔改过了，那么一般来说很难直接更新。

但假设条件允许你直接更新的话，这里要分情况讨论。请先阅读[开发教程](dev.md)了解 UOJ 的架构设计。

* 如果是网页端代码更新，你可以直接进 UOJ 容器更新网页端代码。
* 如果是测评端代码更新，你需要使用 svn 仓库更新测评端代码。
* 如果有一些数据库、文件系统之类的更新，并且是以 Upgrader 的形式打包的，那么你只需要把它放在 `/opt/uoj/web/app/upgrade` 目录下，然后 `/opt/uoj/web/app` 下执行 `php cli.php upgrade:latest`。
* 如果没有以 Upgrader 的形式打包，请仔细看清更新的具体内容，相应地进行修改。
