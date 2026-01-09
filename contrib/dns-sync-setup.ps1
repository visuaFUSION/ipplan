# IPplan DNS Zone Sync - Windows Setup Script
# Run this script as Administrator to install the scheduled task

#Requires -RunAsAdministrator

param(
    [string]$PhpPath = "",
    [string]$IpplanPath = "",
    [int]$IntervalMinutes = 15,
    [switch]$Uninstall,
    [switch]$Status,
    [switch]$RunNow
)

$TaskName = "IPplan DNS Zone Sync"
$TaskDescription = "Periodically synchronizes DNS zones from configured DNS servers into IPplan database"

function Write-Header {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host " IPplan DNS Zone Sync Setup" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
}

function Find-PhpExecutable {
    # Common PHP installation locations on Windows
    $searchPaths = @(
        "C:\php\php.exe",
        "C:\xampp\php\php.exe",
        "C:\wamp64\bin\php\php*\php.exe",
        "C:\wamp\bin\php\php*\php.exe",
        "C:\laragon\bin\php\php*\php.exe",
        "$env:ProgramFiles\PHP\php.exe",
        "${env:ProgramFiles(x86)}\PHP\php.exe"
    )

    # Check PATH first
    $phpInPath = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($phpInPath) {
        return $phpInPath.Source
    }

    # Search common locations
    foreach ($path in $searchPaths) {
        $resolved = Resolve-Path $path -ErrorAction SilentlyContinue
        if ($resolved) {
            return $resolved.Path | Select-Object -First 1
        }
    }

    return $null
}

function Find-IpplanPath {
    # Try to determine IPplan path from script location
    $scriptDir = Split-Path -Parent $MyInvocation.ScriptName
    $ipplanDir = Split-Path -Parent $scriptDir

    if (Test-Path "$ipplanDir\config.php") {
        return $ipplanDir
    }

    return $null
}

function Show-Status {
    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

    if ($task) {
        Write-Host "Task Status: " -NoNewline
        switch ($task.State) {
            "Ready" { Write-Host "Ready" -ForegroundColor Green }
            "Running" { Write-Host "Running" -ForegroundColor Yellow }
            "Disabled" { Write-Host "Disabled" -ForegroundColor Red }
            default { Write-Host $task.State -ForegroundColor Gray }
        }

        $taskInfo = Get-ScheduledTaskInfo -TaskName $TaskName -ErrorAction SilentlyContinue
        if ($taskInfo) {
            Write-Host "Last Run: $($taskInfo.LastRunTime)"
            Write-Host "Last Result: $($taskInfo.LastTaskResult)"
            Write-Host "Next Run: $($taskInfo.NextRunTime)"
        }

        # Show task details
        $action = $task.Actions | Select-Object -First 1
        Write-Host ""
        Write-Host "Configuration:"
        Write-Host "  Executable: $($action.Execute)"
        Write-Host "  Arguments: $($action.Arguments)"
        Write-Host "  Working Dir: $($action.WorkingDirectory)"

        $trigger = $task.Triggers | Select-Object -First 1
        if ($trigger.Repetition) {
            $interval = $trigger.Repetition.Interval
            Write-Host "  Interval: $interval"
        }
    } else {
        Write-Host "Task is not installed." -ForegroundColor Yellow
    }
}

function Uninstall-Task {
    Write-Host "Uninstalling scheduled task..." -ForegroundColor Yellow

    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($task) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "Task '$TaskName' has been removed." -ForegroundColor Green
    } else {
        Write-Host "Task '$TaskName' was not found." -ForegroundColor Yellow
    }
}

function Install-Task {
    param(
        [string]$PhpExe,
        [string]$IpplanDir,
        [int]$Interval
    )

    $scriptPath = Join-Path $IpplanDir "contrib\dns-zone-sync.php"
    $workingDir = Join-Path $IpplanDir "contrib"

    # Verify the sync script exists
    if (-not (Test-Path $scriptPath)) {
        Write-Host "Error: DNS sync script not found at: $scriptPath" -ForegroundColor Red
        return $false
    }

    # Verify PHP can run the script
    Write-Host "Testing PHP execution..." -ForegroundColor Gray
    $testResult = & $PhpExe $scriptPath --help 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Warning: PHP test returned exit code $LASTEXITCODE" -ForegroundColor Yellow
    }

    # Remove existing task if present
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Removing existing task..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }

    # Create the action
    $action = New-ScheduledTaskAction `
        -Execute $PhpExe `
        -Argument "`"$scriptPath`"" `
        -WorkingDirectory $workingDir

    # Create trigger that runs every N minutes indefinitely
    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
        -RepetitionInterval (New-TimeSpan -Minutes $Interval)

    # Create settings - run whether user is logged in or not
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -RunOnlyIfNetworkAvailable `
        -MultipleInstances IgnoreNew

    # Create principal to run as SYSTEM
    $principal = New-ScheduledTaskPrincipal `
        -UserId "SYSTEM" `
        -LogonType ServiceAccount `
        -RunLevel Highest

    # Register the task
    Write-Host "Creating scheduled task..." -ForegroundColor Gray
    try {
        Register-ScheduledTask `
            -TaskName $TaskName `
            -Description $TaskDescription `
            -Action $action `
            -Trigger $trigger `
            -Settings $settings `
            -Principal $principal `
            -Force | Out-Null

        Write-Host ""
        Write-Host "Scheduled task created successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Task Details:" -ForegroundColor Cyan
        Write-Host "  Name: $TaskName"
        Write-Host "  PHP: $PhpExe"
        Write-Host "  Script: $scriptPath"
        Write-Host "  Interval: Every $Interval minutes"
        Write-Host "  Run as: SYSTEM"
        Write-Host ""
        Write-Host "Next Steps:" -ForegroundColor Yellow
        Write-Host "  1. Configure zones in IPplan web interface or"
        Write-Host "     edit: $IpplanDir\data\dns-sync-config.json"
        Write-Host "  2. View task status: .\dns-sync-setup.ps1 -Status"
        Write-Host "  3. Run immediately: .\dns-sync-setup.ps1 -RunNow"
        Write-Host ""

        return $true
    } catch {
        Write-Host "Failed to create scheduled task: $_" -ForegroundColor Red
        return $false
    }
}

function Invoke-TaskNow {
    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($task) {
        Write-Host "Starting task..." -ForegroundColor Yellow
        Start-ScheduledTask -TaskName $TaskName
        Start-Sleep -Seconds 2
        Show-Status
    } else {
        Write-Host "Task is not installed. Run this script without parameters to install." -ForegroundColor Red
    }
}

# Main execution
Write-Header

if ($Status) {
    Show-Status
    exit 0
}

if ($Uninstall) {
    Uninstall-Task
    exit 0
}

if ($RunNow) {
    Invoke-TaskNow
    exit 0
}

# Installation mode
Write-Host "Installing DNS Zone Sync scheduled task..."
Write-Host ""

# Find or verify PHP path
if ([string]::IsNullOrEmpty($PhpPath)) {
    Write-Host "Searching for PHP executable..." -ForegroundColor Gray
    $PhpPath = Find-PhpExecutable
    if ([string]::IsNullOrEmpty($PhpPath)) {
        Write-Host "Error: Could not find PHP executable." -ForegroundColor Red
        Write-Host "Please specify the path using -PhpPath parameter" -ForegroundColor Yellow
        Write-Host "Example: .\dns-sync-setup.ps1 -PhpPath 'C:\php\php.exe'" -ForegroundColor Gray
        exit 1
    }
}

if (-not (Test-Path $PhpPath)) {
    Write-Host "Error: PHP executable not found at: $PhpPath" -ForegroundColor Red
    exit 1
}

Write-Host "Found PHP: $PhpPath" -ForegroundColor Green

# Find or verify IPplan path
if ([string]::IsNullOrEmpty($IpplanPath)) {
    Write-Host "Searching for IPplan installation..." -ForegroundColor Gray
    $IpplanPath = Find-IpplanPath
    if ([string]::IsNullOrEmpty($IpplanPath)) {
        Write-Host "Error: Could not find IPplan installation." -ForegroundColor Red
        Write-Host "Please specify the path using -IpplanPath parameter" -ForegroundColor Yellow
        Write-Host "Example: .\dns-sync-setup.ps1 -IpplanPath 'C:\inetpub\wwwroot\ipplan'" -ForegroundColor Gray
        exit 1
    }
}

if (-not (Test-Path "$IpplanPath\config.php")) {
    Write-Host "Error: IPplan installation not found at: $IpplanPath" -ForegroundColor Red
    Write-Host "(config.php not found in directory)" -ForegroundColor Gray
    exit 1
}

Write-Host "Found IPplan: $IpplanPath" -ForegroundColor Green
Write-Host ""

# Ensure data directory exists
$dataDir = Join-Path $IpplanPath "data"
if (-not (Test-Path $dataDir)) {
    Write-Host "Creating data directory: $dataDir" -ForegroundColor Gray
    New-Item -ItemType Directory -Path $dataDir -Force | Out-Null
}

# Install the task
if (Install-Task -PhpExe $PhpPath -IpplanDir $IpplanPath -Interval $IntervalMinutes) {
    exit 0
} else {
    exit 1
}
