<?php
/** A new front door; the original page content stays intact in WordPress. */
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main class="salon-home" id="main">
    <div class="salon-edition"><span><i aria-hidden="true"></i> INHABITING THE INTERNET</span><span>EST. 2001 &nbsp; / &nbsp; STILL A WORK IN PROGRESS</span><button type="button" class="salon-lights" aria-pressed="false" hidden>Dim the lights <span aria-hidden="true">◐</span></button></div>
    <section class="salon-hero" aria-labelledby="salon-title">
        <div class="salon-intro">
            <p class="salon-eyebrow">WELCOME TO MY LITTLE AFTER-HOURS</p>
            <h1 id="salon-title">Beautifully<br><em>saçma.</em><span class="salon-title-star" aria-hidden="true">✳</span></h1>
            <p class="salon-greeting">I’ve been expecting you.</p>
            <p class="salon-description">A little science, a little drama, a lot of lore.<br>Efecan’s personal corner of the internet.<br>Come in. Make yourself slightly too comfortable.</p>
            <a class="salon-pill" href="<?php echo esc_url(home_url('/bio-kiz/')); ?>">Enter the lore <span aria-hidden="true">↗</span></a>
            <div class="salon-margin-note"><span aria-hidden="true">⤷</span> no algorithm. just vibes.</div>
        </div>
        <div class="salon-portrait-stage">
            <span class="salon-orbit salon-orbit-one" aria-hidden="true"></span><span class="salon-orbit salon-orbit-two" aria-hidden="true"></span>
            <span class="salon-portrait-star" aria-hidden="true">✦</span>
            <figure class="salon-portrait">
                <img src="<?php echo esc_url(home_url('/wp-content/uploads/2025/02/EFE.jpg')); ?>" width="1536" height="2048" alt="Efecan in purple sunglasses" fetchpriority="high">
                <figcaption>EFECAN ŞENTÜRK <span>IN HIS NATURAL HABITAT</span></figcaption>
            </figure>
            <div class="salon-sticker">100 years old.<br><strong>allegedly.</strong><span aria-hidden="true">✶</span></div>
            <span class="salon-portrait-footnote">FIG. 01 — YOUR HOST, PROBABLY AWAKE</span>
        </div>
    </section>
    <div class="salon-ribbon" aria-label="Saçmaca bi website işte. Kız is a state of mind."><span>SAÇMACA Bİ WEBSITE İŞTE</span><b aria-hidden="true">✳</b><span>KIZ IS A STATE OF MIND</span><b aria-hidden="true">✳</b><span>PERSONAL, NOT PERFECT</span><b aria-hidden="true">✳</b></div>
    <section class="salon-explore" aria-labelledby="salon-explore-title">
        <div class="salon-section-heading"><span class="salon-label">CHOOSE YOUR RABBIT HOLE</span><h2 id="salon-explore-title">Stay a <em>little.</em></h2><span class="salon-small-note">There’s no wrong turn here. ↙</span></div>
        <div class="salon-portals">
            <a class="salon-portal" href="<?php echo esc_url(home_url('/bio-kiz/')); ?>"><div class="salon-portal-top"><span>01 / THE PERSON</span><span aria-hidden="true">↗</span></div><span class="salon-portal-art salon-asterisk" aria-hidden="true">✳</span><h3>The lore.</h3><p>Scientist by day. Hardcore sleeper by night. A state of mind, always.</p><span class="salon-portal-link">Meet Efecan <span aria-hidden="true">↗</span></span></a>
            <a class="salon-portal salon-portal-blog" href="<?php echo esc_url(home_url('/blog/')); ?>"><div class="salon-portal-top"><span>02 / THE WORDS</span><span aria-hidden="true">↗</span></div><span class="salon-portal-art salon-quotes" aria-hidden="true">“”</span><h3>The blog.</h3><p>Thoughts, tangents, and whatever refuses to stay in the drafts.</p><span class="salon-portal-link">Wander through <span aria-hidden="true">↗</span></span></a>
            <a class="salon-portal salon-portal-contact" href="<?php echo esc_url(home_url('/contact/')); ?>"><div class="salon-portal-top"><span>03 / THE CONNECTION</span><span aria-hidden="true">↗</span></div><span class="salon-portal-art salon-envelope" aria-hidden="true">✉</span><h3>Say hello.</h3><p>A thought? A secret? A very important piece of nonsense? Send it over.</p><span class="salon-portal-link">Leave a message <span aria-hidden="true">↗</span></span></a>
        </div>
    </section>
    <section class="salon-postcard" aria-labelledby="salon-postcard-title"><div><span class="salon-label">LOCATION: THE INTERNET</span><h2 id="salon-postcard-title">Somewhere between<br><em>science &amp; saçmalık.</em></h2><p>Same moon. Different tabs.</p></div><span class="salon-postcard-seal" aria-hidden="true">S<br>✳</span></section>
</main>
<?php get_footer(); ?>
