# Restore diagram-title divs removed by fix-visual-representation-headings.ps1
$lessonsDir = Join-Path $PSScriptRoot '..\content\security-plus\lessons'
$utf8 = New-Object System.Text.UTF8Encoding $false

Get-ChildItem (Join-Path $lessonsDir 'sy701_*.html') | ForEach-Object {
    $path = $_.FullName
    $html = [System.IO.File]::ReadAllText($path, $utf8)
    $original = $html

    $pattern = '(?s)(<h4>Visual Representation: ([^<]+)</h4>\s*<p>The following diagram illustrates this concept\.</p>\s*<div class="ut-lesson-diagram">\s*)(?!<div class="diagram-title">|<h4>)'
    $html = [regex]::Replace($html, $pattern, {
        param($m)
        $title = $m.Groups[2].Value.Trim()
        return $m.Groups[1].Value + "<div class=`"diagram-title`">$title</div>`n"
    })

    if ($html -ne $original) {
        [System.IO.File]::WriteAllText($path, $html, $utf8)
        Write-Host "restored $($_.Name)"
    }
}

Write-Host 'restore_diagram_titles_complete=1'
