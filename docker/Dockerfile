FROM vfleaking/uoj
MAINTAINER vfleaking vfleaking@163.com
COPY docker/sources.list /etc/apt/sources.list
COPY uoj/1 /root/uoj_1
COPY judge_client/1 /root/judge_client_1

COPY docker/new_problem.sh \
	docker/post-commit.sh \
	docker/uoj-passwd \
	docker/uoj-post-commit \
	docker/gen-uoj-config.php \
	docker/app_uoj233.sql \
	/root/

COPY docker/jdk1.7.0_76 \
	docker/jdk1.8.0_31 \
	/home/local_main_judger/

COPY docker/install /root/install

RUN cd /root && php gen-uoj-config.php && chmod +x install
RUN cd /root && ./install && rm * -rf

COPY docker/up /root/up

RUN chmod +x /root/up

EXPOSE 80 3690

CMD /root/up
