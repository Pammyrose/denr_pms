$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$workerDirectory = Join-Path $projectRoot 'storage/app/private'
$workerStatePath = Join-Path $workerDirectory 'email-worker.json'
$phpExecutable = (Get-Command php -ErrorAction Stop).Source

if (Test-Path -LiteralPath $workerStatePath) {
    $workerState = Get-Content -LiteralPath $workerStatePath -Raw | ConvertFrom-Json
    $existingWorker = Get-Process -Id $workerState.pid -ErrorAction SilentlyContinue
    if ($existingWorker -and $existingWorker.Path -eq $phpExecutable -and $existingWorker.StartTime.ToUniversalTime().ToString('o') -eq $workerState.started_at) {
        Write-Output ('Email worker is already running (PID ' + $existingWorker.Id + ').')
        exit 0
    }
}

$workerArguments = @('artisan', 'queue:work', '--queue=email-auth', '--sleep=1', '--tries=3', '--timeout=30')
$workerProcess = Start-Process -FilePath $phpExecutable -ArgumentList $workerArguments -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput (Join-Path $workerDirectory 'email-worker-output.log') -RedirectStandardError (Join-Path $workerDirectory 'email-worker-errors.log') -PassThru
Start-Sleep -Seconds 2
if ($workerProcess.HasExited) {
    throw 'Email worker exited. Check storage/app/private/email-worker-errors.log.'
}
@{ pid = $workerProcess.Id; started_at = $workerProcess.StartTime.ToUniversalTime().ToString('o') } | ConvertTo-Json | Set-Content -LiteralPath $workerStatePath -Encoding UTF8
Write-Output ('Email worker started in the background (PID ' + $workerProcess.Id + ').')
