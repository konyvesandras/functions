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


function group_pairs_by_outer(string $string): array {
    $pairs = get_word_pairs($string);
    $grouped = [];

    foreach ($pairs as [$inner, $outer]) {
        $grouped[$outer][] = $inner;
    }

    return $grouped;
}

$string = "alma almás almamag magos mag";
print_r(group_pairs_by_outer($string));
/* Kimenet:
Array (
  [almás]    => Array ( [0] => alma )
  [almamag]  => Array ( [0] => alma [1] => mag )
  [magos]    => Array ( [0] => mag )
)
*/
/**
 * Csoportosítja a beágyazott ↔ befogadó szó-párokat a befogadó szó szerint.
 *
 * @param string $string A vizsgált szöveg.
 * @return array Tömb, ahol a kulcs a befogadó szó, az érték pedig a beágyazott szavak tömbje.
 */
