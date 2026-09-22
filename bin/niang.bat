@ECHO OFF
REM Wrapper Windows (CMD/PowerShell natifs, hors WSL/Git Bash) : le shebang de bin/niang
REM (#!/usr/bin/env php) n'y est pas interprété. `bin\niang.bat <commande>` (ou juste
REM `bin\niang <commande>`, Windows résout l'extension .bat automatiquement) relaie vers
REM `php bin\niang <commande>`, exactement l'équivalent documenté pour Windows dans le README.
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0niang

php "%BIN_TARGET%" %*
