@echo off
setlocal
set "CURSOR_PLUGIN_ROOT=%~dp0..\superpowers"
"%~dp0..\superpowers\hooks\run-hook.cmd" session-start
