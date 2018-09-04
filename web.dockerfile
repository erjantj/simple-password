FROM nginx:1.14

COPY public /var/www/public/
ADD vhost.conf /etc/nginx/conf.d/default.conf