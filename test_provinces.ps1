# Test PSGC Regions API
$regionsUrl = "https://psgc.cloud/api/v2/regions"
$regions = (Invoke-WebRequest -Uri $regionsUrl -UseBasicParsing).Content | ConvertFrom-Json

Write-Host "Checking PSGC API for provinces/regions:"
Write-Host "Total regions/provinces: $($regions.data.Count)"

if ($regions.data.Count -gt 0) {
    Write-Host "`nFirst 10 regions:"
    $regions.data | Select-Object -First 10 | ForEach-Object {
        Write-Host "  $($_.name) - Code: $($_.code)"
    }
    
    # Test getting provinces for a region
    Write-Host "`nTesting provinces endpoint for first region:"
    $firstRegion = $regions.data[0]
    $provinceUrl = "https://psgc.cloud/api/v2/regions/$($firstRegion.code)/provinces"
    $provinces = (Invoke-WebRequest -Uri $provinceUrl -UseBasicParsing).Content | ConvertFrom-Json
    Write-Host "Region: $($firstRegion.name)"
    Write-Host "Total provinces: $($provinces.data.Count)"
    if ($provinces.data.Count -gt 0) {
        Write-Host "First province: $($provinces.data[0].name)"
    }
}
