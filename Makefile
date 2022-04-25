up:
	docker-compose up --build --force-recreate -d
	docker ps
	docker-compose exec php-fpm bash

down:
	docker-compose down

r: down up

bash:
	docker-compose exec php-fpm bash

db:
	php artisan binance:run

ps:
	docker ps

perm:
	find . -type f -exec chmod a+rw {} \;
	find . -type d -exec chmod a+rwx {} \;
	# sudo chmod 777 ./ -R

#docker-compose logs