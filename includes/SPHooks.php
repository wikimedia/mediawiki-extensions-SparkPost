<?php
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
 * @author Derick Alangi <alangiderick@gmail.com>
 *
 * @link https://www.mediawiki.org/wiki/Extension:SparkPost
 * @ingroup Extensions
 */

namespace MediaWiki\SparkPost;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use MailAddress;
use MWException;
use RequestContext;
use SparkPost\SparkPost;

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
	 * @throws \Exception If self::sendEmail() fails for some reason
	 * @return string
	 */
	public static function onAlternateUserMailer(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body
	) {
		$config = RequestContext::getMain()->getConfig();
		// From "$wgSparkPostAPIKey" in LocalSettings.php when defined.
		$sparkpostAPIKey = $config->get( 'SparkPostAPIKey' );

		if ( $sparkpostAPIKey === '' || !isset( $sparkpostAPIKey ) ) {
			throw new MWException(
				'Please update your LocalSettings.php with the correct SparkPost API key.'
			);
		}

		$httpClient = new GuzzleAdapter( new Client() );
		$sparkpost = new SparkPost( $httpClient, [ 'key' => $sparkpostAPIKey ] );

		return self::sendEmail( $headers, $to, $from, $subject, $body, $config, $sparkpost );
	}

	/**
	 * Send Email via the SparkPost API
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @param \Config $config
	 * @param SparkPost|null $sparkpost
	 *
	 * @return string
	 * @throws \Exception If something wrong happens in trying to send the email
	 */
	public static function sendEmail(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body,
		$config,
		SparkPost $sparkpost = null
	) {
		$user = RequestContext::getMain()->getUser();
		// Get options parameters from $configs if set in LocalSetting.php
		// From "$wgSparkpostClickTracking", "$wgSparkpostOpenTracking" and
		// "$wgSparkpostTransactional" respectively.
		$click_tracking = $config->get( 'SparkPostClickTracking' );
		$open_tracking = $config->get( 'SparkPostOpenTracking' );
		$transactional = $config->get( 'SparkPostTransactional' );

		// T215249: Get value of $wgUserEmailUseReplyTo to see if it's "true";
		$reply_to = $config->get( 'UserEmailUseReplyTo' );

		if ( $sparkpost === null ) {
			throw new \Exception( "SparkPost object isn't set, process aborted!" );
		}

		$sparkpost->setOptions( [ 'async' => false ] );
		try {
			// Get $to and $from email addresses from the
			// `array` and `MailAddress` object respectively
			$results = $sparkpost->transmissions->post( [
				'options' => [
					'click_tracking' => $click_tracking ?: false,
					'open_tracking' => $open_tracking ?: false,
					'transactional' => $transactional ?: false
				],
				'content' => [
					'from' => [
						'name' => $from->name,
						'email' => $from->address
					],
					'reply_to' => $reply_to ? $user->getEmail() : null,
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
		} catch ( MWException $e ) {
			return $e->getMessage();
		}
	}
}
