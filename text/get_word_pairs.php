<?php
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

$string = "alma almás almamag magos mag";
print_r(get_word_pairs($string));
/* Kimenet:
Array (
  [0] => Array ( [0] => alma [1] => almás )
  [1] => Array ( [0] => alma [1] => almamag )
  [2] => Array ( [0] => mag [1] => almamag )
  [3] => Array ( [0] => mag [1] => magos )
)
*/
