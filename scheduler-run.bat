@echo off
cd /d C:\laragon\www\datn_backend
php artisan schedule:run > NUL 2>&1
