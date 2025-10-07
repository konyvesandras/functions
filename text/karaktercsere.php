<?php
function karaktercsere_folio(string $text): string {
    $csere = ['¦'=>'ī','Ą'=>'ṛ','Ý'=>'Ā','±'=>'ṭ','Ł'=>'ṇ','ˇ'=>'ū','°'=>'ṁ','¤'=>'ḥ','§'=>'ā' ,'—'=>' — ','  '=>' ', 'Ş'=>'ś', 'ń'=>'ñ', '˘'=>'ṣ', 'Ż'=>'ṅ', '¥¢'=>'ṅ', 'Ľ'=>'Ś', '¼'=>'Ś', 'ľ'=>'Ī', 'ª'=>'ś', '¢'=>'ṣ', '£'=>'ṇ', '¨'=>'ḍ', '¤'=>'ḥ' ];
    foreach ($csere as $kulcs => $ertek) {
        $text = str_replace($kulcs, $ertek, $text);
    }
	
if ((strpos($text, "§")>-1) and (containsDiacriticCharacter($text)))	
	$text=str_replace ('§','ā',$text);

    return $text;
}

function containsDiacriticCharacter(string $string): bool
{
    // Unicode-karakterosztály a kívánt jelöltekhez
    $pattern = '/[āīūṛṝḷṅñṭḍṇśṣḥṁ]/u';

    // preg_match visszaadja a találatok számát (0 vagy 1)
    return (bool) preg_match($pattern, $string);
}
