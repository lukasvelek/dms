@echo off
echo Deleting log files
del /Q logs\*.*
rmdir logs\ /Q /S
mkdir logs\
echo Deleting cache files
del /Q cache\*.*
rmdir cache\ /Q /S
mkdir cache\
echo Deleting install files
del /Q app\core\install
echo Deleting finished
pause