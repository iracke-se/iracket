<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class BubblerTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Term::updateOrCreate(
            ['slug' => 'bubbler'],
            [
                'title' => [
                    'en' => 'What is Bubblare?',
                    'sv' => 'Vad är Bubblare?',
                ],
                'content' => [
                    'en' => '
                        <h1>What is Bubblare?</h1>
                        <p>Bubblers are about who has been the best in the previous month. How do you know if you have been the best? Well, it\'s about who got the most points in the previous month, and that\'s the most points in their interval.</p>

                        <p>The intervals are: 0–499, 500–999, 1000–1249, 1250–1499, 1500–1749, 1750–1999, 2000–2249, 2250+.</p>

                        <p>We show the top three in each segment and region. The default is on your own range and region.</p>

                        <h2>Intervals for Women</h2>
                        <ul>
                            <li><strong>Elite class:</strong> at least 1750 points</li>
                            <li><strong>Class 1:</strong> 1500–1749 points</li>
                            <li><strong>Class 2:</strong> 1250–1499 points</li>
                            <li><strong>Class 3:</strong> 1000–1249 points</li>
                            <li><strong>Class 4:</strong> 750–999 points</li>
                            <li><strong>Class 5:</strong> 0–749 points</li>
                        </ul>

                        <h2>Intervals for Men</h2>
                        <ul>
                            <li><strong>Elite:</strong> 2250+ points</li>
                            <li><strong>Class 1:</strong> 2000–2249 points</li>
                            <li><strong>Class 2:</strong> 1750–1999 points</li>
                            <li><strong>Class 3:</strong> 1500–1749 points</li>
                            <li><strong>Class 4:</strong> 1250–1499 points</li>
                            <li><strong>Class 5:</strong> 1000–1249 points</li>
                            <li><strong>Class 6:</strong> 750–999 points</li>
                            <li><strong>Class 7:</strong> 0–749 points</li>
                        </ul>

                        <p>If you get, for example, 100 points a month and step over the interval, you will be shown in your previous interval.</p>

                        <p>Bubblers are simply different listings based on rankings, combined with gender, age, geography, etc. We want to build a number of listings that we post on different pages.</p>

                        <h2>How Points are Calculated</h2>
                        <p>When a user enters new match stats, the system implements the following logic:</p>
                        <ol>
                            <li>Calculate the ranking points difference between the players that play the match.</li>
                            <li>Check in which ranking point difference range the result belongs to.</li>
                            <li>Check which player is the winner and whether it was with higher ranking or lower ranking points, prior to the match.</li>
                            <li>Increase and decrease points accordingly to the winner and loser of the match, based on the official numbers below.</li>
                        </ol>

                        <table>
                            <thead>
                                <tr>
                                    <th>Point Difference</th>
                                    <th>Higher Ranked Wins</th>
                                    <th>Lower Ranked Wins</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>0 – 25</td><td>+10 points</td><td>+10 points</td></tr>
                                <tr><td>26 – 50</td><td>+9 points</td><td>+11 points</td></tr>
                                <tr><td>51 – 75</td><td>+8 points</td><td>+12 points</td></tr>
                                <tr><td>76 – 100</td><td>+7 points</td><td>+13 points</td></tr>
                                <tr><td>101 – 125</td><td>+6 points</td><td>+15 points</td></tr>
                                <tr><td>126 – 150</td><td>+6 points</td><td>+16 points</td></tr>
                                <tr><td>151 – 200</td><td>+5 points</td><td>+17 points</td></tr>
                                <tr><td>201 – 250</td><td>+4 points</td><td>+18 points</td></tr>
                                <tr><td>251 – 300</td><td>+3 points</td><td>+19 points</td></tr>
                                <tr><td>301 – 400</td><td>+2 points</td><td>+20 points</td></tr>
                                <tr><td>401 – 500</td><td>+2 points</td><td>+30 points</td></tr>
                                <tr><td>501+</td><td>+2 points</td><td>+40 points</td></tr>
                            </tbody>
                        </table>

                        <p>The winner receives plus points and the loser the same number of minus points according to the table.</p>

                        <h2>Example</h2>
                        <p><strong>Player 1:</strong> 1100 points<br><strong>Player 2:</strong> 1300 points</p>
                        <p>Difference = 1300 − 1100 = <strong>200 points</strong> → falls in the 151–200 range.</p>
                        <ul>
                            <li><strong>If Player 1 wins:</strong> Player 1 → 1117 points (+17), Player 2 → 1283 points (−17)</li>
                            <li><strong>If Player 2 wins:</strong> Player 1 → 1095 points (−5), Player 2 → 1305 points (+5)</li>
                        </ul>
                    ',
                    'sv' => '
                        <h1>Vad är Bubblare?</h1>
                        <p>Bubblare handlar om vem som har varit bäst den föregående månaden. Hur vet du om du har varit bäst? Jo, det handlar om vem som fick flest poäng föregående månad, och det är flest poäng inom sitt intervall.</p>

                        <p>Intervallen är: 0–499, 500–999, 1000–1249, 1250–1499, 1500–1749, 1750–1999, 2000–2249, 2250+.</p>

                        <p>Vi visar de tre bästa i varje segment och region. Standard är inom ditt eget intervall och din region.</p>

                        <h2>Intervall för Damer</h2>
                        <ul>
                            <li><strong>Elitklass:</strong> minst 1750 poäng</li>
                            <li><strong>Klass 1:</strong> 1500–1749 poäng</li>
                            <li><strong>Klass 2:</strong> 1250–1499 poäng</li>
                            <li><strong>Klass 3:</strong> 1000–1249 poäng</li>
                            <li><strong>Klass 4:</strong> 750–999 poäng</li>
                            <li><strong>Klass 5:</strong> 0–749 poäng</li>
                        </ul>

                        <h2>Intervall för Herrar</h2>
                        <ul>
                            <li><strong>Elite:</strong> 2250+ poäng</li>
                            <li><strong>Klass 1:</strong> 2000–2249 poäng</li>
                            <li><strong>Klass 2:</strong> 1750–1999 poäng</li>
                            <li><strong>Klass 3:</strong> 1500–1749 poäng</li>
                            <li><strong>Klass 4:</strong> 1250–1499 poäng</li>
                            <li><strong>Klass 5:</strong> 1000–1249 poäng</li>
                            <li><strong>Klass 6:</strong> 750–999 poäng</li>
                            <li><strong>Klass 7:</strong> 0–749 poäng</li>
                        </ul>

                        <p>Om du till exempel får 100 poäng en månad och kliver över intervallet, visas du i ditt tidigare intervall.</p>

                        <p>Bubblare är helt enkelt olika listor baserade på rankingar kombinerat med kön, ålder, geografi m.m. Vi vill bygga ett antal listor som vi publicerar på olika sidor.</p>

                        <h2>Hur Poäng Beräknas</h2>
                        <p>När en användare registrerar ny matchstatistik implementerar systemet följande logik:</p>
                        <ol>
                            <li>Beräkna rankingpoängskillnaden mellan spelarna som spelar matchen.</li>
                            <li>Kontrollera vilket poängskillnadsintervall resultatet tillhör.</li>
                            <li>Kontrollera vilken spelare som är vinnare och om det var med högre eller lägre rankingpoäng före matchen.</li>
                            <li>Öka och minska poängen för vinnaren och förloraren enligt den officiella tabellen nedan.</li>
                        </ol>

                        <table>
                            <thead>
                                <tr>
                                    <th>Poängskillnad</th>
                                    <th>Högre rankad vinner</th>
                                    <th>Lägre rankad vinner</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>0 – 25</td><td>+10 poäng</td><td>+10 poäng</td></tr>
                                <tr><td>26 – 50</td><td>+9 poäng</td><td>+11 poäng</td></tr>
                                <tr><td>51 – 75</td><td>+8 poäng</td><td>+12 poäng</td></tr>
                                <tr><td>76 – 100</td><td>+7 poäng</td><td>+13 poäng</td></tr>
                                <tr><td>101 – 125</td><td>+6 poäng</td><td>+15 poäng</td></tr>
                                <tr><td>126 – 150</td><td>+6 poäng</td><td>+16 poäng</td></tr>
                                <tr><td>151 – 200</td><td>+5 poäng</td><td>+17 poäng</td></tr>
                                <tr><td>201 – 250</td><td>+4 poäng</td><td>+18 poäng</td></tr>
                                <tr><td>251 – 300</td><td>+3 poäng</td><td>+19 poäng</td></tr>
                                <tr><td>301 – 400</td><td>+2 poäng</td><td>+20 poäng</td></tr>
                                <tr><td>401 – 500</td><td>+2 poäng</td><td>+30 poäng</td></tr>
                                <tr><td>501+</td><td>+2 poäng</td><td>+40 poäng</td></tr>
                            </tbody>
                        </table>

                        <p>Vinnaren får pluspoäng och förloraren samma antal minuspoäng enligt tabellen.</p>

                        <h2>Exempel</h2>
                        <p><strong>Spelare 1:</strong> 1100 poäng<br><strong>Spelare 2:</strong> 1300 poäng</p>
                        <p>Skillnad = 1300 − 1100 = <strong>200 poäng</strong> → faller i intervallet 151–200.</p>
                        <ul>
                            <li><strong>Om Spelare 1 vinner:</strong> Spelare 1 → 1117 poäng (+17), Spelare 2 → 1283 poäng (−17)</li>
                            <li><strong>Om Spelare 2 vinner:</strong> Spelare 1 → 1095 poäng (−5), Spelare 2 → 1305 poäng (+5)</li>
                        </ul>
                    ',
                ],
                'is_active' => true,
            ]
        );
    }
}
