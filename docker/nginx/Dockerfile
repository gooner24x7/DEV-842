FROM nginx:alpine

COPY nginx.conf /etc/nginx/nginx.conf
RUN rm -f /etc/nginx/conf.d/default.conf
COPY laravel.conf /etc/nginx/conf.d/laravel.conf

RUN mkdir -p /var/www/

WORKDIR /var/www/

CMD ["nginx", "-g", "daemon off;"]
