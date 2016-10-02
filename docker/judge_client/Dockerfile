FROM ubuntu:14.04
MAINTAINER vfleaking vfleaking@163.com

COPY docker/sources.list /etc/apt/sources.list

RUN apt-get update
RUN apt-get install -y vim \
	ntp \
	build-essential \
	python \
	python-requests \
	subversion \
	unzip

COPY docker/jdk-7u76-linux-x64.tar.gz \
	docker/jdk-8u31-linux-x64.tar.gz \
	docker/judge_client/conf.json \
	/root/
COPY docker/judge_client/cur_install /root/install

RUN cd /root && chmod +x install
RUN cd /root && ./install && rm * -rf

COPY docker/judge_client/up /root/up

RUN chmod +x /root/up

EXPOSE 2333

CMD /root/up
