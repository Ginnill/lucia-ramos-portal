<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="mx-auto flex max-w-5xl flex-col text-center">
        <h1 class="mt-6 text-5xl font-medium tracking-tight [text-wrap:balance] text-dark sm:text-6xl"><?php the_title(); ?></h1>

        <?php if(! is_page()): ?>
            <time datetime="<?php echo get_the_date( 'c' ); ?>" itemprop="datePublished" class="order-first text-sm text-dark"><?php echo get_the_date(); ?></time>

            <p class="mt-6 text-sm font-semibold text-dark">by <?php the_author(); ?></p>
        <?php endif; ?>
    </header>

    <?php if(has_post_thumbnail()): ?>
        <div class="mt-10 sm:mt-20 mx-auto max-w-4xl rounded-4xl bg-light overflow-hidden">
            <?php the_post_thumbnail('large', ['class' => 'aspect-16/10 w-full object-cover']); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content mx-auto max-w-3xl mt-10 sm:mt-20">
        <?php the_content(); ?>
    </div>
</article>
