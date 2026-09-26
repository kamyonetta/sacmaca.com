<?php
/** Keep the native WordPress posts query, pagination, and publication workflow. */
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main id="main" class="salon-journal">
    <span class="salon-label">02 / THE WORDS</span>
    <h1>The <em>blog.</em></h1>
    <?php if (have_posts()) : ?>
        <div class="salon-post-list">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('salon-post-item'); ?>>
                <span class="salon-label"><?php echo esc_html(get_the_date()); ?></span>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php the_excerpt(); ?>
                <a class="salon-read" href="<?php the_permalink(); ?>">Read the story <span aria-hidden="true">↗</span><span class="screen-reader-text">: <?php the_title(); ?></span></a>
            </article>
        <?php endwhile; ?>
        </div>
        <?php the_posts_pagination(array('prev_text' => '← Newer', 'next_text' => 'Older →')); ?>
    <?php else : ?>
        <div class="salon-empty"><span aria-hidden="true">“</span><h2>A quiet corner.<br><em>For now.</em></h2><p>The thoughts are still marinating. In the meantime, there’s lore.</p><a class="salon-pill" href="<?php echo esc_url(home_url('/bio-kiz/')); ?>">Meet the author ↗</a></div>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
