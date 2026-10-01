@echo off
title Online Complaint Management System
echo ====================================================
echo Starting Online Complaint Management System...
echo ====================================================

cd /d "%~dp0"

if exist "C:\Program Files\php\php.exe" (
    set PHP_EXE="C:\Program Files\php\php.exe"
) else if exist "D:\php\php.exe" (
    set PHP_EXE="D:\php\php.exe"
) else (
    set PHP_EXE=php
)

echo Starting local server on http://127.0.0.1:8000 ...
start "" http://127.0.0.1:8000/
%PHP_EXE% -c php.ini -S 127.0.0.1:8000
pause
