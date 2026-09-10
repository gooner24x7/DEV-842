.PHONY: npm-build
npm-build:
	docker exec -it php-fpm-project1 bash -c "cd ../dashboard && npm run-script dev"
migrate:
	docker exec -it php-fpm-project1 bash -c "cd ../dashboard && php artisan migrate"