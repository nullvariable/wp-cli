<?php

namespace WP_CLI;

/**
 * Base class for WP-CLI commands that deal with database objects.
 *
 * @package wp-cli
 */
abstract class CommandWithDBObject extends \WP_CLI_Command {

	protected $obj_type;
	protected $obj_id_key = 'ID';

	protected function _create( $args, $assoc_args, $callback ) {
		unset( $assoc_args[ $this->obj_id_key ] );

		$obj_id = $callback( $assoc_args );

		if ( is_wp_error( $obj_id ) ) {
			\WP_CLI::error( $obj_id );
		}

		if ( isset( $assoc_args['porcelain'] ) )
			\WP_CLI::line( $obj_id );
		else
			\WP_CLI::success( "Created $this->obj_type $obj_id." );
	}

	protected function _update( $args, $assoc_args, $callback ) {
		$status = 0;

		if ( empty( $assoc_args ) ) {
			\WP_CLI::error( "Need some fields to update." );
		}

		foreach ( $args as $obj_id ) {
			$params = array_merge( $assoc_args, array( $this->obj_id_key => $obj_id ) );

			$status = $this->success_or_failure( $this->wp_error_to_resp(
				$callback( $params ),
				"Updated $this->obj_type $obj_id."
			) );
		}

		exit( $status );
	}

	protected function _delete( $args, $assoc_args, $callback ) {
		$status = 0;

		foreach ( $args as $obj_id ) {
			$r = $callback( $obj_id, $assoc_args );
			$status = $this->success_or_failure( $r );
		}

		exit( $status );
	}

	protected function wp_error_to_resp( $r, $success_msg ) {
		if ( is_wp_error( $r ) )
			return array( 'error', $r->get_error_message() );
		else
			return array( 'success', $success_msg );
	}

	protected function success_or_failure( $r ) {
		list( $type, $msg ) = $r;

		if ( 'success' == $type ) {
			\WP_CLI::success( $msg );
			$status = 0;
		} else {
			\WP_CLI::warning( $msg );
			$status = 1;
		}

		return $status;
	}

	protected function show_single_field( $items, $field ) {
		foreach ( $items as $item ) {
			if ( !isset( $key ) ) {
				$key = $this->find_field( $item, $field );
				if ( !$key ) {
					\WP_CLI::error( "Invalid $this->obj_type field: $field." );
				}
			}

			\WP_CLI::print_value( $item->$key );
		}
	}

	protected function find_field( $item, $field ) {
		foreach ( array( $field, $this->obj_type . '_' . $field ) as $key ) {
			if ( isset( $item->$key ) ) {
				return $key;
			}
		}

		return false;
	}
}

