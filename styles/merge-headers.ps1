# merge-sources.ps1

# --- Configuration ---
# List all the C++ header files you want to EXCLUDE from the final merged file.
$excludeList = ""

# The name of the output file that will contain all the merged code.
$outputFile = "All.txt" # 

# --- Script Logic ---
# Get the full path of the directory where the script is running.
$scriptPath = $PSScriptRoot

Write-Host "Starting script in directory: $scriptPath"
Write-Host "Excluding the following files:"
$excludeList | ForEach-Object { Write-Host "- $_" }
Write-Host "Output will be saved to: $outputFile"
Write-Host "---"

# Find all .cpp files, filter out the ones in the exclude list,
# get their content, and save it all to the output file.
try {
    Get-ChildItem -Path $scriptPath -Filter *.hpp | `
        Where-Object { $_.Name -notin $excludeList } | `
        Get-Content | `
        Set-Content -Path (Join-Path $scriptPath $outputFile)

    Write-Host "Success! Merged header files into '$outputFile'."
}
catch {
    Write-Error "An error occurred during the merge process: $_"
}

Write-Host "Script finished."