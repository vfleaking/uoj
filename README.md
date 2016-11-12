# Universal Online Judge

## Dependence
This is a dockerized version of UOJ. Before installation, please make sure that [Docker](https://www.docker.com/) has already been installed on your OS.

The docker image of UOJ is **64-bit**, so a **32-bit** host OS may cause installation failure.

## Installation
First please download [JDK7u76](http://www.oracle.com/technetwork/java/javase/downloads/java-archive-downloads-javase7-521261.html#jdk-7u76-oth-JPR) and [JDK8u31](http://www.oracle.com/technetwork/java/javase/downloads/java-archive-javase8-2177648.html#jdk-8u31-oth-JPR), and put them to `docker/jdk-7u76-linux-x64.tar.gz` and `docker/jdk-8u31-linux-x64.tar.gz`. These two compressed files are used by judge\_client for judging Java program. If you are too lazy to download these two huge files, you can simply place two empty .tar.gz files there.

Next, you can run the following command in your terminal: (not the one in the `docker/` directory!)
```sh
./install
```
If everything goes well, you will see `Successfully built <image-id>` in the last line of the output.

To start your UOJ main server, please run:
```sh
docker run -it -p 80:80 -p 3690:3690 <image-id>
```
If you are using docker on Mac OS or having 'std: compile error. no comment' message on uploading problem data, you could possibly use this alternative command:
```sh
docker run -it -p 80:80 -p 3690:3690 --cap-add SYS_PTRACE <image-id>
```

The default hostname of UOJ is `local_uoj.ac`, so you need to modify your host file in your OS in order to map `127.0.0.1` to `local_uoj.ac`. (It is `/etc/hosts` on Linux.) After that, you can access UOJ in your web browser.

The first user registered after the installation of UOJ will be a super user. If you need another super user, please register a user and change its `usergroup` to "<samp>S</samp>" in the table `user_info`. Run
```sh
mysql app_uoj233 -u root -p
```
to login mysql in the terminal.

Notice that if you want only one judge client, then everything is ok now. Cheers!

However, if you want more judge clients, you need to set up them one by one. First run:
```sh
./config_judge_client
```
and answer the questions.

* uoj container id: the container id of the main server.
* uoj ip: the ip address of the main server.
* judger name: you can take a name you like, such as judger, judger\_2, very\_strong\_judger. (containing special characters may cause  unforeseeable consequence.)

After that, a sql command is given, we will talk about it later.

Next, we need to run:
```sh
./install_judge_client
```
to build the docker image. If you want to run judger at the same server, you just need to run
```sh
docker run -it <image-id>
```
And, you need to complete the sql command given just now with the ip address of the judger docker, and modify the database. To someone who do not know how to get the ip address of a docker container, here is the answer:
```sh
docker inspect --format '{{ .NetworkSettings.IPAddress }}' <container-id>
```

Or, if you want to run judger at different server, you need to copy the image to the other server, and run
```sh
docker run -p 2333 -it <image-id>
```
Similarly, you need to complete the sql command and modify the database. This time, you need to fill with the ip address of the host machine of the judger docker.

You may meet many difficulties during the installation. Good luck and have fun!

## Notes

mysql default password: root

local\_main\_judger password: judger

You can change the default hostname and something else in `/var/www/uoj/app/.config.php`. However, not all the config is here, haha.

## More Documentation
As you know, my Yingyu is not very hao. Suoyi only the README file is En(Chi)nglish for internationalization.

More documentation is here: [https://vfleaking.github.io/uoj/](https://vfleaking.github.io/uoj/)

## License
MIT License.
