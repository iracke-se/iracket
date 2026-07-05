<?php

namespace Database\Seeders;

use App\Models\GuideSection;
use Illuminate\Database\Seeder;

class GuideSectionSeeder extends Seeder
{
    /**
     * Seed the in-app User Guide from the source documentation (EN + SV).
     * Screenshots live in public/assets/guide/ and are referenced by path.
     */
    public function run(): void
    {
        foreach ($this->sections() as $section) {
            GuideSection::updateOrCreate(
                ['slug' => $section['slug']],
                $section,
            );
        }
    }

    private function sections(): array
    {
        return [
            [
                'slug' => 'welcome',
                'icon' => 'home',
                'order' => 1,
                'is_active' => true,
                'title' => ['en' => 'Welcome', 'sv' => 'Välkommen'],
                'content' => ['en' => $this->welcomeEn(), 'sv' => $this->welcomeSv()],
            ],
            [
                'slug' => 'getting-started',
                'icon' => 'play',
                'order' => 2,
                'is_active' => true,
                'title' => ['en' => 'Getting Started', 'sv' => 'Kom igång'],
                'content' => ['en' => $this->gettingStartedEn(), 'sv' => $this->gettingStartedSv()],
            ],
            [
                'slug' => 'players',
                'icon' => 'users',
                'order' => 3,
                'is_active' => true,
                'title' => ['en' => 'Players', 'sv' => 'Spelare'],
                'content' => ['en' => $this->playersEn(), 'sv' => $this->playersSv()],
            ],
            [
                'slug' => 'bubblare',
                'icon' => 'trending-up',
                'order' => 4,
                'is_active' => true,
                'title' => ['en' => 'Bubblare', 'sv' => 'Bubblare'],
                'content' => ['en' => $this->bubblareEn(), 'sv' => $this->bubblareSv()],
            ],
            [
                'slug' => 'my-profile',
                'icon' => 'user',
                'order' => 5,
                'is_active' => true,
                'title' => ['en' => 'My Profile', 'sv' => 'Min profil'],
                'content' => ['en' => $this->myProfileEn(), 'sv' => $this->myProfileSv()],
            ],
            [
                'slug' => 'account-settings',
                'icon' => 'cog',
                'order' => 6,
                'is_active' => true,
                'title' => ['en' => 'Account Settings', 'sv' => 'Kontoinställningar'],
                'content' => ['en' => $this->accountSettingsEn(), 'sv' => $this->accountSettingsSv()],
            ],
            [
                'slug' => 'my-matches',
                'icon' => 'clipboard',
                'order' => 7,
                'is_active' => true,
                'title' => ['en' => 'My Matches', 'sv' => 'Mina matcher'],
                'content' => ['en' => $this->myMatchesEn(), 'sv' => $this->myMatchesSv()],
            ],
            [
                'slug' => 'add-manual-match',
                'icon' => 'plus',
                'order' => 8,
                'is_active' => true,
                'title' => ['en' => 'Add a Manual Match', 'sv' => 'Lägg till en manuell match'],
                'content' => ['en' => $this->manualMatchEn(), 'sv' => $this->manualMatchSv()],
            ],
            [
                'slug' => 'notifications',
                'icon' => 'bell',
                'order' => 9,
                'is_active' => true,
                'title' => ['en' => 'Notifications', 'sv' => 'Notiser'],
                'content' => ['en' => $this->notificationsEn(), 'sv' => $this->notificationsSv()],
            ],
            [
                'slug' => 'information-settings',
                'icon' => 'info',
                'order' => 10,
                'is_active' => true,
                'title' => ['en' => 'Information and Settings', 'sv' => 'Information och inställningar'],
                'content' => ['en' => $this->informationEn(), 'sv' => $this->informationSv()],
            ],
            [
                'slug' => 'faq',
                'icon' => 'question',
                'order' => 11,
                'is_active' => true,
                'title' => ['en' => 'Frequently Asked Questions', 'sv' => 'Vanliga frågor'],
                'content' => ['en' => $this->faqEn(), 'sv' => $this->faqSv()],
            ],
        ];
    }

    // ---------------------------------------------------------------- English

    private function welcomeEn(): string
    {
        return <<<'HTML'
<p>Welcome to iRacket, your companion for following Swedish table tennis rankings and tracking your own progress.</p>
<p>Whether you are an active player or simply interested in Swedish table tennis, iRacket gives you quick access to player rankings, match history, club information, and performance statistics.</p>
<h2>What is iRacket?</h2>
<p>iRacket is a mobile app for table tennis players and fans in Sweden. It helps you follow player rankings, view ranking history, monitor clubs, and keep track of matches.</p>
<p>With iRacket you can:</p>
<ul>
<li>Follow your own ranking journey.</li>
<li>Browse Swedish table tennis player rankings.</li>
<li>View detailed player profiles and ranking history.</li>
<li>Follow players you are interested in.</li>
<li>View official and manually added matches.</li>
<li>Add manual matches to estimate ranking changes.</li>
<li>Add notes and comments about matches and opponents.</li>
<li>Receive notifications when another player adds a match involving you.</li>
<li>Explore the monthly top-performing players and clubs in Bubblare.</li>
<li>View club details and club history.</li>
</ul>
<div class="guide-note"><strong>Note:</strong> Players are organized into separate Men and Women categories. Some views also include club-based information.</div>
HTML;
    }

    private function gettingStartedEn(): string
    {
        return <<<'HTML'
<h2>Create Your Account</h2>
<img src="/assets/guide/create-account.png" alt="Registration screen">
<p>Creating an iRacket account only takes a few minutes. You can register using Google, Apple, or your email address.</p>
<p>To create an account with email:</p>
<ol>
<li>Enter your first name and last name.</li>
<li>Enter your email address.</li>
<li>Create a password.</li>
<li>Confirm your password.</li>
<li>Read and accept the Terms and Conditions and Privacy Policy.</li>
<li>Tap Create Account.</li>
</ol>
<div class="guide-note"><strong>Note:</strong> If you register using your email address, iRacket sends a verification code to your inbox before you can continue.</div>
<h2>Verify Your Email</h2>
<p>After registering with your email address, check your inbox for the verification email. Enter the verification code in the app to activate your account.</p>
<p>Once your account has been verified, you can continue setting up iRacket.</p>
<h2>Connect Your Player Profile</h2>
<img src="/assets/guide/connect-player.png" alt="Connect account screen">
<p>After creating your account, choose how you want to use iRacket. You can connect your account to your official Swedish Table Tennis player profile, or continue as a guest.</p>
<h3>Option 1: Active Player</h3>
<p>If you compete in official table tennis competitions, connect your account to your official player profile.</p>
<p>To connect your profile:</p>
<ol>
<li>Enable I am an active player.</li>
<li>Tap the player selection field.</li>
<li>Search for your name.</li>
<li>Select your player profile.</li>
<li>Enable push notifications. This is recommended.</li>
<li>Tap Connect Account.</li>
</ol>
<h3>Option 2: Continue as Guest</h3>
<p>If you only want to browse rankings, players, and club information, tap Continue as Guest.</p>
<div class="guide-note"><strong>Note:</strong> Guest users can explore the app, but player-specific features are unavailable until a player profile is connected.</div>
<h2>Complete the Connection</h2>
<img src="/assets/guide/complete-connection.png" alt="Complete connection screen">
<p>Once you select your player profile, iRacket automatically displays your registered club. Review the information and tap Connect Account to complete the setup.</p>
HTML;
    }

    private function playersEn(): string
    {
        return <<<'HTML'
<h2>Browse Players</h2>
<img src="/assets/guide/browse-players.png" alt="Players list">
<p>The Players page is the main view of iRacket. Players are listed by their latest current ranking points.</p>
<p>Each player card shows:</p>
<ul><li>Player name</li><li>Club</li><li>District</li><li>Gender</li><li>Current ranking points</li></ul>
<p>Use the search field to quickly find a specific player. Tap a player to open the complete player profile.</p>
<h2>Filter Players</h2>
<img src="/assets/guide/filter-players.png" alt="Advanced filters">
<p>Use filters to narrow the player list and find relevant players faster.</p>
<p>Available filters include:</p>
<ul><li>Gender</li><li>Ranking points</li><li>District</li><li>Age</li><li>Ranking month</li><li>Sort order</li></ul>
<p>Tap Apply to update the results. Tap Clear to remove all filters.</p>
<h2>View a Player Profile</h2>
<img src="/assets/guide/player-profile.png" alt="Player profile">
<p>Selecting a player opens their full profile.</p>
<p>A player profile includes:</p>
<ul><li>Player name</li><li>Club</li><li>District</li><li>Latest ranking history</li><li>Current ranking points</li><li>Official ranking information</li></ul>
<p>The ranking table shows the month, ranking position, points, and point change.</p>
<h2>Follow a Player</h2>
<p>To keep track of another player's progress, tap Follow Player on the player's profile.</p>
<p>When you follow a player:</p>
<ul><li>The button changes to Stop Following.</li><li>The player is added to your followed players list.</li><li>You can quickly monitor their current ranking points.</li></ul>
<p>You can stop following a player at any time.</p>
HTML;
    }

    private function bubblareEn(): string
    {
        return <<<'HTML'
<p>Bubblare highlights the best-performing players and clubs from the previous month based on ranking point increases.</p>
<p>Select the year and month, then choose one of three categories:</p>
<ul><li>Women</li><li>Men</li><li>Clubs</li></ul>
<h2>Monthly Top Women</h2>
<img src="/assets/guide/bubblare-women.png" alt="Monthly top women">
<p>The Women view shows the top three players with the highest ranking point increase in each women's ranking class during the selected month.</p>
<h2>Monthly Top Men</h2>
<img src="/assets/guide/bubblare-men.png" alt="Monthly top men">
<p>The Men view shows the top three players with the highest ranking point increase in each men's ranking class during the selected month.</p>
<h2>Monthly Top Clubs</h2>
<img src="/assets/guide/bubblare-clubs.png" alt="Monthly top clubs">
<p>The Clubs view ranks clubs according to the combined ranking point increase of players from that club during the selected month.</p>
<p>Each club card shows:</p>
<ul><li>Club name</li><li>Number of players in that club</li><li>Total ranking points gained</li><li>Number of Bubblare players</li></ul>
<h2>Sort Bubblare Results</h2>
<p>Use the filter button to sort club rankings.</p>
<p>Available sorting options include:</p>
<ul><li>Ranking points gained</li><li>Number of new members</li><li>Number of Bubblare players</li></ul>
<p>Tap Done to apply the selected sorting option.</p>
HTML;
    }

    private function myProfileEn(): string
    {
        return <<<'HTML'
<h2>Profile Overview</h2>
<img src="/assets/guide/profile-overview.png" alt="Profile overview">
<p>Your Profile page gives you a summary of your activity in iRacket.</p>
<p>From your Profile page, you can:</p>
<ul><li>View your profile information.</li><li>See your latest ranking history.</li><li>Open your complete ranking history.</li><li>View players you follow.</li><li>Find new players to follow.</li><li>View your latest matches.</li><li>View your club history.</li></ul>
<h2>My Ranking</h2>
<p>Select My Ranking to view your complete ranking history.</p>
<p>For each official ranking period, iRacket displays:</p>
<ul><li>Month</li><li>Ranking position</li><li>Ranking points</li><li>Monthly point changes</li></ul>
<div class="guide-note"><strong>Note:</strong> Ranking information is based on official data from the Swedish Table Tennis Association.</div>
<h2>Follow Players from Your Profile</h2>
<img src="/assets/guide/follow-players.png" alt="Find players to follow">
<p>Select Find Players to search for players you would like to follow. Search by player name and tap Follow Player.</p>
<p>Players you follow appear on your Profile page for quick access.</p>
HTML;
    }

    private function accountSettingsEn(): string
    {
        return <<<'HTML'
<h2>Edit Your Profile</h2>
<img src="/assets/guide/edit-profile.png" alt="Edit profile">
<p>Profile settings let you update your personal information.</p>
<p>You can:</p>
<ul><li>Change your profile picture.</li><li>Update your display name.</li><li>Update your email address.</li><li>Add your phone number.</li><li>Update your gender.</li><li>Update your age.</li></ul>
<div class="guide-note"><strong>Note:</strong> If your account is connected to an official SBTF player profile, your player name is synchronized automatically and cannot be edited.</div>
<p>Tap Save when you finish making changes.</p>
<h2>Change Your Password</h2>
<p>Use the Password tab to create a new password for your account. For security, you may be asked to enter your current password first.</p>
<h2>Two-Factor Authentication</h2>
<p>Use the Two-Factor tab to add an extra layer of security to your account. When enabled, an additional verification step is required when signing in.</p>
<h2>Change the App Appearance</h2>
<p>Choose how iRacket should appear on your device.</p>
<p>Available options are:</p>
<ul><li>Light</li><li>Dark</li><li>System, which uses your device settings</li></ul>
<h2>Delete Your Account</h2>
<img src="/assets/guide/delete-account.png" alt="Delete account">
<p>If you no longer want to use iRacket, you can permanently delete your account from Profile settings.</p>
<div class="guide-important"><strong>Important:</strong> Deleting your account permanently removes your account information from iRacket.</div>
HTML;
    }

    private function myMatchesEn(): string
    {
        return <<<'HTML'
<h2>View Your Matches</h2>
<img src="/assets/guide/view-matches.png" alt="My matches">
<p>The My Matches page displays both official and manually added matches.</p>
<p>Use the year selector or search field to quickly find a specific match. Matches are grouped by month.</p>
<h2>Official Matches</h2>
<img src="/assets/guide/official-matches.png" alt="Official match details">
<p>Official matches are imported from the Swedish Table Tennis Association. Tap a match to view the match details.</p>
<p>Official match details can include:</p>
<ul><li>Match date</li><li>Players</li><li>Final score</li><li>Winner</li><li>Match status</li></ul>
<div class="guide-important"><strong>Important:</strong> Official matches cannot be edited or deleted from iRacket.</div>
HTML;
    }

    private function manualMatchEn(): string
    {
        return <<<'HTML'
<h2>Create a Match</h2>
<img src="/assets/guide/create-match.png" alt="New match">
<p>Manual matches help you record matches before the next official ranking update.</p>
<p>To add a new match:</p>
<ol><li>Tap New Match.</li><li>Select the match date.</li><li>Search for your opponent.</li><li>Enter the number of sets won by each player.</li><li>Choose one or more suggested tags describing your opponent.</li><li>Add your own notes if desired.</li><li>Save the match.</li></ol>
<p>After you save the match, your opponent receives an in-app notification asking them to review and confirm the match.</p>
<h2>Use Tags and Notes</h2>
<p>Tags and notes help you remember how your opponent played and what happened in the match.</p>
<p>Suggested tags can include playing style, strengths, or observations such as:</p>
<ul><li>Strong backhand</li><li>Strong forehand</li><li>Powerful serve</li><li>Fast footwork</li><li>Excellent net play</li><li>Sensitive to spin</li><li>Good sportsmanship</li><li>Stable player</li></ul>
<p>You can also add your own custom comments.</p>
<h2>Manual Match Details</h2>
<img src="/assets/guide/manual-match-details.png" alt="Manual match details">
<p>Manual matches include additional information such as:</p>
<ul><li>Match notes</li><li>Opponent tags</li><li>Confirmation status</li></ul>
<p>If your opponent has not confirmed the match yet, the status is shown as Pending.</p>
<h2>Edit, Delete, or Confirm a Manual Match</h2>
<p>To edit, delete, or confirm a manually added match, open the match and select the Match Details option.</p>
<p>To make changes:</p>
<ol><li>Open My Matches.</li><li>Select the manually added match.</li><li>Open Match Details.</li><li>Choose Edit Match, Delete Match, or Confirm Match, depending on what you need to do.</li></ol>
<div class="guide-important"><strong>Important:</strong> Only manually added matches can be edited, deleted, or confirmed in this way. Official match data cannot be deleted from the app.</div>
<h2>Automatic Removal of Manual Matches</h2>
<p>Manual matches are temporary. They are designed to help you track and estimate ranking changes between official ranking updates.</p>
<div class="guide-important"><strong>Important:</strong> Manual matches are automatically deleted on the first Monday of each month when new official data is published by the Swedish Table Tennis Association.</div>
HTML;
    }

    private function notificationsEn(): string
    {
        return <<<'HTML'
<img src="/assets/guide/notifications.png" alt="Notifications">
<p>iRacket uses notifications to help players review and confirm manually added matches.</p>
<p>If another player adds a manual match involving you, iRacket sends you a notification.</p>
<ul><li>Go to the Info option, and then tap Notifications.</li><li>Open the notification to review the match details and confirm the match if the information is correct.</li></ul>
<div class="guide-note"><strong>Note:</strong> Enabling push notifications is recommended, especially if you use manual match tracking.</div>
HTML;
    }

    private function informationEn(): string
    {
        return <<<'HTML'
<h2>Information View</h2>
<p>The Info view contains useful information and general app settings.</p>
<p>From the Info view, you can:</p>
<ul><li>Change the app language.</li><li>Read about iRacket.</li><li>View the Terms and Conditions.</li><li>Read the Privacy Policy.</li><li>Find application version information.</li><li>Contact support.</li></ul>
<h2>Change the App Language</h2>
<p>To switch between English and Swedish:</p>
<ol><li>Open Info.</li><li>Select the language option.</li><li>Choose English or Svenska.</li></ol>
<p>The application language changes after you select the new language.</p>
HTML;
    }

    private function faqEn(): string
    {
        return <<<'HTML'
<h2>Can I use iRacket without connecting to a player profile?</h2>
<p>Yes. You can continue as a guest if you only want to browse rankings, players, clubs, and public information.</p>
<h2>Why do I need to confirm a manual match?</h2>
<p>Confirmation helps ensure that both players agree that the manually added match details are correct.</p>
<h2>Can I delete an official match?</h2>
<p>No. Official matches are imported from official ranking data and cannot be deleted from iRacket.</p>
<h2>Why did my manual matches disappear?</h2>
<p>Manual matches are deleted on the first Monday of each month when new official ranking data is published by the Swedish Table Tennis Association.</p>
<h2>Where do I change the app language?</h2>
<p>Open the Info view and select English or Svenska from the language option.</p>
HTML;
    }

    // ---------------------------------------------------------------- Swedish

    private function welcomeSv(): string
    {
        return <<<'HTML'
<p>Välkommen till iRacket, din följeslagare för att följa svensk bordtennisranking och hålla koll på din egen utveckling.</p>
<p>Oavsett om du är aktiv spelare eller bara intresserad av svensk bordtennis ger iRacket dig snabb tillgång till spelarranking, matchhistorik, klubbinformation och prestationsstatistik.</p>
<h2>Vad är iRacket?</h2>
<p>iRacket är en mobilapp för bordtennisspelare och bordtennisintresserade i Sverige. Appen hjälper dig att följa ranking, se rankinghistorik, hålla koll på klubbar och följa matcher.</p>
<p>Med iRacket kan du:</p>
<ul>
<li>Följa din egen rankingresa.</li>
<li>Bläddra bland svenska bordtennisspelares ranking.</li>
<li>Visa detaljerade spelarprofiler och rankinghistorik.</li>
<li>Följa spelare som du är intresserad av.</li>
<li>Se officiella och manuellt tillagda matcher.</li>
<li>Lägga till manuella matcher för att uppskatta rankingförändringar.</li>
<li>Lägga till anteckningar och kommentarer om matcher och motståndare.</li>
<li>Få notiser när en annan spelare lägger till en match där du ingår.</li>
<li>Upptäcka månadens bästa spelare och klubbar i Bubblare.</li>
<li>Visa klubbdetaljer och klubbhistorik.</li>
</ul>
<div class="guide-note"><strong>Obs:</strong> Spelare är indelade i kategorierna Herrar och Damer. Vissa vyer innehåller även klubbinformation.</div>
HTML;
    }

    private function gettingStartedSv(): string
    {
        return <<<'HTML'
<h2>Skapa ditt konto</h2>
<img src="/assets/guide/create-account.png" alt="Registreringsskärm">
<p>Det tar bara några minuter att skapa ett iRacket-konto. Du kan registrera dig med Google, Apple eller din e-postadress.</p>
<p>Så skapar du ett konto med e-post:</p>
<ol>
<li>Ange ditt förnamn och efternamn.</li>
<li>Ange din e-postadress.</li>
<li>Skapa ett lösenord.</li>
<li>Bekräfta lösenordet.</li>
<li>Läs och godkänn användarvillkoren och integritetspolicyn.</li>
<li>Tryck på Skapa konto.</li>
</ol>
<div class="guide-note"><strong>Obs:</strong> Om du registrerar dig med e-post skickar iRacket en verifieringskod till din inkorg innan du kan fortsätta.</div>
<h2>Verifiera din e-post</h2>
<p>När du har registrerat dig med e-post ska du kontrollera din inkorg. Ange verifieringskoden i appen för att aktivera ditt konto.</p>
<p>När kontot är verifierat kan du fortsätta att konfigurera iRacket.</p>
<h2>Koppla din spelarprofil</h2>
<img src="/assets/guide/connect-player.png" alt="Koppla konto-skärm">
<p>När du har skapat ditt konto väljer du hur du vill använda iRacket. Du kan koppla kontot till din officiella spelarprofil inom svensk bordtennis eller fortsätta som gäst.</p>
<h3>Alternativ 1: Aktiv spelare</h3>
<p>Om du spelar officiella bordtennistävlingar kan du koppla kontot till din officiella spelarprofil.</p>
<p>Så kopplar du din profil:</p>
<ol>
<li>Aktivera Jag är en aktiv spelare.</li>
<li>Tryck på fältet för spelarval.</li>
<li>Sök efter ditt namn.</li>
<li>Välj din spelarprofil.</li>
<li>Aktivera pushnotiser. Detta rekommenderas.</li>
<li>Tryck på Koppla konto.</li>
</ol>
<h3>Alternativ 2: Fortsätt som gäst</h3>
<p>Om du bara vill bläddra bland ranking, spelare och klubbinformation trycker du på Fortsätt som gäst.</p>
<div class="guide-note"><strong>Obs:</strong> Gästanvändare kan utforska appen, men spelarspecifika funktioner blir inte tillgängliga förrän en spelarprofil har kopplats.</div>
<h2>Slutför kopplingen</h2>
<img src="/assets/guide/complete-connection.png" alt="Slutför koppling-skärm">
<p>När du har valt din spelarprofil visar iRacket automatiskt din registrerade klubb. Kontrollera uppgifterna och tryck på Koppla konto för att slutföra konfigurationen.</p>
HTML;
    }

    private function playersSv(): string
    {
        return <<<'HTML'
<h2>Bläddra bland spelare</h2>
<img src="/assets/guide/browse-players.png" alt="Spelarlista">
<p>Spelarsidan är huvudvyn i iRacket. Spelarna visas efter sina senaste aktuella rankingpoäng.</p>
<p>Varje spelarkort visar:</p>
<ul><li>Spelarens namn</li><li>Klubb</li><li>Distrikt</li><li>Kön</li><li>Aktuella rankingpoäng</li></ul>
<p>Använd sökfältet för att snabbt hitta en viss spelare. Tryck på en spelare för att öppna hela spelarprofilen.</p>
<h2>Filtrera spelare</h2>
<img src="/assets/guide/filter-players.png" alt="Avancerade filter">
<p>Använd filter för att begränsa spelarlistan och hitta relevanta spelare snabbare.</p>
<p>Tillgängliga filter är:</p>
<ul><li>Kön</li><li>Rankingpoäng</li><li>Distrikt</li><li>Ålder</li><li>Rankingmånad</li><li>Sorteringsordning</li></ul>
<p>Tryck på Använd för att uppdatera resultaten. Tryck på Rensa för att ta bort alla filter.</p>
<h2>Visa en spelarprofil</h2>
<img src="/assets/guide/player-profile.png" alt="Spelarprofil">
<p>När du väljer en spelare öppnas spelarens fullständiga profil.</p>
<p>En spelarprofil innehåller:</p>
<ul><li>Spelarens namn</li><li>Klubb</li><li>Distrikt</li><li>Senaste rankinghistorik</li><li>Aktuella rankingpoäng</li><li>Officiell rankinginformation</li></ul>
<p>Rankingtabellen visar månad, rankingplacering, poäng och poängförändring.</p>
<h2>Följ en spelare</h2>
<p>För att hålla koll på en annan spelares utveckling trycker du på Följ spelare i spelarens profil.</p>
<p>När du följer en spelare:</p>
<ul><li>Ändras knappen till Sluta följa.</li><li>Läggs spelaren till i din lista över följda spelare.</li><li>Kan du snabbt följa spelarens aktuella rankingpoäng.</li></ul>
<p>Du kan sluta följa en spelare när som helst.</p>
HTML;
    }

    private function bubblareSv(): string
    {
        return <<<'HTML'
<p>Bubblare lyfter fram de spelare och klubbar som presterat bäst föregående månad baserat på ökningar i rankingpoäng.</p>
<p>Välj år och månad och välj sedan en av tre kategorier:</p>
<ul><li>Damer</li><li>Herrar</li><li>Klubbar</li></ul>
<h2>Månadens bästa damer</h2>
<img src="/assets/guide/bubblare-women.png" alt="Månadens bästa damer">
<p>Damvyn visar de tre spelare med störst ökning i rankingpoäng i varje damklass under den valda månaden.</p>
<h2>Månadens bästa herrar</h2>
<img src="/assets/guide/bubblare-men.png" alt="Månadens bästa herrar">
<p>Herrvyn visar de tre spelare med störst ökning i rankingpoäng i varje herrklass under den valda månaden.</p>
<h2>Månadens bästa klubbar</h2>
<img src="/assets/guide/bubblare-clubs.png" alt="Månadens bästa klubbar">
<p>Klubbvyn rankar klubbar efter den sammanlagda ökningen i rankingpoäng för spelare från klubben under den valda månaden.</p>
<p>Varje klubbkort visar:</p>
<ul><li>Klubbnamn</li><li>Antal medlemmar</li><li>Totalt antal vunna rankingpoäng</li><li>Antal Bubblare-spelare</li></ul>
<h2>Sortera Bubblare-resultat</h2>
<p>Använd filterknappen för att sortera klubbrankingen.</p>
<p>Tillgängliga sorteringsalternativ är:</p>
<ul><li>Vunna rankingpoäng</li><li>Antal nya medlemmar</li><li>Antal Bubblare-spelare</li></ul>
<p>Tryck på Klar för att använda det valda sorteringsalternativet.</p>
HTML;
    }

    private function myProfileSv(): string
    {
        return <<<'HTML'
<h2>Profilöversikt</h2>
<img src="/assets/guide/profile-overview.png" alt="Profilöversikt">
<p>Din profilsida ger dig en sammanfattning av din aktivitet i iRacket.</p>
<p>Från din profilsida kan du:</p>
<ul><li>Visa din profilinformation.</li><li>Se din senaste rankinghistorik.</li><li>Öppna din fullständiga rankinghistorik.</li><li>Visa spelare du följer.</li><li>Hitta nya spelare att följa.</li><li>Visa dina senaste matcher.</li><li>Visa din klubbhistorik.</li></ul>
<h2>Min ranking</h2>
<p>Välj Min ranking för att visa din fullständiga rankinghistorik.</p>
<p>För varje officiell rankingperiod visar iRacket:</p>
<ul><li>Månad</li><li>Rankingplacering</li><li>Rankingpoäng</li><li>Månatliga poängförändringar</li></ul>
<div class="guide-note"><strong>Obs:</strong> Rankinginformationen baseras på officiella uppgifter från Svenska Bordtennisförbundet.</div>
<h2>Följ spelare från din profil</h2>
<img src="/assets/guide/follow-players.png" alt="Hitta spelare att följa">
<p>Välj Hitta spelare för att söka efter spelare du vill följa. Sök på spelarens namn och tryck på Följ spelare.</p>
<p>Spelare du följer visas på din profilsida för snabb åtkomst.</p>
HTML;
    }

    private function accountSettingsSv(): string
    {
        return <<<'HTML'
<h2>Redigera din profil</h2>
<img src="/assets/guide/edit-profile.png" alt="Redigera profil">
<p>I profilinställningarna kan du uppdatera dina personliga uppgifter.</p>
<p>Du kan:</p>
<ul><li>Ändra din profilbild.</li><li>Uppdatera ditt visningsnamn.</li><li>Uppdatera din e-postadress.</li><li>Lägga till ditt telefonnummer.</li><li>Uppdatera kön.</li><li>Uppdatera ålder.</li></ul>
<div class="guide-note"><strong>Obs:</strong> Om ditt konto är kopplat till en officiell SBTF-spelarprofil synkroniseras ditt spelarnamn automatiskt och kan inte redigeras.</div>
<p>Tryck på Spara när du är klar med ändringarna.</p>
<h2>Ändra ditt lösenord</h2>
<p>Använd fliken Lösenord för att skapa ett nytt lösenord till ditt konto. Av säkerhetsskäl kan du bli ombedd att först ange ditt nuvarande lösenord.</p>
<h2>Tvåfaktorsautentisering</h2>
<p>Använd fliken Tvåfaktor för att lägga till ett extra säkerhetslager för ditt konto. När funktionen är aktiverad krävs ett extra verifieringssteg vid inloggning.</p>
<h2>Ändra appens utseende</h2>
<p>Välj hur iRacket ska visas på din enhet.</p>
<p>Tillgängliga alternativ är:</p>
<ul><li>Ljust</li><li>Mörkt</li><li>System, som använder enhetens inställningar</li></ul>
<h2>Ta bort ditt konto</h2>
<img src="/assets/guide/delete-account.png" alt="Ta bort konto">
<p>Om du inte längre vill använda iRacket kan du permanent ta bort ditt konto från profilinställningarna.</p>
<div class="guide-important"><strong>Viktigt:</strong> När du tar bort ditt konto raderas din kontoinformation permanent från iRacket.</div>
HTML;
    }

    private function myMatchesSv(): string
    {
        return <<<'HTML'
<h2>Visa dina matcher</h2>
<img src="/assets/guide/view-matches.png" alt="Mina matcher">
<p>Sidan Mina matcher visar både officiella och manuellt tillagda matcher.</p>
<p>Använd årsväljaren eller sökfältet för att snabbt hitta en viss match. Matcherna grupperas per månad.</p>
<h2>Officiella matcher</h2>
<img src="/assets/guide/official-matches.png" alt="Officiella matchdetaljer">
<p>Officiella matcher importeras från Svenska Bordtennisförbundet. Tryck på en match för att visa matchdetaljerna.</p>
<p>Officiella matchdetaljer kan innehålla:</p>
<ul><li>Matchdatum</li><li>Spelare</li><li>Slutresultat</li><li>Vinnare</li><li>Matchstatus</li></ul>
<div class="guide-important"><strong>Viktigt:</strong> Officiella matcher kan inte redigeras eller tas bort från iRacket.</div>
HTML;
    }

    private function manualMatchSv(): string
    {
        return <<<'HTML'
<h2>Skapa en match</h2>
<img src="/assets/guide/create-match.png" alt="Ny match">
<p>Manuella matcher hjälper dig att registrera matcher innan nästa officiella rankinguppdatering.</p>
<p>Så lägger du till en ny match:</p>
<ol><li>Tryck på Ny match.</li><li>Välj matchdatum.</li><li>Sök efter din motståndare.</li><li>Ange hur många set varje spelare vann.</li><li>Välj en eller flera föreslagna taggar som beskriver din motståndare.</li><li>Lägg till egna anteckningar om du vill.</li><li>Spara matchen.</li></ol>
<p>När du sparar matchen får din motståndare en notis i appen med en begäran om att granska och bekräfta matchen.</p>
<h2>Använd taggar och anteckningar</h2>
<p>Taggar och anteckningar hjälper dig att komma ihåg hur motståndaren spelade och vad som hände i matchen.</p>
<p>Föreslagna taggar kan beskriva spelstil, styrkor eller observationer, till exempel:</p>
<ul><li>Stark backhand</li><li>Stark forehand</li><li>Kraftfull serve</li><li>Snabbt fotarbete</li><li>Mycket bra nätspel</li><li>Känslig för skruv</li><li>Bra sportsmannaanda</li><li>Stabil spelare</li></ul>
<p>Du kan också lägga till egna kommentarer.</p>
<h2>Detaljer för manuell match</h2>
<img src="/assets/guide/manual-match-details.png" alt="Detaljer för manuell match">
<p>Manuella matcher innehåller ytterligare information, till exempel:</p>
<ul><li>Matchanteckningar</li><li>Taggar om motståndaren</li><li>Bekräftelsestatus</li></ul>
<p>Om din motståndare ännu inte har bekräftat matchen visas statusen som Väntar.</p>
<h2>Redigera, ta bort eller bekräfta en manuell match</h2>
<p>För att redigera, ta bort eller bekräfta en manuellt tillagd match öppnar du matchen och väljer alternativet Matchdetaljer.</p>
<p>Så gör du ändringar:</p>
<ol><li>Öppna Mina matcher.</li><li>Välj den manuellt tillagda matchen.</li><li>Öppna Matchdetaljer.</li><li>Välj Redigera match, Ta bort match eller Bekräfta match, beroende på vad du vill göra.</li></ol>
<div class="guide-important"><strong>Viktigt:</strong> Endast manuellt tillagda matcher kan redigeras, tas bort eller bekräftas på detta sätt. Officiell matchdata kan inte tas bort från appen.</div>
<h2>Automatisk borttagning av manuella matcher</h2>
<p>Manuella matcher är tillfälliga. De är avsedda att hjälpa dig att följa och uppskatta rankingförändringar mellan officiella rankinguppdateringar.</p>
<div class="guide-important"><strong>Viktigt:</strong> Manuella matcher tas automatiskt bort den första måndagen varje månad när ny officiell data publiceras av Svenska Bordtennisförbundet.</div>
HTML;
    }

    private function notificationsSv(): string
    {
        return <<<'HTML'
<img src="/assets/guide/notifications.png" alt="Notiser">
<p>iRacket använder notiser för att hjälpa spelare att granska och bekräfta manuellt tillagda matcher.</p>
<p>Om en annan spelare lägger till en manuell match där du ingår skickar iRacket en notis till dig.</p>
<ul><li>Gå till alternativet Info och tryck sedan på Notiser.</li><li>Öppna notisen för att granska matchdetaljerna och bekräfta matchen om uppgifterna stämmer.</li></ul>
<div class="guide-note"><strong>Obs:</strong> Det rekommenderas att aktivera pushnotiser, särskilt om du använder manuell matchregistrering.</div>
HTML;
    }

    private function informationSv(): string
    {
        return <<<'HTML'
<h2>Informationsvy</h2>
<p>Informationsvyn innehåller användbar information och allmänna appinställningar.</p>
<p>Från informationsvyn kan du:</p>
<ul><li>Ändra appens språk.</li><li>Läsa mer om iRacket.</li><li>Visa användarvillkoren.</li><li>Läsa integritetspolicyn.</li><li>Hitta information om appversionen.</li><li>Kontakta support.</li></ul>
<h2>Ändra appens språk</h2>
<p>Så växlar du mellan engelska och svenska:</p>
<ol><li>Öppna Info.</li><li>Välj språkalternativet.</li><li>Välj English eller Svenska.</li></ol>
<p>Appens språk ändras när du har valt det nya språket.</p>
HTML;
    }

    private function faqSv(): string
    {
        return <<<'HTML'
<h2>Kan jag använda iRacket utan att koppla en spelarprofil?</h2>
<p>Ja. Du kan fortsätta som gäst om du bara vill bläddra bland ranking, spelare, klubbar och offentlig information.</p>
<h2>Varför behöver jag bekräfta en manuell match?</h2>
<p>Bekräftelsen hjälper till att säkerställa att båda spelarna är överens om att uppgifterna för den manuellt tillagda matchen stämmer.</p>
<h2>Kan jag ta bort en officiell match?</h2>
<p>Nej. Officiella matcher importeras från officiell rankingdata och kan inte tas bort från iRacket.</p>
<h2>Varför försvann mina manuella matcher?</h2>
<p>Manuella matcher tas bort den första måndagen varje månad när ny officiell rankingdata publiceras av Svenska Bordtennisförbundet.</p>
<h2>Var ändrar jag appens språk?</h2>
<p>Öppna informationsvyn och välj English eller Svenska i språkalternativet.</p>
HTML;
    }
}
