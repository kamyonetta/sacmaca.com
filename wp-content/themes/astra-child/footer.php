<?php if (!defined('ABSPATH')) { exit; } ?>
    </div><!-- .ast-container -->
    </div><!-- #content -->
    <footer class="salon-footer">
        <div class="salon-footer-top">
            <div><span class="salon-label">THE SOUNDTRACK</span><p>A little music.<br><em>A little atmosphere.</em></p></div>
            <iframe class="salon-player" src="<?php echo esc_url(home_url('/mp3-player/index.html')); ?>" title="Saçmaca music player" width="380" height="150" loading="lazy" allow="autoplay"></iframe>
            <a class="salon-footer-hello" href="<?php echo esc_url(home_url('/contact/')); ?>">Send a little<br>signal. <span aria-hidden="true">↗</span></a>
        </div>
        <div class="salon-footer-bottom"><a href="<?php echo esc_url(home_url('/')); ?>">Saçmaca bi website işte.</a><span>Made of lore &amp; questionable sleep.</span><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy</a><a href="#page">Back to top ↑</a></div>
    </footer>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
