# 维护

## 数据可持久化

（我并不是在说函数式线段树233）

有同学表示重启后 docker 丢失了数据。在这里要科普下 docker 的 image 和 container 的概念。

`run` 命令的功能是读一个 image 并运行，跑起来的 image 是一名光荣的 container，跑完的 container 还是一名光荣的 container。

所以当你关掉了你的 container 之后，使用 `run` 命令试图让它复活是不对的。。。

首先，你需要
```sh
docker ps -a
```
来查看所有正在运行或运行结束的 container，找到你刚才掐掉的那个（通常就是第一个），然后复制下它的 container id。然后：
```sh
docker restart <container-id>
```
这家伙就在后台默默跑起来了！如果你想让它回到前台，请用
```sh
docker attach <container-id>
```
当当！复活了！你以为丢失了的数据都回来了！（container 其实可以起名的，这种进阶用法自己探索吧233）

## 更新

当 github 上有更新的时候，如何更新自己本地的版本呢？

求看眼[开发](/dev/)最前面几行关于使用 svn 仓库的。

嗯好，假设你已经看完了。如果是 uoj 和 judge_client 的更新，那么你可以 svn checkout 一下，然后把 git 里的新版本 commit 上去……不过你以前 checkout 过的话就可以留着那个文件夹，下次就不用重新 checkout 了。

如果有什么数据库上的更新……再说吧，这部分我还没设计，感觉。。。到时候给个自动的小脚本好了。
