<?php
/**
 * Plugin Name: Taxonomy Terms CLI
 * Plugin URI: https://stevegrunwell.com
 * Description: WP-CLI command to gather details about taxonomy terms in a site.
 * Author: Steve Grunwell
 * Author URI: https://stevegrunwell.com
 * Version: 0.1.0
 * License: MIT
 * Text Domain: taxonomy-terms-cli
 */

// Bail out if this isn't a WP-CLI environment
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Import content from the existing CMS into WordPress
 */
class Taxonomy_Terms_CLI extends WP_CLI_Command {

	/**
	 * Retrieve information about the taxonomy terms in the current WordPress site.
	 *
	 * ## OPTIONS
	 *
	 * [--taxonomy=<taxonomy>]
	 * : The taxonomies to retrieve terms for. Separate multiple values with a comma.
	 *
	 * ## EXAMPLES
	 *
	 *   wp taxonomy-terms list --taxonomy=post_tag
	 *   wp taxonomy-terms list --taxonomy=category,post_tag
	 *
	 * @synopsis [--taxonomy=<taxonomy>]
	 * @subcommand list
	 */
	public function term_list( $args, $assoc_args ) {
		$taxonomies = isset( $assoc_args['taxonomy'] ) ? $assoc_args['taxonomy'] : null;
		$term_args = array();

		$terms = $this->get_terms( $taxonomies, $term_args );
		if ( empty( $terms ) ) {
			WP_CLI::error( __( 'No taxonomy terms were found!', 'taxonomy-terms-cli' ) );

		} else {
			WP_CLI::success( sprintf(
				_n( 'One term found.', '%d terms found.', count( $terms ), 'taxonomy-terms-cli' ),
				count( $terms )
			) );
		}
	}

	/**
	 * Retrieve the terms for the given $taxonomies with the given $arguments.
	 *
	 * This method primarily acts as a wrapper around get_terms() with slightly different defaults.
	 *
	 * @param string|array $taxonomies Optional. The taxonomies to retrieve terms for. The default is
	 *                                 all public taxonomies.
	 * @param array        $args       Optional. Additional arguments. Default is an empty array.
	 * @return array An array of WP_Term objects, or an empty array if no terms were found.
	 *
	 * @see get_terms()
	 */
	protected function get_terms( $taxonomies = null, $args = array() ) {
		$default_args = array(
			'orderby'    => 'name',
			'order'      => 'asc',
			'hide_empty' => false,
		);
		$args = wp_parse_args( $args, $default_args );

		if ( ! $taxonomies ) {
			$taxonomies = get_taxonomies( null, 'names' );
		} elseif ( ! is_array( $taxonomies ) ) {
			$taxonomies = explode( ',', (string) $taxonomies );
		}

		$terms = get_terms( $taxonomies, $args );

		if ( is_wp_error( $terms ) ) {
			WP_CLI::error( sprintf(
				__( 'An error occurred whilst retrieving taxonomy terms: %s', 'taxonomy-terms-cli' ),
				$terms->get_error_message()
			) );
		}

		return $terms;
	}

	/**
	 * Print a table of results.
	 *
	 * @param array $results The list of terms results.
	 */
	protected function print_table( $results ) {
		$table = new \cli\Table();

		// Set the table headers
		$table->setHeaders( array(
			'term_id'  => _x( 'ID', 'the taxonomy term ID', 'taxonomy-terms-cli' ),
			'taxonomy' => _x( 'Taxonomy', 'the taxonomy this term belongs to', 'taxonomy-terms-cli' ),
			'name'     => _x( 'Name', 'the taxonomy term title', 'taxonomy-terms-cli' ),
			'slug'     => _x( 'Slug', 'the taxonomy term slug', 'taxonomy-terms-cli' ),
			'count'    => _x( '# Posts', 'number of posts assigned to this term', 'taxonomy-terms-cli' )
		) );

		// Set content
		foreach ( $results as $result ) {
			$table->addRow( array(
				'term_id'  => $result->term_id,
				'taxonomy' => $result->taxonomy,
				'name'     => $result->name,
				'slug'     => $result->slug,
				'count'    => $result->count,
			) );
		}

		$table->display();
	}

}
\WP_CLI::add_command( 'taxonomy-terms', 'Taxonomy_Terms_CLI' );