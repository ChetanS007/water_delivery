
$baseUrl = "c:\xampp\htdocs\water_delivery"
$indexFile = "$baseUrl\index.php"
$urlFile = "$baseUrl\image_urls.txt"
$imgDir = "$baseUrl\assets\img"

if (-not (Test-Path $imgDir)) {
    New-Item -ItemType Directory -Force -Path $imgDir
}

$urlsRaw = Get-Content $urlFile
$content = Get-Content $indexFile -Raw

# Regex to extract clean URLs from the messy text file lines
$urlRegex = "https://uaques\.smartdemowp\.com/[^""'\s<>]+\.(?:png|jpg|jpeg|gif|svg|webp)"

$downloaded = @{}

foreach ($line in $urlsRaw) {
    if ($line -match $urlRegex) {
        $url = $matches[0]
        
        # specific fix for the srcset/weird lines if regex captured too much or little, but strictly matching extension should be safe.
        # Check if we already processed this URL
        if ($downloaded.ContainsKey($url)) { continue }
        
        $fileName = [System.IO.Path]::GetFileName($url)
        $localPath = Join-Path $imgDir $fileName
        
        # Handle filename collisions
        $counter = 1
        $baseName = [System.IO.Path]::GetFileNameWithoutExtension($fileName)
        $ext = [System.IO.Path]::GetExtension($fileName)
        
        while (Test-Path $localPath) {
            # Check if it's actually the same file (optimization skipped for simplicity, assuming different URLs might imply different files or just overwrite/skip)
            # Actually, if we already downloaded it *in this script run*, we skipped.
            # If it exists from previous run or user? 
            # Let's just create a unique name to be safe so we don't overwrite user's manual "ice-1.png" if it differs, though likely it's the same.
            # Actually, user made "assets/img/ice-1.png". If I download `ice-1.png`, I'll overwrite it. That's fine, it's the same image.
            
             # But what about border-1.png vs border-1.png (different folders)?
             # We need to distinguish based on URL.
             # If filenames are same but URLs are different -> Rename.
             
             # Since I don't validte file content, I will assume collision = rename needed UNLESS I just downloaded it.
             # But I can't easily check "did I just download this?" without tracking filenames.
             # I'll track used filenames.
             
             $fileName = "${baseName}_${counter}${ext}"
             $localPath = Join-Path $imgDir $fileName
             $counter++
        }
        
        # Mark as downloaded to avoid processing same URL twice
        $downloaded[$url] = $fileName
        
        Write-Host "Downloading $url to $fileName..."
        try {
            Invoke-WebRequest -Uri $url -OutFile $localPath
        } catch {
            Write-Host "Failed to download $url"
            continue
        }
        
        # Replace in content
        # We need to be careful about matching. strict string replace of the URL.
        $content = $content.Replace($url, "assets/img/$fileName")
    }
}

Set-Content -Path $indexFile -Value $content
Write-Host "Done updating index.php"
