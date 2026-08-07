# Test PSGC API
$resp = (Invoke-WebRequest -Uri 'https://psgc.cloud/api/v2/cities-municipalities' -UseBasicParsing).Content | ConvertFrom-Json

Write-Host "Total cities:" $resp.data.Count

Write-Host "`nTesting multiple cities for barangay availability:"
$testCities = @('Adams', 'Quezon', 'Cavite', 'Antipolo')

foreach ($cityName in $testCities) {
    $city = $resp.data | Where-Object {$_.name -like "*$cityName*"} | Select-Object -First 1
    if ($city) {
        Write-Host "`nCity: $($city.name), Code: $($city.code)"
        
        $barangaysUrl = "https://psgc.cloud/api/v2/cities-municipalities/$($city.code)/barangays"
        $barangays = (Invoke-WebRequest -Uri $barangaysUrl -UseBasicParsing).Content | ConvertFrom-Json
        Write-Host "  Barangays count: $($barangays.data.Count)"
        if ($barangays.data.Count -gt 0) {
            Write-Host "  First 2 barangays: $($barangays.data[0].name), $($barangays.data[1].name)"
        }
    }
}

