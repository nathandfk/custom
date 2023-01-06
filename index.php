<?php get_header(); ?>


<?php if ( have_posts() ) : ?>

	<!-- pagination here -->

	<!-- the loop -->
	<?php while ( have_posts() ) : the_post(); ?>
		<h2><?php the_title(); ?></h2>
        <?php the_content(); ?>
	<?php endwhile; ?>
	<!-- end of the loop -->

	<!-- pagination here -->

	<?php wp_reset_postdata(); ?>

<?php else : ?>
	<p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
<?php endif; ?>

<?php get_footer(); ?>
    