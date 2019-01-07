<?php

namespace MediaWiki\SparkPost;

use Exception;
use MailAddress;
use MWException;
use RequestContext;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use SparkPost\SparkPost;

/**
 * Hooks for SparkPost extension for MediaWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Alangi Derick <alangiderick@gmail.com>
 *
 * @link https://www.mediawiki.org/wiki/Extension:SparkPost Documentation
 * @ingroup Extensions
*/

class SPHooks {

	/**
	 * Hook handler to send e-mails
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 *
	 * @return bool
	 */
	public static function onAlternateUserMailer(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body
	) {
		$conf = RequestContext::getMain()->getConfig();

		// From "wgSparkPostAPIKey" in LocalSettings.php when defined.
		$sparkpostAPIKey = $conf->get( 'SparkPostAPIKey' );

		if ( $sparkpostAPIKey === "" || !isset( $sparkpostAPIKey ) ) {
			throw new MWException(
				'Please update your LocalSettings.php with the correct SparkPost API key.'
			);
		}

		$httpClient = new GuzzleAdapter( new Client() );
		$sparkpost = new SparkPost( $httpClient, [ 'key' => $sparkpostAPIKey ] );

		return self::sendEmail( $headers, $to, $from, $subject, $body, $sparkpost );
	}

	/**
	 * Send Email via the SparkPost API
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @param SparkPost|null $sparkpost
	 * @throws Exception
	 *
	 * @return bool
	 */
	public static function sendEmail(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body,
		SparkPost $sparkpost = null
	) {
		$sparkpost->setOptions( [ 'async' => false ] );
		try {
			// Get $to and $from email addresses from the
			// `array` and `MailAddress` object respectively
			$results = $sparkpost->transmissions->post( [
				'content' => [
					'from' => [
						'name' => $from->name,
						'email' => $from->address
					],
					'subject' => $subject,
					'text' => $body
				],
				'recipients' => [
					[
						'address' => [
							'email' => $to[0]->address
						],
					],
				]
			] );
			if ( !$results ) {
				throw new MWException( "Bad response, email can't be sent!" );
			}
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

		return false;
	}
}
