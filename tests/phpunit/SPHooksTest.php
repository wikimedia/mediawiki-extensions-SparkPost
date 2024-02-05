<?php

namespace MediaWiki\Extension\Test\SparkPost;

use Exception;
use MailAddress;
use MediaWiki\Extension\SparkPost\SPHooks;
use MediaWikiIntegrationTestCase;

/**
 * Test for SPHooks code.
 *
 * @author Derick Alangi
 * @covers \MediaWiki\Extension\SparkPost\SPHooks
 */
class SPHooksTest extends MediaWikiIntegrationTestCase {

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		$this->overrideConfigValue( 'SparkPostAPIKey', '' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct SparkPost API key.' );

		SPHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}
}
