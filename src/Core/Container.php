<?php
declare( strict_types=1 );

namespace VisitorSentinel\Core;

class Container {
	private array $bindings = array();
	private array $instances = array();

	public function singleton( string $id, $factory = null ): void {
		if ( null === $factory && class_exists( $id ) ) {
			$factory = function () use ( $id ) {
				return new $id( $this );
			};
		}
		$this->bindings[ $id ] = array( 'factory' => $factory, 'shared' => true );
	}

	public function get( string $id ) {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}
		$binding  = isset( $this->bindings[ $id ] ) ? $this->bindings[ $id ] : array(
			'factory' => function () use ( $id ) {
				return new $id( $this );
			},
			'shared'  => false,
		);
		$instance = call_user_func( $binding['factory'], $this );
		if ( $binding['shared'] ) {
			$this->instances[ $id ] = $instance;
		}
		return $instance;
	}
}
