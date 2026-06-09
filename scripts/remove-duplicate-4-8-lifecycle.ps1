$path = Join-Path $PSScriptRoot '..\content\security-plus\lessons\sy701_4_8.html'
$utf8 = New-Object System.Text.UTF8Encoding $false
$html = [System.IO.File]::ReadAllText($path, $utf8)
$marker = '<h4>Visual Representation: 🚨 Comprehensive Incident Response Lifecycle</h4>'
$first = $html.IndexOf($marker)
$second = $html.IndexOf($marker, $first + 1)
if ($second -gt 0) {
    $end = $html.IndexOf('</div>', $second)
    # Close outer ut-lesson-diagram (find third closing from second block start)
    $pos = $second
    $depth = 0
    $diagramEnd = -1
    while ($pos -lt $html.Length) {
        if ($html.Substring($pos) -match '^(<div\b[^>]*>)') {
            $depth++
            $pos += $Matches[0].Length
            continue
        }
        if ($html.Substring($pos) -match '^(</div>)') {
            $depth--
            $pos += $Matches[0].Length
            if ($depth -eq 0) {
                $diagramEnd = $pos
                break
            }
            continue
        }
        $pos++
    }
    if ($diagramEnd -gt $second) {
        $html = $html.Remove($second, $diagramEnd - $second)
        [System.IO.File]::WriteAllText($path, $html, $utf8)
        Write-Host 'removed_duplicate_4_8_lifecycle=1'
    }
}
