@echo off
echo Running PHP script...
C:\xampp\php\php.exe C:\xampp\htdocs\HR2\cron\default_absent.php
if %errorlevel% neq 0 (
    echo Error: PHP script failed with exit code %errorlevel%.
    pause
) else (
    echo PHP script completed successfully.
)
pause