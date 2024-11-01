<?php
/**
 * Created by PhpStorm.
 * User: PerfectSolution, Patrick Tolvstein
 * Date: 11/06/2018
 * Time: 11.20
 */

/**
 * Class WC_CoolPay_Address
 */
class WC_CoolPay_Address {

	/**
	 * @param string $address
	 *
	 * @return mixed
	 */
	public static function get_street_name( $address ) {
		$house_number = self::get_house_number( $address );

		list( $street_name, $extension ) = explode( $house_number, $address, 2 );

		return trim( $street_name );
	}

	/**
	 * @param string $address
	 *
	 * @return string
	 */
	public static function get_house_number( $address ) {
		preg_match( '/\d+[A-Z]?/i', $address, $matches );

		return trim( reset( $matches ) );
	}

	/**
	 * @param string $address
	 *
	 * @return string
	 */
	public static function get_house_extension( $address ) {
		$house_number = self::get_house_number( $address );

		list( $street_name, $extension ) = explode( $house_number, $address, 2 );

		return trim( trim( $extension, "," ) );
	}
}