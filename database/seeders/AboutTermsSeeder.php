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
                    'en' => '<p>
                        </p><h1>About Us</h1><p>
                        </p><p>Welcome to i-Racket, how ranking should be and a little bit more.</p><p>
                        </p><p>We are passionate table tennis players who have created this app to gather and strengthen the table tennis community throughout Sweden. i-Racket is your ultimate companion for everything related to table tennis ranking, whether you are a beginner or an experienced player.</p><p>

                        </p><h2>Our Vision</h2><p>
                        </p><p>We aim to promote and develop table tennis in Sweden by providing a platform that enables community, competition, ranking, and engagement. Our vision is to highlight table tennis with a focus on ranking, thereby encouraging growth within the sport.</p><p>

                        </p><h2>What i-Racket Offers</h2><p>
                        </p><p><br></p><p>

                        </p><h2>Our Team</h2><p>
                        </p><p>i-Racket is created by a dedicated team of table tennis enthusiasts. We are passionate about the sport and about creating a dynamic and user-friendly platform that helps you improve your game and experience the joy of table tennis.</p><p>

                        </p><h2>Contact</h2><p>
                        </p><p>Do you have questions, suggestions, or just want to say hello? Don\'t hesitate to contact us at <a href="mailto:info@iracket.se">info@iracket.se</a>.</p><p>
                    </p>',
                    'sv' => '<p>
                        </p><h1>Om Oss                  </h1><p>Välkommen till i-Racket, som ranking borde vara och lite mer.</p><p>                       </p><p>Vi är passionerade bordtennisspelare som har skapat denna app för att samla och stärka bordtennisgemenskapen i hela Sverige. i-Racket är din ultimata följeslagare för allt som rör bordtennisranking, oavsett om du är en nybörjare eller en erfaren spelare.</p><p>
                        </p><h2>Vår Vision                        </h2><p>Vi strävar efter att främja och utveckla bordtennis i Sverige genom att erbjuda en plattform som möjliggör gemenskap, tävling, ranking och engagemang. Vår vision är att lyfta fram bordtennisen med fokus på ranking och på så sätt uppmuntra tillväxten inom sporten.</p><p>
                        </p><h2>Vad i-Racket Erbjuder</h2><p>                                             </p><h2>Vårt Team                   </h2><p>i-Racket är skapat av ett dedikerat team av bordtennisentusiaster. Vi brinner för sporten och för att skapa en dynamisk och användarvänlig plattform som hjälper dig att förbättra ditt spel och uppleva glädjen i bordtennis.</p><p>                     </p><h2>Kontakt</h2><p>
                        </p><p>Har du frågor, förslag eller vill bara säga hej? Tveka inte att kontakta oss på <a href="mailto:info@iracket.se">info@iracket.se</a></p><p>
                    </p>',
                ],
                'is_active' => true,
            ]
        );
    }
}
