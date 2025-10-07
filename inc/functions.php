<?php
// inc/functions.php – optimalizált, cache-elhető

function load_cache(string $txtFile): ?array {
    $cacheFile = __DIR__ . '/../cache/' . basename($txtFile, '.txt') . '.json';
    if (!file_exists($cacheFile)) return null;

    $data = json_decode(file_get_contents($cacheFile), true);
    if (!$data) return null;

    // ha a forrás újabb, mint a cache → érvénytelen
    $srcFile = __DIR__ . '/../txt/' . $txtFile;
    if (filemtime($srcFile) > $data['mtime']) return null;

    return $data;
}

function save_cache(string $txtFile, array $data): void {
    $cacheFile = __DIR__ . '/../cache/' . basename($txtFile, '.txt') . '.json';
    $data['mtime'] = filemtime(__DIR__ . '/../txt/' . $txtFile);
    file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

function tokenize_words(string $string): array {
    $cleaned = preg_replace('/[[:punct:]]+/u', ' ', $string);
    $words   = preg_split('/\s+/u', mb_strtolower($cleaned), -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_unique($words));
}

function get_unique_words(string $string): array {
    return tokenize_words($string);
}

function get_repeated_words(string $string): array {
    $words  = tokenize_words($string);
    $counts = array_count_values($words);
    return array_keys(array_filter($counts, fn($c) => $c > 1));
}

function get_word_pairs(string $string): array {
    static $pairs = null;
    if ($pairs !== null) return $pairs;

    $words = array_filter(get_unique_words($string), fn($w) => mb_strlen($w) > 2);
    usort($words, fn($a, $b) => mb_strlen($a) <=> mb_strlen($b));

    $pairs = [];
    $count = count($words);

    for ($i = 0; $i < $count; $i++) {
        $inner = $words[$i];
        for ($j = $i+1; $j < $count; $j++) {
            $outer = $words[$j];
            // csak akkor vizsgáljuk, ha az outer hosszabb
            if (mb_strlen($outer) <= mb_strlen($inner)) continue;

            if (mb_strpos($outer, $inner) !== false) {
                $pairs[] = [$inner, $outer];
                if (count($pairs) > 20000) { // biztonsági limit
                    return $pairs;
                }
            }
        }
    }
    return $pairs;
}


function highlight_words_amp_safe(string $string, string $txtFile): string {
    // Cache betöltés
    $cache = load_cache($txtFile);

    if ($cache) {
        $repeated = $cache['repeated'];
        $pairs    = $cache['pairs'];
    } else {
        $repeated = get_repeated_words($string);
        $pairs    = get_word_pairs($string);
        save_cache($txtFile, [
            'unique'   => get_unique_words($string),
            'repeated' => $repeated,
            'pairs'    => $pairs
        ]);
    }

    $tokens = preg_split('/(\s+)/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);

    foreach ($tokens as &$token) {
        $clean = mb_strtolower(preg_replace('/[[:punct:]]+/u', '', $token));

        if ($clean && in_array($clean, $repeated, true)) {
            $token = '<span class="repeated">'.$token.'</span>';
            continue;
        }

        foreach ($pairs as [$inner, $outer]) {
            if (mb_strtolower($token) === $outer) {
                $token = preg_replace(
                    '/(' . preg_quote($inner, '/') . ')/ui',
                    '<span class="embedded">$1</span>',
                    $token
                );
            }
        }
    }
    unset($token);

    return implode('', $tokens);
}
