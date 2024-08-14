.PHONY: deploy help

help:
	echo "No help here!"

deploy:
	docker buildx build --platform linux/amd64 -f docker/Dockerfile -t magehand/engelsystem-bd:master .
	docker push magehand/engelsystem-bd:master
