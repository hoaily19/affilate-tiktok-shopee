@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo ====================================
echo  TikTok Affiliate Shop
echo ====================================
echo.
echo Khoi dong server...
echo Server: http://localhost:8000
echo.
php -S localhost:8000 router.php
pause
