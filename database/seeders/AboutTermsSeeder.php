<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class AboutTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Term::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => [
                    'en' => 'About Us',
                    'sv' => 'Om Oss',
                ],
                'content' => [
                    'en' => '
                        <h1>About Us</h1>
                        <p>Welcome to i-Racket, how ranking should be and a little bit more.</p>
                        <p>We are passionate table tennis players who have created this app to gather and strengthen the table tennis community throughout Sweden. i-Racket is your ultimate companion for everything related to table tennis ranking, whether you are a beginner or an experienced player.</p>

                        <h2>Our Vision</h2>
                        <p>We aim to promote and develop table tennis in Sweden by providing a platform that enables community, competition, ranking, and engagement. Our vision is to highlight table tennis with a focus on ranking, thereby encouraging growth within the sport.</p>

                        <h2>What i-Racket Offers</h2>
                        <ul>
                            <li><strong>Real-time ranking:</strong> Every match you publish generates your new ranking. You can immediately see how you can climb in the ranking or fight to maintain your position.</li>
                            <li><strong>Monthly ranking:</strong> We retrieve and ensure your ranking from the official site.</li>
                            <li><strong>Follow your favorite players:</strong> Follow your favorite players and friends. Get updates on their latest matches and successes.</li>
                            <li><strong>Take notes on your own matches:</strong> Take notes on played matches and note your opponents strengths and weaknesses. Next time you meet, you can use this information to avoid repeating earlier mistakes.</li>
                            <li><strong>Rising stars:</strong> We highlight the most promising players and the most prominent clubs in the country.</li>
                            <li><strong>Follow your child:</strong> As a parent, you can follow your child and their friends. You can see their development and any awards they may have won.</li>
                        </ul>

                        <h2>Our Team</h2>
                        <p>i-Racket is created by a dedicated team of table tennis enthusiasts. We are passionate about the sport and about creating a dynamic and user-friendly platform that helps you improve your game and experience the joy of table tennis.</p>

                        <h2>Contact</h2>
                        <p>Do you have questions, suggestions, or just want to say hello? Don\'t hesitate to contact us at <a href="mailto:info@iracket.se">info@iracket.se</a>.</p>
                    ',
                    'sv' => '
                        <h1>Om Oss</h1>
                        <p>Välkommen till i-Racket, som ranking borde vara och lite mer.</p>
                        <p>Vi är passionerade bordtennisspelare som har skapat denna app för att samla och stärka bordtennisgemenskapen i hela Sverige. i-Racket är din ultimata följeslagare för allt som rör bordtennisranking, oavsett om du är en nybörjare eller en erfaren spelare.</p>

                        <h2>Vår Vision</h2>
                        <p>Vi strävar efter att främja och utveckla bordtennis i Sverige genom att erbjuda en plattform som möjliggör gemenskap, tävling, ranking och engagemang. Vår vision är att lyfta fram bordtennisen med fokus på ranking och på så sätt uppmuntra tillväxten inom sporten.</p>

                        <h2>Vad i-Racket Erbjuder</h2>
                        <ul>
                            <li><strong>Ranking i realtid:</strong> Varje match som du själv publicerar genererar din nya ranking. Du ser direkt hur du kan klättra i rankingen eller kämpa för att behålla din position.</li>
                            <li><strong>Månatlig ranking:</strong> Vi hämtar och säkerställer din ranking från det officiella organet inom branschen.</li>
                            <li><strong>Följ dina Favoritspelare:</strong> Följ dina favoritspelare och vänner. Få uppdateringar om deras senaste matcher och framgångar.</li>
                            <li><strong>Registrera dina egna matcher:</strong> Registrera dina egna spelade matcher, och notera deras styrkor och svagheter. Nästa gång ni möts kan du utnyttja denna information.</li>
                            <li><strong>Bubblare:</strong> Vi lyfter fram de mest lovande spelarna och de mest framstående klubbarna i landet.</li>
                            <li><strong>Följ ditt barn:</strong> Som förälder kan du följa ditt barn och deras vänner. Du kan se deras utveckling och eventuella vunna utmärkelser.</li>
                        </ul>

                        <h2>Vårt Team</h2>
                        <p>i-Racket är skapat av ett dedikerat team av bordtennisentusiaster. Vi brinner för sporten och för att skapa en dynamisk och användarvänlig plattform som hjälper dig att förbättra ditt spel och uppleva glädjen i bordtennis.</p>

                        <h2>Kontakt</h2>
                        <p>Har du frågor, förslag eller vill bara säga hej? Tveka inte att kontakta oss på <a href="mailto:info@iracket.se">info@iracket.se</a></p>
                    ',
                ],
                'is_active' => true,
            ]
        );
    }
}
