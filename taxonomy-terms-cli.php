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
	 * [--ancestors]
	 * : Include the ancestors of a hierarchal taxonomy term.
	 *
	 * [--full-path]
	 * : Generate the full ancestral path to a term. Will automatically enable --ancestors flag.
	 *
	 * [--order=<asc|desc>]
	 * : The direction to order results, either ASC or DESC. Default is ASC.
	 *
	 * [--orderby=<id|count|name|slug>]
	 * : The field to order results by (id, count, name, or slug). Default is "name".
	 *
	 * [--taxonomy=<taxonomy>]
	 * : The taxonomies to retrieve terms for. Separate multiple values with a comma.
	 *
	 * ## EXAMPLES
	 *
	 *   wp taxonomy-terms list --taxonomy=post_tag
	 *   wp taxonomy-terms list --taxonomy=category,post_tag
	 *
	 * @synopsis [--ancestors] [--full-path] [--order=<asc|desc>] [--orderby=<id|count|name|slug>] [--taxonomy=<taxonomy>]
	 * @subcommand list
	 */
	public function term_list( $args, $assoc_args ) {
		$taxonomies = isset( $assoc_args['taxonomy'] ) ? $assoc_args['taxonomy'] : null;
		$term_args = array(
			'order'   => isset( $assoc_args['order'] ) && 'desc' === strtolower( $assoc_args['order'] ) ? 'desc' : 'asc',
		);

		$orderby_options = array( 'id', 'count', 'name', 'slug' );
		if ( isset( $assoc_args['orderby'] ) ) {
			$orderby = strtolower( $assoc_args['orderby'] );
			$term_args['orderby'] = in_array( $orderby, $orderby_options ) ? $orderby : 'name';
		}

		$terms = $this->get_terms( $taxonomies, $term_args );
		if ( empty( $terms ) ) {
			WP_CLI::error( __( 'No taxonomy terms were found!', 'taxonomy-terms-cli' ) );

		} else {

			if ( isset( $assoc_args['ancestors'] ) || isset( $assoc_args['full-path'] ) ) {
				$terms = $this->get_ancestors( $terms, isset( $assoc_args['full-path'] ) );
			}

			$this->print_table( $terms );
			WP_CLI::line();
			WP_CLI::success( sprintf(
				_n( 'One term found.', '%d terms found.', count( $terms ), 'taxonomy-terms-cli' ),
				count( $terms )
			) );
		}
	}

	/**
	 * Filter through the given $terms and retrieve the names of a hierarchal term's parents.
	 *
	 * @param array $terms The terms retrieved by get_terms().
	 * @param bool  $path  Optional. Include the ancestral path in the output? Default is false.
	 * @return array The same $terms array with an additional 'ancestors' property added to each of
	 *               the taxonomy terms.
	 */
	protected function get_ancestors( $terms, $path = false ) {
		$parent_lookup = array();

		// This could be more efficient, but get the terms keyed by the term ID
		foreach ( $terms as $term ) {
			$parent_lookup[ $term->term_id ] = $term;
		}

		foreach ( $terms as $term_key => $term ) {
			$ancestors = get_ancestors( $term->term_id, $term->taxonomy );
			$ancestors = array_reverse( $ancestors );
			$term_ancestors = array();
			$term_path = array();

			foreach ( $ancestors as $ancestor_id ) {
				if ( isset( $parent_lookup[ $ancestor_id ] ) ) {
					$term_ancestors[] = $parent_lookup[ $ancestor_id ]->name;
					$term_path[] = $parent_lookup[ $ancestor_id ]->slug;
				}
			}

			$terms[ $term_key ]->ancestors = $term_ancestors;
			if ( $path ) {
				$terms[ $term_key ]->ancestral_path = $term_path;
			}
		}

		return $terms;
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
		$first_row = current( $results );
		$ancestors = property_exists( $first_row, 'ancestors' ) && is_array( $first_row->ancestors );
		$ancestral_path = property_exists( $first_row, 'ancestral_path' ) && is_array( $first_row->ancestral_path );

		// Set the table headers
		$headers = array(
			'term_id'  => _x( 'ID', 'the taxonomy term ID', 'taxonomy-terms-cli' ),
			'taxonomy' => _x( 'Taxonomy', 'the taxonomy this term belongs to', 'taxonomy-terms-cli' ),
			'name'     => _x( 'Name', 'the taxonomy term title', 'taxonomy-terms-cli' ),
			'slug'     => _x( 'Slug', 'the taxonomy term slug', 'taxonomy-terms-cli' ),
			'count'    => _x( '# Posts', 'number of posts assigned to this term', 'taxonomy-terms-cli' )
		);

		if ( $ancestors ) {
			$headers['ancestors'] = _x( 'Term Parents', 'parents of a hierarchal term', 'taxonomy-terms-cli' );
		}

		if ( $ancestral_path ) {
			$headers['ancestral_path'] = _x( 'Full path', 'slugs for all ancestors of the term', 'taxonomy-terms-cli' );
		}

		$table->setHeaders( $headers );

		// Set content
		foreach ( $results as $result ) {
			$row = array(
				'term_id'  => $result->term_id,
				'taxonomy' => $result->taxonomy,
				'name'     => $result->name,
				'slug'     => $result->slug,
				'count'    => $result->count,
			);

			if ( $ancestors ) {
				$row['ancestors'] = implode(
					_x( ' › ', 'ancestral separator', 'taxonomy-terms-cli' ),
					$result->ancestors
				);
			}

			if ( $ancestral_path ) {
				$row['ancestral_path'] = empty( $result->ancestral_path ) ?
					$result->slug
					:	trailingslashit( implode( '/', $result->ancestral_path ) ) . $result->slug;
			}

			$table->addRow( $row );
		}

		$table->display();
	}

}
\WP_CLI::add_command( 'taxonomy-terms', 'Taxonomy_Terms_CLI' );