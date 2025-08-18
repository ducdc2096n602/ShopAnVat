@echo off
cd /d C:\xampp\htdocs\ShopAnVat\gemini-api-middleware
call pm2 start server.js --name gemini-api
