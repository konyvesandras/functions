<?php
/**
 * inc/functions.php
 * Egységes, Unicode-biztos szövegfeldolgozó és kiemelő függvények
 */

/**
 * Egyedi szavak kigyűjtése (kisbetűsítve).
 */
function get_unique_words(string $string): array {

// Régi (memóriát zabálhat):
// $words = preg_split('/\W+/u', mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);

// Új, biztonságosabb:
$cleaned = preg_replace('/[[:punct:]]+/u', ' ', $string);
$words   = preg_split('/\s+/u', mb_strtolower($cleaned), -1, PREG_SPLIT_NO_EMPTY);

//    $words = preg_split('/\W+/u', mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_unique($words));
}

/**
 * Ismétlődő szavak kigyűjtése.
 */
function get_repeated_words(string $string): array {
    $words = preg_split('/\W+/u', mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);
    $counts = array_count_values($words);
    return array_keys(array_filter($counts, fn($c) => $c > 1));
}

/**
 * Beágyazott ↔ befogadó párok kigyűjtése.
 */
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

/**
 * Biztonságos, AMP-kompatibilis kiemelés:
 * - ismétlődő szavak teljes kiemelése
 * - beágyazott szavak részleges kiemelése
 */
function highlight_words_amp_safe(string $string): string {
    $repeated = get_repeated_words($string);
    $pairs    = get_word_pairs($string);

    // Szavak + whitespace megtartása
    $tokens = preg_split('/(\s+)/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);

    foreach ($tokens as &$token) {
        $clean = mb_strtolower(preg_replace('/\W+/u', '', $token));

        // Ismétlődő szavak kiemelése
        if ($clean && in_array($clean, $repeated, true)) {
            $token = '<span class="repeated">'.$token.'</span>';
            continue;
        }

        // Beágyazott szavak részleges kiemelése
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
