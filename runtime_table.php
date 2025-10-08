<?php
// runtime_table.php – PHP/JS futtatási kapcsolatok feltérképezése táblázatban

declare(strict_types=1);

$ROOT = __DIR__;

function rglob(string $dir, array $exts): array {
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, $exts, true)) {
                $files[] = str_replace('\\','/',$f->getPathname());
            }
        }
    }
    return $files;
}

function lintStatus(string $file): string {
    $disabled = ini_get('disable_functions');
    if ($disabled && stripos($disabled,'shell_exec')!==false) return "Lint unavailable";
    $out = @shell_exec('php -l '.escapeshellarg($file).' 2>&1');
    if (!$out) return "Lint unavailable";
    return (stripos($out,'No syntax errors detected')!==false) ? "Lint OK" : "Lint error";
}

function analyzePhp(string $file, string $code): array {
    $edges = [];
    // include/require
    if (preg_match_all('/\b(?:require|include)(?:_once)?\s*\(?\s*(?:__DIR__\s*\.\s*)?[\'"]([^\'"]+)[\'"]/', $code, $m)) {
        foreach ($m[1] as $inc) {
            $resolved = realpath(dirname($file).'/'.$inc);
            $edges[] = ['type'=>'php-include','target'=>$resolved ?: $inc];
        }
    }
    // reads
    foreach (['file_get_contents','readfile'] as $fn) {
        if (preg_match_all('/\b'.$fn.'\s*\(\s*[\'"]([^\'"]+)/', $code, $m)) {
            foreach ($m[1] as $p) $edges[] = ['type'=>'php-read','target'=>$p];
        }
    }
    if (preg_match_all('/\bfopen\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]r/', $code, $m)) {
        foreach ($m[1] as $p) $edges[] = ['type'=>'php-read','target'=>$p];
    }
    // writes
    if (preg_match_all('/\bfile_put_contents\s*\(\s*[\'"]([^\'"]+)/', $code, $m)) {
        foreach ($m[1] as $p) $edges[] = ['type'=>'php-write','target'=>$p];
    }
    if (preg_match_all('/\bfopen\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]w/', $code, $m)) {
        foreach ($m[1] as $p) $edges[] = ['type'=>'php-write','target'=>$p];
    }
    // http
    if (preg_match_all('/\bcurl_init\s*\(\s*[\'"]([^\'"]+)/', $code, $m)) {
        foreach ($m[1] as $u) $edges[] = ['type'=>'php-http','target'=>$u];
    }
    if (preg_match_all('/\bfile_get_contents\s*\(\s*[\'"](https?:\/\/[^\'"]+)/i', $code, $m)) {
        foreach ($m[1] as $u) $edges[] = ['type'=>'php-http','target'=>$u];
    }
    // redirect
    if (preg_match_all('/\bheader\s*\(\s*[\'"]Location:\s*([^\'"]+)/i', $code, $m)) {
        foreach ($m[1] as $loc) $edges[] = ['type'=>'php-redirect','target'=>$loc];
    }
    return $edges;
}

function analyzeJs(string $file, string $code): array {
    $edges = [];
    if (preg_match_all('/\bfetch\s*\(\s*[\'"]([^\'"]+)/', $code, $m)) {
        foreach ($m[1] as $u) $edges[] = ['type'=>'js-calls-php','target'=>$u];
    }
    if (preg_match_all('/\burl\s*:\s*[\'"]([^\'"]+)/i', $code, $m)) {
        foreach ($m[1] as $u) $edges[] = ['type'=>'js-calls-php','target'=>$u];
    }
    return $edges;
}

// --- Collect ---
$phpFiles = rglob($ROOT, ['php']);
$jsFiles  = rglob($ROOT, ['js']);

$rows = [];
$allTargets = [];
foreach ($phpFiles as $php) {
    $code = @file_get_contents($php) ?: '';
    $lint = lintStatus($php);
    $edges = analyzePhp($php, $code);
    if (!$edges) {
        $rows[] = [$php, 'php', $lint, '-', '-'];
    } else {
        foreach ($edges as $e) {
            $rows[] = [$php, 'php', $lint, $e['type'], $e['target']];
            $allTargets[] = $e['target'];
        }
    }
}
foreach ($jsFiles as $js) {
    $code = @file_get_contents($js) ?: '';
    $edges = analyzeJs($js, $code);
    if (!$edges) {
        $rows[] = [$js, 'js', '-', '-', '-'];
    } else {
        foreach ($edges as $e) {
            $rows[] = [$js, 'js', '-', $e['type'], $e['target']];
            $allTargets[] = $e['target'];
        }
    }
}

// Árva fájlok
$orphans = array_diff($phpFiles, array_map('strval',$allTargets));

?><!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<title>Runtime kapcsolatok – táblázat</title>
<style>
  body { background:#121212; color:#eee; font-family:sans-serif; padding:20px; }
  table { border-collapse:collapse; width:100%; font-size:14px; }
  th,td { border:1px solid #333; padding:6px 8px; vertical-align:top; }
  th { background:#1e1e1e; }
  tr:nth-child(even){ background:#1a1a1a; }
  .ok { color:#8bc34a; font-weight:bold; }
  .err { color:#f44336; font-weight:bold; }
  .unk { color:#9e9e9e; }
</style>
</head>
<body>
<h1>Runtime kapcsolatok – táblázat</h1>
<table>
<thead>
<tr><th>Forrásfájl</th><th>Típus</th><th>Lint</th><th>Kapcsolat típusa</th><th>Cél</th></tr>
</thead>
<tbody>
<?php foreach ($rows as $r): 
  [$src,$kind,$lint,$type,$target] = $r;
  $cls = $lint==='Lint OK'?'ok':($lint==='Lint error'?'err':'unk');
?>
<tr>
  <td><?=htmlspecialchars($src)?></td>
  <td><?=htmlspecialchars($kind)?></td>
  <td class="<?=$cls?>"><?=htmlspecialchars($lint)?></td>
  <td><?=htmlspecialchars($type)?></td>
  <td><?=htmlspecialchars($target)?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h2>Árva PHP fájlok</h2>
<ul>
<?php foreach ($orphans as $o): ?>
  <li><?=htmlspecialchars($o)?></li>
<?php endforeach; ?>
</ul>
</body>
</html>


