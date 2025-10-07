<pre>
<?php

function get_unique_words(string $string): array {
    // Szavak kiszedése, kisbetűsítés, nem-alfanumerikus karakterek mentén
    $words = preg_split('/\W+/u', mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);

    // Egyedi szavak visszaadása
    return array_values(array_unique($words));
}


function get_words_containing_others(string $string): array {
    $words = get_unique_words($string);
    $filtered = array_filter($words, fn($w) => mb_strlen($w) > 3);
    $containers = [];

    foreach ($filtered as $outer) {
        foreach ($words as $inner) {
            if ($outer !== $inner && mb_strpos($outer, $inner) !== false) {
                $containers[] = $outer;
                break; // Már találtunk benne egy másik szót
            }
        }
    }

    return array_values(array_unique($containers));
}


$string = "alma almás almamag magos mag";
print_r(get_words_containing_others($string));
// Kimenet: Array ( [0] => almás [1] => almamag [2] => magos )
