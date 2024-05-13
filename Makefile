build-inspect:
	docker build -t harbor.dobest.com/inspect-service/swoole:v1 -f Dockerfile .
	docker push harbor.dobest.com/inspect-service/swoole:v1