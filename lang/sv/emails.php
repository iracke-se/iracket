<?php

return [

    'account_verification' => [
        'subject'    => 'Välkommen till :app - Verifiera ditt konto',
        'title'      => 'Välkommen till :app!',
        'body'       => 'Tack för att du skapade ett konto hos oss. För att slutföra din registrering och börja använda :app, vänligen verifiera din e-postadress genom att ange koden nedan:',
        'code_label' => 'Din verifieringskod',
        'expiry'     => 'Den här koden går ut om <strong>15 minuter</strong>. Om du inte skapade ett konto hos :app kan du ignorera det här e-postmeddelandet.',
        'help'       => 'Behöver du hjälp? Kontakta oss på',
    ],

    'verification_code_resent' => [
        'subject'    => 'Din nya verifieringskod - :app',
        'title'      => 'Ny verifieringskod',
        'body'       => 'Du begärde en ny verifieringskod för ditt :app-konto. Använd koden nedan för att verifiera din e-postadress:',
        'code_label' => 'Din verifieringskod',
        'expiry'     => 'Den här koden går ut om <strong>15 minuter</strong>. Om du inte begärde den här koden, ignorera detta e-postmeddelande eller kontakta support om du har frågor.',
        'help'       => 'Behöver du hjälp? Kontakta oss på',
    ],

    'reset_password' => [
        'subject'    => 'Återställ ditt lösenord - :app',
        'title'      => 'Återställ ditt lösenord',
        'body'       => 'Vi mottog en begäran om att återställa lösenordet för ditt :app-konto. Klicka på knappen nedan för att skapa ett nytt lösenord:',
        'button'     => 'Återställ lösenord',
        'expiry'     => 'Den här länken för lösenordsåterställning går ut om <strong>60 minuter</strong>. Om du inte begärde en lösenordsåterställning kan du ignorera det här e-postmeddelandet.',
        'url_help'   => 'Om du har problem med att klicka på knappen, kopiera och klistra in URL:en nedan i din webbläsare:',
    ],

    'contact_reply' => [
        'subject'          => 'Sv: Ditt meddelande till :app',
        'title'            => 'Tack för att du kontaktade oss',
        'body'             => 'Tack för att du kontaktade :app. Vi har tagit emot ditt meddelande och hjälper dig gärna.',
        'original_message' => 'Ditt ursprungliga meddelande:',
        'our_response'     => 'Vårt svar:',
        'closing'          => 'Om du har ytterligare frågor är du välkommen att svara på detta e-postmeddelande eller kontakta oss igen via vår webbplats.',
        'regards'          => 'Med vänliga hälsningar,<br>:app-teamet',
    ],

    'new_contact' => [
        'subject'   => 'Ny kontaktformulärsinlämning - :app',
        'title'     => 'Ny kontaktformulärsinlämning',
        'body'      => 'Du har fått en ny kontaktformulärsinlämning från webbplatsen :app.',
        'col_name'  => 'Namn:',
        'col_email' => 'E-post:',
        'col_msg'   => 'Meddelande:',
        'submitted' => 'Skickat:',
        'footer'    => 'Du kan svara på denna kontakt från adminpanelen eller genom att svara direkt på detta e-postmeddelande.',
    ],

];
