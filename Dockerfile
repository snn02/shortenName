# Используем официальный образ PHP с Apache
FROM php:8.3-apache

# Копируем все файлы из текущей папки в корень веб-сервера
COPY . /var/www/html/

# (Опционально) Устанавливаем расширения, если понадобятся позже
# RUN docker-php-ext-install mysqli gd mbstring