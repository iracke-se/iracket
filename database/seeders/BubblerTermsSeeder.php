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
                'title' => 'Bubbler',
                'content' => '
                    <h1>What is Bubbler?</h1>
                    <p>Bubbler is about who was the best the previous month.</p>

                    <h2>How do you know if you\'ve been the best?</h2>
                    <p>Well, it\'s about who got the most points the previous month, and it\'s the most points in their range.</p>

                    <p>The ranges are: 0-499, 500-999, 1000-1249, 1250-1499, 1500-1749, 1750-1999, 2000-2249, 2250 and up.</p>

                    <p>We show the top three in each segment and region. The default is within your own range and region.</p>

                    <h2>Interval for Women</h2>
                    <ul>
                        <li><strong>Elite class:</strong> At least 1750 points</li>
                        <li><strong>Class 1:</strong> 1500-1749 points</li>
                        <li><strong>Class 2:</strong> 1250-1499 points</li>
                        <li><strong>Class 3:</strong> 1000-1249 points</li>
                        <li><strong>Class 4:</strong> 750-999 points</li>
                        <li><strong>Class 5:</strong> Maximum 749 points</li>
                    </ul>

                    <h2>Interval for Men</h2>
                    <ul>
                        <li><strong>Elite:</strong> At least 2250 points</li>
                        <li><strong>Class 1:</strong> 2000-2249 points</li>
                        <li><strong>Class 2:</strong> 1750-1999 points</li>
                        <li><strong>Class 3:</strong> 1500-1749 points</li>
                        <li><strong>Class 4:</strong> 1250-1499 points</li>
                        <li><strong>Class 5:</strong> 1000-1249 points</li>
                        <li><strong>Class 6:</strong> 750-999 points</li>
                        <li><strong>Class 7:</strong> Maximum 749 points</li>
                    </ul>

                    <p>If you get, for example, 100 points one month and go over the range, you will appear in your previous range.</p>

                    <p>Bubblers are simply different lists based on rankings combined with gender, age, geography, etc. We want to build a number of lists that we publish on different pages.</p>

                    <h2>How Points are Calculated</h2>
                    <p>When a user enters new match statistics, the system implements the following logic:</p>
                    <ol>
                        <li>Calculate the ranking point difference between the players playing the match.</li>
                        <li>Check within which ranking score difference interval the result belongs.</li>
                        <li>Check which player is the winner and whether it was with higher ranking points or lower ranking points, before the match.</li>
                        <li>Increase and decrease the points according to the winner and loser of the match, based on the official figures below.</li>
                    </ol>

                    <h2>Points Table</h2>
                    <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                        <thead>
                            <tr style="border-bottom: 2px solid #404040;">
                                <th style="text-align: left; padding: 0.75rem; color: #fafafa;">Point Difference</th>
                                <th style="text-align: left; padding: 0.75rem; color: #fafafa;">Higher Ranked Wins</th>
                                <th style="text-align: left; padding: 0.75rem; color: #fafafa;">Lower Ranked Wins</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">0 - 25</td>
                                <td style="padding: 0.75rem;">10 points</td>
                                <td style="padding: 0.75rem;">10 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">26 - 50</td>
                                <td style="padding: 0.75rem;">9 points</td>
                                <td style="padding: 0.75rem;">11 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">51 - 75</td>
                                <td style="padding: 0.75rem;">8 points</td>
                                <td style="padding: 0.75rem;">12 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">76 - 100</td>
                                <td style="padding: 0.75rem;">7 points</td>
                                <td style="padding: 0.75rem;">13 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">101 - 125</td>
                                <td style="padding: 0.75rem;">6 points</td>
                                <td style="padding: 0.75rem;">15 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">126 - 150</td>
                                <td style="padding: 0.75rem;">6 points</td>
                                <td style="padding: 0.75rem;">16 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">151 - 200</td>
                                <td style="padding: 0.75rem;">5 points</td>
                                <td style="padding: 0.75rem;">17 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">201 - 250</td>
                                <td style="padding: 0.75rem;">4 points</td>
                                <td style="padding: 0.75rem;">18 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">251 - 300</td>
                                <td style="padding: 0.75rem;">3 points</td>
                                <td style="padding: 0.75rem;">19 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">301 - 400</td>
                                <td style="padding: 0.75rem;">2 points</td>
                                <td style="padding: 0.75rem;">20 points</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #404040;">
                                <td style="padding: 0.75rem;">401 - 500</td>
                                <td style="padding: 0.75rem;">2 points</td>
                                <td style="padding: 0.75rem;">30 points</td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem;">501 and up</td>
                                <td style="padding: 0.75rem;">2 points</td>
                                <td style="padding: 0.75rem;">40 points</td>
                            </tr>
                        </tbody>
                    </table>

                    <p>The winner receives plus points and the loser receives the same number of minus points according to the table.</p>

                    <h2>Example</h2>
                    <p><strong>Player 1:</strong> 1100 points<br>
                    <strong>Player 2:</strong> 1300 points</p>

                    <p>The difference in points = 1300 - 1100 = <strong>200 points</strong></p>

                    <p>Based on the table above (151-200 range):</p>
                    <ul>
                        <li><strong>If Player 1 wins:</strong> Player 1 will have 1117 points (+17) and Player 2 will have 1283 points (-17)</li>
                        <li><strong>If Player 2 wins:</strong> Player 1 will have 1095 points (-5) and Player 2 will have 1305 points (+5)</li>
                    </ul>

                    <p>Bubblers are lists based on different listings. We place different lists on various pages throughout the application.</p>
                ',
                'is_active' => true,
            ]
        );
    }
}
