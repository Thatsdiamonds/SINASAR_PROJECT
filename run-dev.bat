@echo off

cd backend
start cmd /k "php artisan serve"
cd ..

cd frontend
start cmd /k "npm run dev"
cd ..
