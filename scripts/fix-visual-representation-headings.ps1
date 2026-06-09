# Repair Visual Representation headings inserted with mojibake titles.
$lessonsDir = Join-Path $PSScriptRoot '..\content\security-plus\lessons'
$utf8 = New-Object System.Text.UTF8Encoding $false

Get-ChildItem (Join-Path $lessonsDir 'sy701_*.html') | ForEach-Object {
    $path = $_.FullName
    $html = [System.IO.File]::ReadAllText($path, $utf8)
    $original = $html

    $pattern = '(?s)<h4>Visual Representation: [^<]*</h4>\s*<p>The following diagram illustrates this concept\.</p>\s*<div class="ut-lesson-diagram">\s*(?:<div class="diagram-title">([^<]+)</div>|<h4>([^<]+)</h4>)'
    $html = [regex]::Replace($html, $pattern, {
        param($m)
        $raw = if ($m.Groups[1].Success -and $m.Groups[1].Value) { $m.Groups[1].Value } else { $m.Groups[2].Value }
        $title = ($raw -replace '^[\p{So}\p{Sk}\s]+', '').Trim()
        if ($title -eq '') { $title = 'Lesson Concept Overview' }
        return "<h4>Visual Representation: $title</h4>`n<p>The following diagram illustrates this concept.</p>`n<div class=`"ut-lesson-diagram`">`n<div class=`"diagram-title`">$title</div>`n"
    })

    if ($html -ne $original) {
        [System.IO.File]::WriteAllText($path, $html, $utf8)
        Write-Host "fixed $($_.Name)"
    }
}

Write-Host 'fix_visual_representation_headings_complete=1'
