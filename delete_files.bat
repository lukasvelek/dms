@echo off
echo Deleting log files
del /Q logs\*.*
echo Deleting cache files
del /Q cache\*.*
echo Deleting install files
del /Q app\core\install
echo Deleting finished
pause