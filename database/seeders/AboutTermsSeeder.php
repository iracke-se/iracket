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
            ['slug' => 'about'],
            [
                'title' => 'About Us',
                'content' => '
                    <h1>About Us</h1>
                    <p>Welcome to i-Racket, what rankings should be and a little more.</p>

                    <p>We are passionate table tennis players who have created this app to bring together and strengthen the table tennis community throughout Sweden. i-Racket is your ultimate companion for everything related to table tennis rankings, whether you are a beginner or an experienced player.</p>

                    <h2>Our Vision</h2>
                    <p>We strive to promote and develop table tennis in Sweden by offering a platform that enables community, competition, ranking and engagement. Our vision is to highlight table tennis with a focus on ranking and thus encourage growth in the sport.</p>

                    <h2>What i-Racket Offers</h2>
                    <ul>
                        <li><strong>Real-time rankings:</strong> Every match you publish generates your new ranking. You can immediately see how you can climb the rankings or fight to maintain your position.</li>
                        <li><strong>Monthly Rankings:</strong> We retrieve and secure your ranking from the official industry body.</li>
                        <li><strong>Follow your Favorite Players:</strong> Follow your favorite players and friends. Get updates on their latest matches and achievements.</li>
                        <li><strong>Record your own matches:</strong> Record your own matches played, and note their strengths and weaknesses. The next time you meet, you can use this information.</li>
                        <li><strong>Bubbler:</strong> We highlight the most promising players and the most prominent clubs in the country.</li>
                        <li><strong>Follow your child:</strong> As a parent, you can follow your child and their friends. You can see their progress and any awards they have won.</li>
                    </ul>

                    <h2>Our Team</h2>
                    <p>i-Racket is created by a dedicated team of table tennis enthusiasts. We are passionate about the sport and about creating a dynamic and user-friendly platform that helps you improve your game and experience the joy of table tennis.</p>

                    <h2>Contact</h2>
                    <p>Do you have any questions, suggestions or just want to say hello? Don\'t hesitate to contact us at <a href="mailto:info@iracket.se">info@iracket.se</a></p>
                ',
                'is_active' => true,
            ]
        );
    }
}
