<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class MatchesTermsSeeder extends Seeder
{
    public function run(): void
    {
        Term::updateOrCreate(
            ['slug' => 'matches'],
            [
                'title' => [
                    'en' => 'Matches',
                    'sv' => 'Matcher',
                ],
                'content' => [
                    'en' => '<p>Easily register matches you have played and get your updated ranking instantly.</p><p>In addition to the result, you can also add personal notes after each match. Save your thoughts about the opponent and the game, such as strengths, playing style, or what you can improve next time.</p><p>When you face the same player again, you have all the information in one place, giving you a clear advantage and better conditions to improve.</p>',
                    'sv' => '<p>Registrera enkelt matcher du har spelat och få din uppdaterade ranking direkt.</p><p>Utöver resultatet kan du även skriva egna anteckningar efter matchen. Spara dina tankar om motståndaren och spelet, till exempel vad spelaren är bra på, styrkor i spelet eller vad du själv kan göra bättre nästa gång.</p><p>När du möter samma spelare igen har du all information samlad, vilket ger dig ett tydligt försprång och bättre förutsättningar att utvecklas.</p>',
                ],
                'is_active' => true,
            ]
        );
    }
}
