@echo off
pushd "%~dp0"
set "phpexe=C:\php\php.exe"
set "dlerPHP=D:\somefolder\tapas.io_downloader.php"

REM VAR syntax is "<EP#>_<Path>" ["<otherComicsEP#>_<otherPath>" ["..."]]
set VAR="294730;ForestTails" 

if exist "%phpexe%" if exist "%dlerPHP%" (
	for %%I IN (%VAR%) do for /F "tokens=1,* delims=;" %%J in ("%%~I") do (
		"%phpexe%" "%dlerPHP%" -e %%~J -p "%%~K"
	)
	goto :EOFi
)
echo+ ERROR: missing "%phpexe%" and/or "%dlerPHP%".
:EOFi
popd
pause
exit /B