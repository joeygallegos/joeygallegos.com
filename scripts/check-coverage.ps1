param(
    [Parameter(Mandatory = $true)]
    [string]$Clover,
    [Parameter(Mandatory = $true)]
    [double]$Minimum
)

if (!(Test-Path -LiteralPath $Clover)) {
    Write-Error "Coverage report not found at: $Clover"
    exit 1
}

[xml]$coverageXml = Get-Content -LiteralPath $Clover
$lineRate = [double]$coverageXml.coverage.'line-rate'
$coveragePercent = [math]::Round($lineRate * 100, 2)

Write-Host ("Line coverage: {0}% (minimum: {1}%)" -f $coveragePercent, $Minimum)

if ($coveragePercent -lt $Minimum) {
    Write-Error ("Coverage gate failed: {0}% is below {1}%" -f $coveragePercent, $Minimum)
    exit 1
}

exit 0
