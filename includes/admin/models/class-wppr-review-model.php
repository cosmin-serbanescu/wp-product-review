<?php
/**
 * Model responsible for the reviews in WPPR.
 *
 * @package     WPPR
 * @subpackage  Models
 * @copyright   Copyright (c) 2017, Marius Cristea
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPPR_Review
 *
 * @since 3.0
 */
class WPPR_Review_Model extends WPPR_Model_Abstract {

	/**
	 * The review ID.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var int $ID The review id.
	 */
	private $ID = 0;

	/**
	 * The overall score of the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var float $score The overall score of the review.
	 */
	private $score = 0;

	/**
	 * If the review is active or not.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var bool $is_active If the review is active or not.
	 */
	private $is_active = false;

	/**
	 * Array containg the list of pros for the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var array $pros The list of pros.
	 */
	private $pros = array();

	/**
	 * The array containg the list of cons for the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var array $cons The list of cons.
	 */
	private $cons = array();

	/**
	 * The review title.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var string $name The review title.
	 */
	private $name = '';

	/**
	 * The url of the image used in the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var array $image The urls of the images used.
	 */
	private $image = '';

	/**
	 * The click behaviour.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var string $click The click behaviour.
	 */
	private $click = '';

	/**
	 * The list of links as url=>link_title
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var array $links The list of links from the review
	 */
	private $links = array();

	/**
	 * The price of the product reviewed.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var string $price The price of the product reviewed.
	 */
	private $price = '0.00';

	/**
	 * An array keeping the list of options for the product reviewed.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @var array $options The options of the product reviewed.
	 */
	private $options = array();

	/**
	 * WPPR_Review constructor.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param mixed $review_id The review id.
	 */
	public function __construct( $review_id = false ) {
	    parent::__construct();

		if ( $review_id === false ) {
			$this->logger->error( 'No review id provided.' );

			return false;
		}
		if ( $this->check_post( $review_id ) ) {
			$this->ID = $review_id;
			$this->logger->notice( 'Checking review status for ID: ' . $review_id );
			$this->setup_status();
			if ( $this->is_active() ) {
				$this->logger->notice( 'Setting up review for ID: ' . $review_id );
				$this->setup_price();
				$this->setup_name();
				$this->setup_click();
				$this->setup_image();
				$this->setup_links();
				$this->setup_pros_cons();
				$this->setup_options();
				$this->count_rating();

				return true;
			} else {
				$this->logger->warning( 'Review is not active for this ID: ' . $review_id );

				return false;
			}
		} else {
			$this->logger->error( 'No post id found to attach this review.' );
		}

		return false;
	}

	/**
	 * Check if post record exists with that id.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @param string $review_id The review id to check.
	 * @return bool
	 */
	private function check_post( $review_id ) {
		return is_string( get_post_type( $review_id ) );
	}

	/**
	 * Setup the review status.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_status() {
		$status = get_post_meta( $this->ID, 'cwp_meta_box_check', true );
		if ( $status === 'Yes' ) {
			$this->is_active = true;
		} else {
			$this->is_active = false;
		}
	}

	/**
	 * Check if review is active or not.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return bool
	 */
	public function is_active() {
		return apply_filters( 'wppr_is_review_active', $this->is_active, $this->ID, $this );
	}

	/**
	 * Setup the price of the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_price() {
		$price       = get_post_meta( $this->ID, 'cwp_rev_price', true );
		$price       = $this->format_price( $price );
		$this->price = $price;
	}

	/**
	 * Format a string to a price format.
	 *
	 * @since   3.0.0
	 * @access  private
	 * @param string $string The string for the price.
	 * @return string
	 */
	private function format_price( $string ) {
		$price = preg_replace( '/[^0-9.,]/', '', $string );

		return floatval( $price );
	}

	/**
	 * Setup the name of the review.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_name() {
		$name       = get_post_meta( $this->ID, 'cwp_rev_product_name', true );
		$this->name = $name;
	}

	/**
	 * Setup the link behaviour
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_click() {
		$click = get_post_meta( $this->ID, 'cwp_image_link', true );
		if ( $click === 'image' || $click === 'link' ) {
			$this->click = $click;
		}
	}

	/**
	 * Setup the image url.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_image() {
		$image = get_post_meta( $this->ID, 'cwp_rev_product_image', true );
		if ( empty( $image ) ) {
			$image = wp_get_attachment_url( get_post_thumbnail_id( $this->ID ) );
		}
		$this->image = $image;
	}

	/**
	 * Setup the links array.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_links() {
		$link_text                = get_post_meta( $this->ID, 'cwp_product_affiliate_text', true );
		$link_url                 = get_post_meta( $this->ID, 'cwp_product_affiliate_link', true );
		$this->links[ $link_text ] = $link_url;
		$link_text                = get_post_meta( $this->ID, 'cwp_product_affiliate_text2', true );
		$link_url                 = get_post_meta( $this->ID, 'cwp_product_affiliate_link2', true );
		$this->links[ $link_text ] = $link_url;
		$new_links                = get_post_meta( $this->ID, 'wppr_links', true );
		if ( ! empty( $new_links ) ) {
			$this->links = $new_links;
		}
	}

	/**
	 * Setup the pros and cons array.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_pros_cons() {
		$options_nr = $this->wppr_get_option( 'cwppos_option_nr' );
		$pros       = array();
		$cons       = array();
		for ( $i = 1; $i <= $options_nr; $i ++ ) {
			$tmp_pro = get_post_meta( $this->ID, 'cwp_option_' . $i . '_pro', true );
			$tmp_con = get_post_meta( $this->ID, 'cwp_option_' . $i . '_cons', true );
			if ( ! empty( $tmp_pro ) ) {
				$pros[] = $tmp_pro;
			}
			if ( ! empty( $tmp_con ) ) {
				$cons[] = $tmp_con;
			}
		}
		// New pros meta.
		$new_pros = get_post_meta( $this->ID, 'wppr_pros', true );
		if ( ! empty( $new_pros ) ) {
			$pros = $new_pros;
		}
		$this->pros = array_filter( $pros );
		// New cons meta.
		$new_cons = get_post_meta( $this->ID, 'wppr_cons', true );
		if ( ! empty( $new_cons ) ) {
			$cons = $new_cons;
		}
		$this->cons = array_filter( $cons );

	}

	/**
	 * Setup the options array.
	 *
	 * @since   3.0.0
	 * @access  private
	 */
	private function setup_options() {
		$options    = array();
		$options_nr = $this->wppr_get_option( 'cwppos_option_nr' );
		for ( $i = 1; $i <= $options_nr; $i ++ ) {
			$tmp_name = get_post_meta( $this->ID, 'option_' . $i . '_content', true );
			if ( $tmp_name != '' ) {
				$tmp_score = get_post_meta( $this->ID, 'option_' . $i . '_grade', true );
				$options[] = array(
					'name'  => $tmp_name,
					'value' => $tmp_score,
				);
			}
		}
		$new_options = get_post_meta( $this->ID, 'wppr_options', true );
		if ( ! empty( $new_options ) ) {
			$options = $new_options;
		}
		$this->options = $options;
	}

	/**
	 * Calculate the review rating.
	 *
	 * @since   3.0.0
	 * @access  public
	 */
	public function count_rating() {
		$values      = wp_list_pluck( $this->options, 'value' );
		$this->score = ( count( $this->options ) > 0 ) ? floatval( array_sum( $values ) / count( $this->options ) ) : 0;

		update_post_meta( $this->ID, 'wppr_rating', number_format( $this->score, 2 ) );
	}

	/**
	 * Setter method for options.
	 *
	 * We update the options array if there is only a single component like :
	 *      array(
	 *          'name'=>'Review name',
	 *          'value'=>Option rating
	 *      )
	 * or the all options array if we get smth like:
	 *  array(
	 *      array(
	 *          'name'=>'Review name',
	 *          'value'=>Option rating
	 *      ),
	 *      array(
	 *          'name'=>'Review name',
	 *          'value'=>Option rating
	 *      )
	 *  )
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   array $options The options array.
	 * @return bool
	 */
	public function set_options( $options ) {
		if ( is_array( $options ) ) {
			$options = apply_filters( 'wppr_options_format', $options, $this->ID, $this );
			if ( isset( $options['name'] ) ) {
				/**
				 * Add options if the param is
				 * array(
				 *  'name'=>'Review name',
				 *  'value'=>Option rating
				 * )
				 */
				$this->options[] = $options;
				$this->count_rating();

				return update_post_meta( $this->ID, 'wppr_options', $this->options );
			} else {
				/**
				 * Update the all list of options.
				 */
				$this->options = $options;
				$this->count_rating();

				return update_post_meta( $this->ID, 'wppr_options', $this->options );

			}
		} else {
			$this->logger->error( 'Invalid value for options in review: ' . $this->ID );
		}

		return false;
	}

	/**
	 * Update the cons array.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   array|string $cons The cons array or string to add.
	 * @return bool
	 */
	public function set_cons( $cons ) {
		$cons = apply_filters( 'wppr_cons_format', $cons, $this->ID, $this );
		if ( is_array( $cons ) ) {
			// We update the whole array.
			$this->cons = $cons;
			$this->logger->notice( 'Update cons array for ID . ' . $this->ID );

			return update_post_meta( $this->ID, 'wppr_cons', $this->cons );
		} else {
			// We add the text to the old array.
			$this->pros[] = $cons;
			$this->logger->notice( 'Adding cons option for ID . ' . $this->ID );

			return update_post_meta( $this->ID, 'wppr_cons', $this->cons );
		}

	}

	/**
	 * Update the pros array.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   array|string $pros The pros array or string to add.
	 * @return bool
	 */
	public function set_pros( $pros ) {
		$pros = apply_filters( 'wppr_pros_format', $pros, $this->ID, $this );
		if ( is_array( $pros ) ) {
			// We update the whole array.
			$this->pros = $pros;
			$this->logger->notice( 'Update pros array for ID . ' . $this->ID );

			return update_post_meta( $this->ID, 'wppr_pros', $this->pros );
		} else {
			// We add the text to the old array.
			$this->pros[] = $pros;
			$this->logger->notice( 'Adding pros option for ID . ' . $this->ID );

			return update_post_meta( $this->ID, 'wppr_pros', $this->pros );
		}
	}

	/**
	 * Save the links array ( url=>title ) to the postmeta.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   array $links The new links array.
	 * @return bool Either was saved or not.
	 */
	public function set_links( $links ) {
		$links = apply_filters( 'wppr_links_format', $links, $this->ID, $this );
		if ( is_array( $links ) ) {
			$this->links = $links;

			return update_post_meta( $this->ID, 'wppr_links', $links );
		} else {
			$this->logger->error( 'Review: ' . $this->ID . ' Invalid array for links, it should be url=>text' );
		}

		return false;
	}

	/**
	 * Set the new image url.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   string $image The new image url.
	 * @return bool
	 */
	public function set_image( $image ) {
		$image = apply_filters( 'wppr_image_format', $image, $this->ID, $this );
		if ( $image !== $this->image ) {
			$this->image = $image;

			return update_post_meta( $this->ID, 'cwp_rev_product_image', $image );
		} else {
			$this->logger->warning( 'Image already used for ID: ' . $this->ID );
		}

		return false;
	}

	/**
	 * Setter for click behaviour.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param string $click The new click behaviour.
	 * @return bool
	 */
	public function set_click( $click ) {
		if ( $click === 'image' || $click === 'link' ) {
			if ( $this->click != $click ) {
				$this->click = $click;

				return update_post_meta( $this->ID, 'cwp_image_link', $this->click );
			} else {
				$this->logger->warning( 'Value for click already set in ID: ' . $this->ID );
			}
		} else {
			$this->logger->warning( 'Wrong value for click on ID : ' . $this->ID );
		}

		return false;
	}

	/**
	 * Setter method for saving the review name.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   string $name The new review name.
	 * @return bool
	 */
	public function set_name( $name ) {
		$name = apply_filters( 'wppr_name_format', $name, $this->ID, $this );
		if ( $name !== $this->name ) {
			$this->name = $name;

			return update_post_meta( $this->ID, 'cwp_rev_product_name', $name );
		}

		return false;
	}

	/**
	 * Setup the new price.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   string $price The new price.
	 * @return bool
	 */
	public function set_price( $price ) {
		$price = $this->format_price( $price );
		$price = apply_filters( 'wppr_price_format', $price, $this->ID, $this );
		if ( $price !== $this->price ) {
			$this->price = $price;

			return update_post_meta( $this->ID, 'cwp_rev_price', $price );
		} else {
			$this->logger->warning( 'Review: ' . $this->ID . ' price is the same.' );
		}

		return false;
	}

	/**
	 * Deactivate the review.
	 *
	 * @since   3.0.0
	 * @access  public
	 */
	public function deactivate() {
		if ( $this->is_active === false ) {
			$this->logger->warning( 'Review is already inactive for ID: ' . $this->ID );
		}

		$this->is_active = apply_filters( 'wppr_review_change_status', false, $this->ID, $this );

		do_action( 'wppr_review_deactivate', $this->ID, $this );

		return update_post_meta( $this->ID, 'cwp_meta_box_check', 'No' );
	}

	/**
	 * Activate the review.
	 *
	 * @since   3.0.0
	 * @access  public
	 */
	public function activate() {
		if ( $this->is_active === true ) {
			$this->logger->warning( 'Review is already active for ID: ' . $this->ID );
		}

		$this->is_active = apply_filters( 'wppr_review_change_status', true, $this->ID, $this );
		do_action( 'wppr_review_activate', $this->ID, $this );

		return update_post_meta( $this->ID, 'cwp_meta_box_check', 'Yes' );
	}

	/**
	 * Return the review id.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return int
	 */
	public function get_ID() {
		return $this->ID;
	}

	/**
	 * Return the review name.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return string
	 */
	public function get_name() {
		return apply_filters( 'wppr_name', $this->name, $this->ID, $this );
	}

	/**
	 * Returns the review price.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return string
	 */
	public function get_price() {
		return apply_filters( 'wppr_price', $this->price, $this->ID, $this );
	}

	/**
	 * Return the click behaviour.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return string
	 */
	public function get_click() {
		return apply_filters( 'wppr_click', $this->click, $this->ID, $this );
	}

	/**
	 * Return the url of the thumbnail.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return string
	 */
	public function get_small_thumbnail() {
		global $wpdb;
		// filter for image size;
		$size        = apply_filters( 'wppr_review_image_size', 'thumbnail', $this->ID, $this );
		$attachment  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $this->image ) );
		$image_id    = isset( $attachment[0] ) ? $attachment[0] : '';
		$image_thumb = '';
		if ( ! empty( $image_id ) ) {
			$image_thumb = wp_get_attachment_image_src( $image_id, $size );
			if ( $size !== 'thumbnail' ) {
				if ( $image_thumb[0] === $this->image ) {
					$image_thumb = wp_get_attachment_image_src( $image_id, 'thumbnail' );
				}
			}
		}

		return apply_filters( 'wppr_thumb', isset( $image_thumb[0] ) ? $image_thumb[0] : $this->image, $this->ID, $this );
	}

	/**
	 * Get the list of images for the review.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_image() {
		return apply_filters( 'wppr_images', $this->image, $this->ID, $this );
	}

	/**
	 * Return the list of links in url=>text format.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_links() {
		return apply_filters( 'wppr_links', $this->links, $this->ID );

	}

	/**
	 * Getter for the pros array.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_pros() {
		return apply_filters( 'wppr_pros', $this->pros, $this->ID, $this );
	}

	/**
	 * Getter for the cons array.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_cons() {
		return apply_filters( 'wppr_cons', $this->cons, $this->ID, $this );
	}

	/**
	 * Return the rating of the review.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return float
	 */
	public function get_rating() {
		$comment_influence = intval( $this->wppr_get_option( 'cwppos_infl_userreview' ) );
		$rating            = $this->score;
		if ( $comment_influence > 0 ) {
			$comments_rating = $this->get_comments_rating();
			$rating          = $comments_rating * ( $comment_influence / 100 ) + $rating * ( ( 100 - $comment_influence ) / 100 );
		}

		return apply_filters( 'wppr_rating', $rating, $this->ID, $this );
	}

	/**
	 * Get comments rating.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return float|int
	 */
	public function get_comments_rating() {
		if ( $this->ID === 0 ) {
			$this->logger->error( 'Can not get comments rating, id is not set' );

			return 0;
		}
		$comments_query = new WP_Comment_Query;
		$comments       = $comments_query->query( array(
			'fields'  => 'ids',
			'status'  => 'approve',
			'post_id' => $this->ID,
		) );
		if ( $comments ) {
			$options = array();
			foreach ( $comments as $comment ) {
				$options = array_merge( $options, $this->get_comment_options( $comment ) );
			}

			if ( count( $options ) != 0 ) {
				return ( array_sum( wp_list_pluck( $options, 'values' ) ) / count( $options ) );
			} else {
				return 0;
			}
		} else {
			return 0;
		}

	}

	/**
	 * Return the options values and names associated with the comment.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @param   int $comment_id The comment id.
	 * @return array
	 */
	public function get_comment_options( $comment_id ) {
		$options = array();
		if ( $this->wppr_get_option( 'cwppos_show_userreview' ) === 'yes' ) {
			$options_names = wp_list_pluck( $this->options, 'name' );
			foreach ( $options_names as $k => $name ) {
				$value = get_comment_meta( $comment_id, 'meta_option_' . $k, true );
				if ( ! empty( $value ) ) {
					$value = 0;
				}
				$options[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		return $options;

	}

	/**
	 * Return the options array of the review.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_options() {
		return apply_filters( 'wppr_options', $this->options, $this->ID, $this );
	}

	/**
	 * Method to retrieve the review model data as an array.
	 *
	 * @since   3.0.0
	 * @access  public
	 * @return array
	 */
	public function get_review_data() {
		$data = array(
			'id' => $this->get_ID(),
			'name' => $this->get_name(),
			'price' => $this->get_price(),
			'click' => $this->get_click(),
			'image' => array(
				'full' => $this->get_image(),
				'thumb' => $this->get_small_thumbnail(),
			),
			'rating' => $this->get_rating(),
			'comment_rating' => $this->get_comments_rating(),
			'pros' => $this->get_pros(),
			'cons' => $this->get_cons(),
			'options' => $this->get_options(),
			'links' => $this->get_links(),
		);

		return $data;
	}

}
