<?php

/**
 * @package WPSEO\UnitTests
 */
class WPSEO_Configuration_Endpoint_Mock extends WPSEO_Configuration_Endpoint {
	public function get_service() {
		return $this->service;
	}

	public function get_route( $route ) {
		return parent::get_route( $route );
	}
}

class WPSEO_WP_REST_Server_Mock extends WP_REST_Server {
	public function get_endpoints() {
		return $this->endpoints;
	}
}

/**
 * Class WPSEO_Configuration_Endpoint_Test
 */
class WPSEO_Configuration_Endpoint_Test extends WPSEO_UnitTestCase {
	/** @var WPSEO_Configuration_Endpoint_Mock */
	protected $endpoint;

	/**
	 * Set up
	 */
	public function setUp() {
		parent::setUp();

		$this->endpoint = new WPSEO_Configuration_Endpoint_Mock();
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::set_service()
	 */
	public function test_set_service() {
		$service = $this->getMockBuilder( WPSEO_Configuration_Service::class )->getMock();

		$this->assertNull( $this->endpoint->set_service( $service ) );
		$this->assertEquals( $service, $this->endpoint->get_service() );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::get_route()
	 */
	public function test_get_route() {
		$expected = '/' . WPSEO_Configuration_Endpoint::REST_NAMESPACE . '/' . WPSEO_Configuration_Endpoint::ENDPOINT_STORE;
		$result   = $this->endpoint->get_route( WPSEO_Configuration_Endpoint::ENDPOINT_STORE );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::get_configuration()
	 */
	public function test_get_configuration() {
		$nonce       = wp_create_nonce( 'wp_rest' );
		$endpoint    = $this->endpoint->get_route( WPSEO_Configuration_Endpoint::ENDPOINT_STORE );
		$base_config = array( 'test' => 'config' );

		$expected = array_merge(
			$base_config,
			array(
				'endpoint' => $endpoint,
				'nonce'    => $nonce,
			)
		);

		$service = $this
			->getMockBuilder( WPSEO_Configuration_Service::class )
			->setMethods( array( 'get_configuration' ) )
			->getMock();

		$service
			->expects( $this->once() )
			->method( 'get_configuration' )
			->willReturn( $base_config );


		$this->endpoint->set_service( $service );

		$result = $this->endpoint->get_configuration();
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::can_retrieve_data()
	 */
	public function test_can_retrieve_data_fail() {
		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		$this->assertFalse( $this->endpoint->can_retrieve_data() );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::can_retrieve_data()
	 */
	public function test_can_retrieve_data_pass() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $user_id );

		$this->assertTrue( $this->endpoint->can_retrieve_data() );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::can_save_data()
	 */
	public function test_can_save_data_fail() {
		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		$this->assertFalse( $this->endpoint->can_save_data() );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::can_save_data()
	 */
	public function test_can_save_data_pass() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $user_id );

		$this->assertTrue( $this->endpoint->can_save_data() );
	}

	/**
	 * @covers WPSEO_Configuration_Endpoint::register()
	 */
	public function test_register() {
		global $wp_rest_server;
		$wp_rest_server = new WPSEO_WP_REST_Server_Mock();

		$this->endpoint->register();

		$endpoints = $wp_rest_server->get_endpoints();

		$this->assertTrue( isset( $endpoints[ '/' . WPSEO_Configuration_Endpoint::REST_NAMESPACE ] ) );
		$this->assertTrue( isset( $endpoints[ $this->endpoint->get_route( WPSEO_Configuration_Endpoint::ENDPOINT_RETRIEVE ) ] ) );
		$this->assertTrue( isset( $endpoints[ $this->endpoint->get_route( WPSEO_Configuration_Endpoint::ENDPOINT_STORE ) ] ) );
	}
}
