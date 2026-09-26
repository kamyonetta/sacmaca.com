<?php
/*
Template Name: Contact Page
*/

get_header();

// Process form submission
$feedback = ''; // Variable to store feedback message.
if ( $_SERVER["REQUEST_METHOD"] == "POST" ) {
    // Sanitize inputs
    $email   = sanitize_email( $_POST['email'] );
    $message = sanitize_textarea_field( $_POST['message'] );
    
    $to      = "efecan@sacmaca.com";
    $subject = "Mesaj $email";
    
    // Construct the email body
    $body  = "You have received a new message from your website contact form.\n\n";
    $body .= "Email: $email\n";
    $body .= "Message:\n$message\n";
    
    // Set the email headers
    $headers = array(
        "From: $email",
        "Reply-To: $email"
    );
    
    // Send the email using wp_mail
    if ( wp_mail( $to, $subject, $body, $headers ) ) {
        $feedback = "<p class='contact-confirmation'>The birds are bringing your message to me. I will reply to you. Soon.</p>";
    } else {
        $feedback = "<p class='contact-confirmation'>The birds could not receive your message. Can you try again? Louder this time.</p>";
    }
}
?>

<div class="contact-container">
    <!-- Feedback area: Reserve space regardless of whether there's a message -->
    <div class="contact-feedback">
        <?php if (!empty($feedback)) : ?>
            <div class="contact-message">
                <?php echo $feedback; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contact Form Markup -->
    <form method="post" action="">
        <label for="email">Your Email:</label>
        <input type="email" id="email" name="email" placeholder="<?php echo esc_attr( get_theme_mod( 'contact_email_placeholder', 'Your personal e-mail address :)' ) ); ?>" required>

        <label for="message">Your Message:</label>
        <textarea id="message" name="message" rows="6" placeholder="<?php echo esc_attr( get_theme_mod( 'contact_message_placeholder', 'Your very private message to me...' ) ); ?>" required></textarea>

        <button type="submit">Send</button>
    </form>
</div>


<?php get_footer(); ?>
