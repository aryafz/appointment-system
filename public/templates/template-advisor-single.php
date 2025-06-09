<?php
/**
 * Template for single Advisor
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>

                    <?php
                    // فیلدهای متا
                    $about       = get_post_meta( get_the_ID(), '_as_about', true );
                    $address     = get_post_meta( get_the_ID(), '_as_address', true );
                    $phone       = get_post_meta( get_the_ID(), '_as_phone', true );
                    // و … در صورت نیاز نمایش دهید
                    ?>
                    <?php if ( $about ) : ?>
                        <h3><?php _e( 'درباره مشاور', 'appointment-system' ); ?></h3>
                        <p><?php echo wpautop( esc_html( $about ) ); ?></p>
                    <?php endif; ?>

                    <h3><?php _e( 'رزرو نوبت', 'appointment-system' ); ?></h3>
                    <?php
                    // لینک به برگه رزرو با پارامتر advisor_id
                    $booking_page_id = 1452; // **اینجا شناسه برگه‌ی «رزرو نوبت» را وارد کنید**
                    $booking_url = add_query_arg(
                        array( 'advisor_id' => get_the_ID() ),
                        get_permalink( $booking_page_id )
                    );
                    ?>
                    <a href="<?php echo esc_url( $booking_url ); ?>" class="button as-book-now">
                        <?php _e( 'رزرو نوبت', 'appointment-system' ); ?>
                    </a>
                </div>

            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php get_footer(); ?>
