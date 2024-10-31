<?php
/*
Plugin Name: Recent Archive More Widget
Plugin URI: mrparagon.me/recent-archive/
Description: Shows Post of a category not listed in the category content  on a sidebar when the category is viewed.
Author: Kingsley Paragon
Version: 1.1
Author URI: mrparagon.me
license: GPLV2
*/

/*

{someone posted this on a forum: i decided to make this plugin, got it ready 17hrs later }
The message from the original seeker
"[I am in the process of finding a plugin that could achieve as the following:
- Show the posts in in widget area like normal plugin that could show recent posts and has filters the same.
- Filter to show only posts in specific category if it's on that category archive
- If the archive show for example 5 posts that widget must not show those posts but show others.
It's hard to explain myself.]" 

*/
add_action('wp_head','ramw_add_count_to_archive');

/*===---===-0--===--=-----=======---999---===============================
+Another way to acheive what i have below:
+         get_option('posts_per_page'); and set it as offset for the query
=        below...hahaha... didn't like that idea at first..
 =         it goes like this

  =        'offset' => (int)get_option('posts_per_page'),
   =          
            then it came like this:: Thought about the loophole(no p intended) 
    =       what if the page is using a custom query..
     =      Don't mind me. i'm hypersensitive. 
=
 =-=--0r0r0r0r0000r0r00r90f0f09foflfkrkfkfkrjfkekdjdkd==================== */

//Recent Post on the sidebar of category archive page::loads stylesheet.
function ramw_load_ra_widget_style(){
	wp_enqueue_style('tp_style',plugins_url('css/tp_style.css',__FILE__));
}
add_action('wp_enqueue_scripts','ramw_load_ra_widget_style'); ?>

<?php
//some functions here
function ramw_add_count_to_archive() { 
if(is_category()) {
add_filter('the_excerpt', 'ramw_get_post_ids');
}
}

// more functions 

function ramw_get_post_ids($excerpt){
ramw_add_id_to_array();

return $excerpt;
}

$g_post_id =array();

function ramw_add_id_to_array(){
global $g_post_id;
$g_post_id[] =get_the_ID();

return $g_post_id;

}

class Ramw_Recent_Archive_Sider extends WP_Widget
{
	
	function __construct()
	{
		$r_archive_options =array(
      'classname'=>'recent-archive-sidebar',
      'description'=> 'Shows more category archive posts on sidebar widget');
      parent::__construct('recent_archive_post', ' Recent Archive More ' , $r_archive_options);


         //added from default
		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
		
	}

function ramw_checkcat(){ 
if(is_category()) {
	$cart =get_the_category();
	// $ramw_cats =array();
	if(is_array($cart) AND (!empty($cart))){ 
     // $ramw_cats[0] = $cart[0]->name;
     // $ramw_cats[1] = $cart[0]->cat_ID;

	// 	print_r($cart);
	 return $cart[0]->name;
    //return $ramw_cats;
}
}
else{
	return false;
}
}

	public function widget($args, $instance) {
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_recent_post', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();


		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( ' More on '.$this->ramw_checkcat() );

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 4;
		if ( ! $number )
			$number = 4;

			$a_args = array(
		    'category_name'          => $this->ramw_checkcat(),
			'posts_per_page'         => $number,
			 'offset'                 => '0',
			 'post__not_in'            => ramw_add_id_to_array()
				);


?> 
<?php if($this->ramw_checkcat()!==false):  ?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>

		<?php 
		$archive_q = new WP_Query($a_args);

	if($archive_q->have_posts()): ?>
	 <ul class="recent_archive">
	 <?php
		while ( $archive_q->have_posts() ):
		$archive_q->the_post();
?>

<li>
<?php if ( '' != get_the_post_thumbnail() ):  ?>
<div class="archive-thumbnail">

<a href="<?php the_permalink(); ?>"><?php  the_post_thumbnail(); ?>  </a>
 </div>
<?php   endif; ?>
 <p class="archive-post-title">
<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a> </p>
<div class="cls"> </div>
</li>


 <?php endwhile; ?>
<?php endif; ?> 
</ul>
<?php echo $args['after_widget']; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

endif;
}
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		

		

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['recent_by_img']) )
			delete_option('recent_by_img');

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_recent_post', 'widget');
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 3;
		

?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
        

		
<?php
		
	}
}

function ramw_add_recent_archive_sider(){
	register_widget('Ramw_Recent_Archive_Sider');
}
add_action('widgets_init', 'ramw_add_recent_archive_sider');
?>