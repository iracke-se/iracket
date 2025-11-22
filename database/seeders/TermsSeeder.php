<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Term::updateOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'title' => 'Terms and Conditions',
                'content' => '
                <h1>Terms and Conditions</h1>
                <p><strong>Last updated:</strong> November 22, 2025</p>

                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using iRacket, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

                <h2>2. Use License</h2>
                <p>Permission is granted to temporarily use iRacket for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                <ul>
                    <li>modify or copy the materials;</li>
                    <li>use the materials for any commercial purpose, or for any public display (commercial or non-commercial);</li>
                    <li>attempt to decompile or reverse engineer any software contained on iRacket;</li>
                    <li>remove any copyright or other proprietary notations from the materials; or</li>
                    <li>transfer the materials to another person or "mirror" the materials on any other server.</li>
                </ul>

                <h2>3. User Account</h2>
                <p>To use certain features of iRacket, you must register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>

                <h2>4. User Conduct</h2>
                <p>You agree not to use iRacket to:</p>
                <ul>
                    <li>Upload, post, or transmit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or otherwise objectionable;</li>
                    <li>Impersonate any person or entity;</li>
                    <li>Upload, post, or transmit any content that infringes any patent, trademark, trade secret, copyright, or other proprietary rights;</li>
                    <li>Upload, post, or transmit any unsolicited or unauthorized advertising, promotional materials, or any other form of solicitation.</li>
                </ul>

                <h2>5. Disclaimer</h2>
                <p>The materials on iRacket are provided on an "as is" basis. iRacket makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

                <h2>6. Limitations</h2>
                <p>In no event shall iRacket or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use iRacket.</p>

                <h2>7. Modifications</h2>
                <p>iRacket may revise these terms of service at any time without notice. By using this application you are agreeing to be bound by the then current version of these terms of service.</p>

                <h2>8. Governing Law</h2>
                <p>These terms and conditions are governed by and construed in accordance with the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>

                <h2>9. Contact Us</h2>
                <p>If you have any questions about these Terms and Conditions, please contact us at support@iracket.com.</p>
            ',
                'is_active' => true,
            ]
        );

        Term::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'content' => '
                <h1>Privacy Policy</h1>
                <p><strong>Last updated:</strong> November 22, 2025</p>

                <h2>1. Introduction</h2>
                <p>Welcome to iRacket. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data and tell you about your privacy rights.</p>

                <h2>2. Information We Collect</h2>
                <p>We may collect, use, store and transfer different kinds of personal data about you which we have grouped together as follows:</p>
                <ul>
                    <li><strong>Identity Data</strong> includes first name, last name, username or similar identifier.</li>
                    <li><strong>Contact Data</strong> includes email address and telephone numbers.</li>
                    <li><strong>Technical Data</strong> includes internet protocol (IP) address, your login data, browser type and version, time zone setting and location.</li>
                    <li><strong>Profile Data</strong> includes your username and password, your interests, preferences, feedback and survey responses.</li>
                    <li><strong>Usage Data</strong> includes information about how you use our website and services.</li>
                </ul>

                <h2>3. How We Use Your Information</h2>
                <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                <ul>
                    <li>To register you as a new customer.</li>
                    <li>To process and deliver your service.</li>
                    <li>To manage our relationship with you.</li>
                    <li>To improve our website, products/services, marketing or customer relationships.</li>
                    <li>To recommend products or services which may be of interest to you.</li>
                </ul>

                <h2>4. Data Security</h2>
                <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed. In addition, we limit access to your personal data to those employees, agents, contractors and other third parties who have a business need to know.</p>

                <h2>5. Data Retention</h2>
                <p>We will only retain your personal data for as long as necessary to fulfil the purposes we collected it for, including for the purposes of satisfying any legal, accounting, or reporting requirements.</p>

                <h2>6. Your Legal Rights</h2>
                <p>Under certain circumstances, you have rights under data protection laws in relation to your personal data, including the right to:</p>
                <ul>
                    <li>Request access to your personal data.</li>
                    <li>Request correction of your personal data.</li>
                    <li>Request erasure of your personal data.</li>
                    <li>Object to processing of your personal data.</li>
                    <li>Request restriction of processing your personal data.</li>
                    <li>Request transfer of your personal data.</li>
                    <li>Right to withdraw consent.</li>
                </ul>

                <h2>7. Third-Party Links</h2>
                <p>This website may include links to third-party websites, plug-ins and applications. Clicking on those links or enabling those connections may allow third parties to collect or share data about you. We do not control these third-party websites and are not responsible for their privacy statements.</p>

                <h2>8. Cookies</h2>
                <p>We use cookies to distinguish you from other users of our website. This helps us to provide you with a good experience when you browse our website and also allows us to improve our site.</p>

                <h2>9. Changes to This Privacy Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

                <h2>10. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us at privacy@iracket.com.</p>
            ',
                'is_active' => true,
            ]
        );
    }
}
