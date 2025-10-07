<?php
function get_unique_words(string $string): array {
    // Szavak kiszedése, kisbetűsítés, nem-alfanumerikus karakterek mentén
    $words = preg_split('/\W+/u', mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);

    // Egyedi szavak visszaadása
    return array_values(array_unique($words));
}

function get_word_pairs(string $string): array {
    $words = get_unique_words($string);
    $filtered = array_filter($words, fn($w) => mb_strlen($w) > 3);
    $pairs = [];

    foreach ($filtered as $inner) {
        foreach ($words as $outer) {
            if ($inner !== $outer && mb_strpos($outer, $inner) !== false) {
                $pairs[] = [$inner, $outer];
            }
        }
    }

    return $pairs;
}



function group_pairs_by_inner(string $string): array {
    $pairs = get_word_pairs($string);
    $grouped = [];

    foreach ($pairs as [$inner, $outer]) {
        $grouped[$inner][] = $outer;
    }

    return $grouped;
}


function render_grouped_pairs(array $grouped, string $mode = 'inner'): string {
    $html = "<ul>\n";

    foreach ($grouped as $key => $values) {
        $label = ($mode === 'inner') ? "Beágyazott: " : "Befogadó: ";
        $html .= "  <li><strong>{$label}{$key}</strong>: " . implode(', ', $values) . "</li>\n";
    }

    $html .= "</ul>";
    return $html;
}

$string = "alma almás almamag magos mag";
$grouped = group_pairs_by_inner($string);
echo render_grouped_pairs($grouped, 'inner');
