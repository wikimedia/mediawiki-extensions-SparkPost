<?php

namespace MediaWiki\SparkPost;

use MailAddress;
use MediaWikiTestCase;
use MWException;

/**
 * Test for SPHooks code.
 *
 * @author Derick Alangi
 * @coversDefaultClass \MediaWiki\SparkPost\SPHooks
 */
class SPHooksTest extends MediaWikiTestCase {
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

		$this->setExpectedException(
			MWException::class, 'Please update your LocalSettings.php with the correct SparkPost API key.'
		);

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

		// Note: Add a newline at the end of the response
		$expected = '{"errors": [ {"message": "Forbidden."} ]}' . "\n";
		$actual = SPHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);

		$this->assertSame( $expected, $actual );
	}
}
