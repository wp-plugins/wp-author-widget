<?php
/**
 Plugin Name: Author Widget
 Plugin URI: http://wordpress.org/plugins/author-widget/
 Description:
 Author: wordpressdotorg, lancewillett, obenland, iandstewart, MikeHansenMe
 Version: 0.1
 Author URI: http://wordpress.org/
 */

/*
 * Widget to display blog authors with avatars and archive links.
 *
 * Configurable parameters include:
 * 1. Whether to display authors who haven't written any posts.
 * 2. Avatar size.
 */
class Author_Widget extends WP_Widget {
	public function __construct() {
		load_plugin_textdomain( 'author-widget' );

		parent::__construct( 'authors', __( 'Authors', 'author-widget' ), array(
				'classname' => 'author_widget',
				'description' => __( 'Display blogs authors with avatars and archive links.', 'author-widget' )
			),
			array(
				'width' => 300
			)
		);
		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	public function widget( $args, $instance ) {
		$cache = wp_cache_get( 'author_widget', 'widget' );

		if ( !is_array( $cache ) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = null;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		$instance = wp_parse_args( $instance, array(
			'title'       => __( 'Authors', 'author-widget' ),
			'all'         => false,
			'avatar_size' => 48,
		) );

		$user_args = array(
			'fields' => 'all',
			'who'    => 'authors',
		);

		$user_args  = apply_filters( 'author_widget_user_args', $user_args );
		$authors = get_users( $user_args );
		
		ob_start();

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
		echo '<ul>';

		foreach ( $authors as $author ) :
			if ( ! $instance['all'] && ! count_user_posts( $author->ID ) )
				continue;
		?>

		<li>
			<a href="<?php echo esc_url( get_author_posts_url( $author->ID, $author->user_nicename ) ); ?>">
				<?php if ( $instance['avatar_size'] > 0 ) echo get_avatar( $author->ID, $instance['avatar_size'] ); ?>
				<strong><?php echo esc_html( $author->display_name ); ?></strong>
			</a>
		</li>

		<?php
		endforeach;

		echo '</ul>';
		echo $args['after_widget'];
		$cache[ $args['widget_id'] ] = ob_get_flush();
		wp_cache_set( 'author_widget', $cache, 'widget' );
	}

	public function flush_widget_cache() {
		wp_cache_delete( 'author_widget', 'widget' );
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, array(
			'title'       => '',
			'all'         => false,
			'avatar_size' => 48,
		) );

		?>
		<p>
			<label>
				<?php _e( 'Title:', 'author-widget' ); ?>
				<input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</label>
		</p>
		<p>
			<label>
				<input class="checkbox" type="checkbox" <?php checked( $instance['all'] ); ?> name="<?php echo $this->get_field_name( 'all' ); ?>" />
				<?php _e( 'Display all authors (including those who have not written any posts)', 'author-widget' ); ?>
			</label>
		</p>
		<p>
			<label>
				<?php _e( 'Avatar Size (px):', 'author-widget' ); ?>
				<select name="<?php echo $this->get_field_name( 'avatar_size' ); ?>">
					<?php foreach( array( '0' => __( 'No Avatars', 'author-widget' ), '16' => '16x16', '32' => '32x32', '48' => '48x48', '96' => '96x96', '128' => '128x128' ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $instance['avatar_size'] ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$new_instance['title']       = strip_tags( $new_instance['title'] );
		$new_instance['all']         = isset( $new_instance['all'] );
		$new_instance['avatar_size'] = (int) $new_instance['avatar_size'];

		return $new_instance;
	}
}

function author_widget_init() {
	register_widget( 'Author_Widget' );
}
add_action( 'widgets_init', 'author_widget_init' );

