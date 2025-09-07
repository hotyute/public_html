# --- Configuration ---

# The root path of your project. Use "." for the current directory.
$projectPath = "."

# The base name of the combined CSS file that will be created in the project path.
$combinedCssFile = "combined_styles.txt"

# The base name of the combined JS file that will be created in the project path.
$combinedJsFile = "combined_scripts.txt"

# The base name of the combined PHP file that will be created in the project path.
$combinedPhpFile = "combined_php.txt"

# The maximum file size in kilobytes (KB) before creating a new split file.
$maxFileSizeKB = 100

# NEW: Array of directory names to exclude from the search (case-insensitive).
# The script will skip any file whose path contains these folder names.
# Example: $excludedDirs = @("node_modules", "vendor", "dist", "build")
$excludedDirs = @("node_modules", "vendor")


# --- Script Logic ---

# Convert KB to Bytes for comparison
$maxFileSizeBytes = $maxFileSizeKB * 1024

# Get the full path for the project
$fullProjectPath = (Get-Item -Path $projectPath).FullName

# Create a regex pattern from the excluded directories array for efficient filtering.
# This will create a string like "node_modules|vendor"
$excludePattern = $excludedDirs -join '|'

# --- Process CSS Files ---

Write-Host "--- Starting to combine CSS files ---" -ForegroundColor Yellow

# Parse the base name and extension for dynamic naming
$cssBaseName = [System.IO.Path]::GetFileNameWithoutExtension($combinedCssFile)
$cssExtension = [System.IO.Path]::GetExtension($combinedCssFile)
$cssSplitIndex = 1
$outputCssPath = Join-Path -Path $fullProjectPath -ChildPath "$($cssBaseName)_$($cssSplitIndex)$($cssExtension)"

# Ensure the very first output file is empty before starting
Clear-Content -Path $outputCssPath -ErrorAction SilentlyContinue

# Get all CSS files recursively, excluding the output files and the specified directories.
$cssFilesQuery = Get-ChildItem -Path $fullProjectPath -Recurse -Filter "*.css" | Where-Object { $_.Name -notlike "$($cssBaseName)_*$($cssExtension)" }
if ($excludePattern) {
    $cssFiles = $cssFilesQuery | Where-Object { $_.FullName -notmatch $excludePattern }
} else {
    $cssFiles = $cssFilesQuery
}


foreach ($file in $cssFiles) {
    Write-Host "Processing CSS file: $($file.FullName)"
    
    $relativePath = $file.FullName.Replace("$fullProjectPath\", "")
    
    # Prepare the entire block of text that will be appended
    $header = "/* --- Start of $($relativePath) --- */"
    $content = Get-Content -Path $file.FullName -Raw
    $footer = "/* --- End of $($relativePath) --- */`n"
    $contentToAppend = -join($header, "`n", $content, "`n", $footer)
    
    # Check current file size. If file doesn't exist yet, its size is 0.
    $currentFileSize = if (Test-Path $outputCssPath) { (Get-Item $outputCssPath).Length } else { 0 }
    
    # If adding the new content exceeds the max size AND the file isn't empty, create a new split file.
    if (($currentFileSize + $contentToAppend.Length) -gt $maxFileSizeBytes -and $currentFileSize -ne 0) {
        Write-Host "Size limit reached. Creating new CSS split file." -ForegroundColor Cyan
        $cssSplitIndex++
        $outputCssPath = Join-Path -Path $fullProjectPath -ChildPath "$($cssBaseName)_$($cssSplitIndex)$($cssExtension)"
        Clear-Content -Path $outputCssPath -ErrorAction SilentlyContinue
    }
    
    # Append the content to the current output file
    Add-Content -Path $outputCssPath -Value $contentToAppend
}

Write-Host "CSS files successfully combined." -ForegroundColor Green
Write-Host ""


# --- Process JS Files ---

Write-Host "--- Starting to combine JS files ---" -ForegroundColor Yellow

# Parse the base name and extension for dynamic naming
$jsBaseName = [System.IO.Path]::GetFileNameWithoutExtension($combinedJsFile)
$jsExtension = [System.IO.Path]::GetExtension($combinedJsFile)
$jsSplitIndex = 1
$outputJsPath = Join-Path -Path $fullProjectPath -ChildPath "$($jsBaseName)_$($jsSplitIndex)$($jsExtension)"

# Ensure the very first output file is empty before starting
Clear-Content -Path $outputJsPath -ErrorAction SilentlyContinue

# Get all JS files recursively, excluding the output files and the specified directories.
$jsFilesQuery = Get-ChildItem -Path $fullProjectPath -Recurse -Filter "*.js" | Where-Object { $_.Name -notlike "$($jsBaseName)_*$($jsExtension)" }
if ($excludePattern) {
    $jsFiles = $jsFilesQuery | Where-Object { $_.FullName -notmatch $excludePattern }
} else {
    $jsFiles = $jsFilesQuery
}

foreach ($file in $jsFiles) {
    Write-Host "Processing JS file: $($file.FullName)"
    
    $relativePath = $file.FullName.Replace("$fullProjectPath\", "")
    
    # Prepare the entire block of text that will be appended
    $header = "/* --- Start of $($relativePath) --- */"
    $content = Get-Content -Path $file.FullName -Raw
    $footer = "/* --- End of $($relativePath) --- */`n"
    $contentToAppend = -join($header, "`n", $content, "`n", $footer)
    
    # Check current file size. If file doesn't exist yet, its size is 0.
    $currentFileSize = if (Test-Path $outputJsPath) { (Get-Item $outputJsPath).Length } else { 0 }
    
    # If adding the new content exceeds the max size AND the file isn't empty, create a new split file.
    if (($currentFileSize + $contentToAppend.Length) -gt $maxFileSizeBytes -and $currentFileSize -ne 0) {
        Write-Host "Size limit reached. Creating new JS split file." -ForegroundColor Cyan
        $jsSplitIndex++
        $outputJsPath = Join-Path -Path $fullProjectPath -ChildPath "$($jsBaseName)_$($jsSplitIndex)$($jsExtension)"
        Clear-Content -Path $outputJsPath -ErrorAction SilentlyContinue
    }
    
    # Append the content to the current output file
    Add-Content -Path $outputJsPath -Value $contentToAppend
}

Write-Host "JS files successfully combined." -ForegroundColor Green
Write-Host ""


# --- Process PHP Files ---

Write-Host "--- Starting to combine PHP files ---" -ForegroundColor Yellow

# Parse the base name and extension for dynamic naming
$phpBaseName = [System.IO.Path]::GetFileNameWithoutExtension($combinedPhpFile)
$phpExtension = [System.IO.Path]::GetExtension($combinedPhpFile)
$phpSplitIndex = 1
$outputPhpPath = Join-Path -Path $fullProjectPath -ChildPath "$($phpBaseName)_$($phpSplitIndex)$($phpExtension)"

# Ensure the very first output file is empty before starting
Clear-Content -Path $outputPhpPath -ErrorAction SilentlyContinue

# Get all PHP files recursively, excluding the output files and the specified directories.
$phpFilesQuery = Get-ChildItem -Path $fullProjectPath -Recurse -Filter "*.php" | Where-Object { $_.Name -notlike "$($phpBaseName)_*$($phpExtension)" }
if ($excludePattern) {
    $phpFiles = $phpFilesQuery | Where-Object { $_.FullName -notmatch $excludePattern }
} else {
    $phpFiles = $phpFilesQuery
}

foreach ($file in $phpFiles) {
    Write-Host "Processing PHP file: $($file.FullName)"
    
    $relativePath = $file.FullName.Replace("$fullProjectPath\", "")
    
    # Get and clean the content
    $content = Get-Content -Path $file.FullName -Raw
    $cleanedContent = $content -replace '^\s*<\?(php|=)?\s*', '' -replace '\s*\?>\s*$', ''

    # Prepare the entire block of text that will be appended
    $header = "/* --- Start of $($relativePath) --- */"
    $footer = "`n/* --- End of $($relativePath) --- */`n"
    $contentToAppend = -join($header, "`n", $cleanedContent, "`n", $footer)
    
    # Check current file size. If file doesn't exist yet, its size is 0.
    $currentFileSize = if (Test-Path $outputPhpPath) { (Get-Item $outputPhpPath).Length } else { 0 }
    
    # If adding the new content exceeds the max size AND the file isn't empty, create a new split file.
    if (($currentFileSize + $contentToAppend.Length) -gt $maxFileSizeBytes -and $currentFileSize -ne 0) {
        Write-Host "Size limit reached. Creating new PHP split file." -ForegroundColor Cyan
        $phpSplitIndex++
        $outputPhpPath = Join-Path -Path $fullProjectPath -ChildPath "$($phpBaseName)_$($phpSplitIndex)$($phpExtension)"
        Clear-Content -Path $outputPhpPath -ErrorAction SilentlyContinue
    }
    
    # Append the content to the current output file
    Add-Content -Path $outputPhpPath -Value $contentToAppend
}

Write-Host "PHP files successfully combined." -ForegroundColor Green
Write-Host "Script finished." -ForegroundColor Cyan