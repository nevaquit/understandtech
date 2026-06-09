# Insert Visual Representation headings before ut-lesson-diagram blocks that lack one.
$lessonsDir = Join-Path $PSScriptRoot '..\content\security-plus\lessons'
Get-ChildItem (Join-Path $lessonsDir 'sy701_*.html') | ForEach-Object {
    $html = Get-Content $_.FullName -Raw
    $original = $html
    $offset = 0
    while ($html.Substring($offset) -match '(?s)(<div\s+class="[^"]*\but-lesson-diagram\b[^"]*")') {
        $m = [regex]::Match($html.Substring($offset), '(?s)(<div\s+class="[^"]*\but-lesson-diagram\b[^"]*")')
        if (-not $m.Success) { break }
        $diagramStart = $offset + $m.Index
        $lookbackStart = [Math]::Max(0, $diagramStart - 800)
        $lookback = $html.Substring($lookbackStart, $diagramStart - $lookbackStart)
        if ($lookback -match '<h4>Visual Representation:[^<]*</h4>') {
            $offset = $diagramStart + 1
            continue
        }
        $title = 'Lesson Concept Overview'
        $rest = $html.Substring($diagramStart)
        if ($rest -match '<div class="diagram-title">([^<]+)</div>') {
            $title = ($Matches[1] -replace '^[\p{So}\p{Sk}\s]+', '').Trim()
        } elseif ($rest -match '<h4>([^<]+)</h4>') {
            $title = ($Matches[1] -replace '^[\p{So}\p{Sk}\s]+', '').Trim()
        }
        $escaped = [System.Net.WebUtility]::HtmlEncode($title)
        $block = "<h4>Visual Representation: $escaped</h4>`n<p>The following diagram illustrates this concept.</p>`n"
        $html = $html.Substring(0, $diagramStart) + $block + $html.Substring($diagramStart)
        Write-Host "heading $($_.Name) @ $diagramStart ($title)"
        $offset = $diagramStart + $block.Length + 1
    }
    if ($html -ne $original) {
        Set-Content -Path $_.FullName -Value $html -NoNewline
    }
}
Write-Host 'ensure_lesson_visual_headings_complete=1'
