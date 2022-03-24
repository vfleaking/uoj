FROM ubuntu:20.04
EXPOSE 80 3690
ENV LANG=C.UTF-8 TZ=Asia/Shanghai

COPY docker/sources.list /etc/apt/sources.list
COPY web /opt/uoj/web
COPY judger /root/judge_client
COPY docker/new_problem.sh \
	docker/post-commit.sh \
	docker/judge-repo-post-commit.sh \
	docker/uoj-passwd \
	docker/gen-uoj-config.php \
	docker/app_uoj233.sql \
	docker/prepare \
	docker/setup \
	docker/composer-setup.sh \
	/root/

RUN cd /root && chmod +x prepare && ./prepare
RUN cd /root && ./setup

COPY docker/up /root/up
RUN chmod +x /root/up

VOLUME [ "/var/lib/mysql", "/var/uoj_data", "/var/uoj_data_copy", "/opt/uoj/web", "/opt/uoj/judger", "/var/svn", "/var/log" ]

CMD /root/up
