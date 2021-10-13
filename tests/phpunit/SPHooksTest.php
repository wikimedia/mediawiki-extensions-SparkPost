<?php

namespace MediaWiki\SparkPost;

use MailAddress;
use MediaWikiIntegrationTestCase;
use MWException;
use SparkPost\SparkPostException;

/**
 * Test for SPHooks code.
 *
 * @author Derick Alangi
 * @coversDefaultClass \MediaWiki\SparkPost\SPHooks
 */
class SPHooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @param string $apiKey SparkPost API key
	 */
	public function setConfig( $apiKey ) {
		$this->setMwGlobals( 'wgSparkPostAPIKey', $apiKey );
	}

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 *
	 * @covers ::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		$this->setConfig( '' );

		$this->expectException( MWException::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct SparkPost API key.' );

		$actual = SPHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * @covers ::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerWithApiKeyAndInvalidDomain() {
		$this->setConfig( 'TestAPIKeyString' );

		$this->expectException( SparkPostException::class );
		$actual = SPHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}
}
