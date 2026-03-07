@echo off
title Kore ERP - Windows Server Installer
color 0B

echo.
echo  ============================================================
echo   Kore ERP - Windows Server Installer
echo  ============================================================
echo.

:: ─── Check if running as Administrator ───────────────────────────────────────
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo  [!] Administrator privileges required.
    echo  [!] Restarting installer with elevated permissions...
    echo.

    :: Re-launch this script with RunAs
    powershell -Command ^
        "Start-Process cmd -ArgumentList '/c cd /d \"%~dp0\" && \"%~f0\"' -Verb RunAs"

    :: If powershell elevation fails, show manual instruction
    if %errorLevel% NEQ 0 (
        echo  [!] Could not auto-elevate. Please:
        echo.
        echo      1. Right-click install.bat
        echo      2. Select "Run as administrator"
        echo.
        pause
    )
    exit /b
)

:: ─── Check PowerShell is available ───────────────────────────────────────────
where powershell >nul 2>&1
if %errorLevel% NEQ 0 (
    echo  [!] PowerShell is required but not found.
    echo  [!] PowerShell 5.1 is included with Windows 8.1+ and Server 2012 R2+.
    echo.
    pause
    exit /b 1
)

:: ─── Check PowerShell version ─────────────────────────────────────────────────
for /f "tokens=*" %%v in ('powershell -Command "$PSVersionTable.PSVersion.Major" 2^>nul') do set PS_VER=%%v
if defined PS_VER (
    if %PS_VER% LSS 5 (
        echo  [!] PowerShell 5.1 or higher is required. Found version %PS_VER%.
        echo  [!] Update PowerShell from: https://aka.ms/wmf51
        echo.
        pause
        exit /b 1
    )
)

:: ─── Run the installer ────────────────────────────────────────────────────────
echo  [+] Running Kore ERP installer...
echo  [+] Working directory: %~dp0
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1"
set EXIT_CODE=%errorLevel%

if %EXIT_CODE% NEQ 0 (
    echo.
    echo  [!] Installer exited with code %EXIT_CODE%.
    echo  [!] Check the install log in your %%TEMP%% directory for details.
    echo.
)

:: Keep window open if it wasn't opened by user interaction
if "%1"=="auto" exit /b %EXIT_CODE%
pause
exit /b %EXIT_CODE%
