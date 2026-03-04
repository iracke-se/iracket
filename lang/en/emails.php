<?php

return [

    'account_verification' => [
        'subject'    => 'Welcome to :app - Verify Your Account',
        'title'      => 'Welcome to :app!',
        'body'       => 'Thank you for creating an account with us. To complete your registration and start using :app, please verify your email address by entering the code below:',
        'code_label' => 'Your Verification Code',
        'expiry'     => 'This code will expire in <strong>15 minutes</strong>. If you didn\'t create an account with :app, you can safely ignore this email.',
        'help'       => 'Need help? Contact us at',
    ],

    'verification_code_resent' => [
        'subject'    => 'Your New Verification Code - :app',
        'title'      => 'New Verification Code',
        'body'       => 'You requested a new verification code for your :app account. Please use the code below to verify your email address:',
        'code_label' => 'Your Verification Code',
        'expiry'     => 'This code will expire in <strong>15 minutes</strong>. If you didn\'t request this code, please ignore this email or contact support if you have concerns.',
        'help'       => 'Need help? Contact us at',
    ],

    'reset_password' => [
        'subject'    => 'Reset Your Password - :app',
        'title'      => 'Reset Your Password',
        'body'       => 'We received a request to reset your password for your :app account. Click the button below to create a new password:',
        'button'     => 'Reset Password',
        'expiry'     => 'This password reset link will expire in <strong>60 minutes</strong>. If you didn\'t request a password reset, you can safely ignore this email.',
        'url_help'   => 'If you\'re having trouble clicking the button, copy and paste the URL below into your browser:',
    ],

    'contact_reply' => [
        'subject'          => 'Re: Your message to :app',
        'title'            => 'Thank you for contacting us',
        'body'             => 'Thank you for reaching out to :app. We have received your message and are happy to assist you.',
        'original_message' => 'Your original message:',
        'our_response'     => 'Our response:',
        'closing'          => 'If you have any further questions, feel free to reply to this email or contact us again through our website.',
        'regards'          => 'Best regards,<br>The :app Team',
    ],

    'new_contact' => [
        'subject'   => 'New Contact Form Submission - :app',
        'title'     => 'New Contact Form Submission',
        'body'      => 'You have received a new contact form submission from the :app website.',
        'col_name'  => 'Name:',
        'col_email' => 'Email:',
        'col_msg'   => 'Message:',
        'submitted' => 'Submitted:',
        'footer'    => 'You can reply to this contact from the admin panel or by replying directly to this email.',
    ],

];
