
$lines = Get-Content 'c:\Users\Admin\AppData\Roaming\Code\User\workspaceStorage\3813f61238a4b0e8a06d1c11a7642a25\GitHub.copilot-chat\chat-session-resources\cf805bbd-783d-4f55-9723-711a10967f03\call_MHx2SlpmRHhxMVN6U1VqTk05U2k__vscode-1775018326903\content.txt' -Raw
$scriptContent = [regex]::Match($lines, '(?s)`powershell(.*?)`').Groups[1].Value
Set-Content -Path 'rewrite.ps1' -Value $scriptContent -Encoding UTF8

