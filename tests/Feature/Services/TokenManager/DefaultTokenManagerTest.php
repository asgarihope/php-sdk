<?php

namespace Tests\Feature\Services\TokenManager;

use PHPUnit\Framework\TestCase;
use Radeir\Services\TokenManager\DefaultTokenManager;
use Radeir\DTOs\RadeTokenDTO;

/**
 * Test helper class that allows overriding the cache file path
 */
class TestableTokenManager extends DefaultTokenManager
{
	private $testCacheFilePath;

	public function setTestCacheFilePath(string $path): void
	{
		$this->testCacheFilePath = $path;
	}

	protected function getTokenFileCacheAddress(): string
	{
		return $this->testCacheFilePath ?? parent::getTokenFileCacheAddress();
	}

	// Expose the protected method for testing
	public function exposedGetTokenFileCacheAddress(): string
	{
		return $this->getTokenFileCacheAddress();
	}
}

class DefaultTokenManagerTest extends TestCase
{
	private TestableTokenManager $tokenManager;
	private string $tempCacheFilePath;
	private string $uniqueId;

	protected function setUp(): void
	{
		parent::setUp();

		// Create a unique ID for test isolation
		$this->uniqueId = uniqid();

		// Configure test token manager
		$this->tokenManager = new TestableTokenManager([
			'username' => 'test_user',
			'password' => 'test_password',
			'scopes'   => ['test_scope'],
			'baseUrl'  => 'https://test.example.com',
		]);

		// Use a temporary file path for testing
		$this->tempCacheFilePath = __DIR__ . '/token_cache_test_' . $this->uniqueId . '.json';
		$this->tokenManager->setTestCacheFilePath($this->tempCacheFilePath);
	}

	protected function tearDown(): void
	{
		// Clean up test cache file
		if (file_exists($this->tempCacheFilePath)) {
			unlink($this->tempCacheFilePath);
		}

		parent::tearDown();
	}

	public function testSaveAndLoadToken(): void
	{
		// Arrange
		$accessToken = 'test_access_token_' . $this->uniqueId;
		$expireAt = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

		// Act - Save the token
		$savedToken = $this->tokenManager->saveToken($accessToken, $expireAt);

		// Assert - Token DTO was created correctly
		$this->assertInstanceOf(RadeTokenDTO::class, $savedToken);
		$this->assertEquals($accessToken, $savedToken->getAccessToken());
		$this->assertTrue($savedToken->valid());

		// Assert - Cache file was created with correct content
		$this->assertFileExists($this->tempCacheFilePath);
		$fileContent = file_get_contents($this->tempCacheFilePath);
		$tokenData = json_decode($fileContent, true);
		$this->assertIsArray($tokenData);
		$this->assertEquals($accessToken, $tokenData['token']);
		$this->assertEquals($expireAt, $tokenData['expireAt']);

		// Act - Load the token
		$loadedToken = $this->tokenManager->loadToken();

		// Assert - Loaded token matches saved token
		$this->assertInstanceOf(RadeTokenDTO::class, $loadedToken);
		$this->assertEquals($accessToken, $loadedToken->getAccessToken());
		$this->assertEquals($expireAt, $loadedToken->getExpireAt());
		$this->assertTrue($loadedToken->valid());
	}

	public function testLoadTokenReturnsNullWhenFileDoesNotExist(): void
	{
		// Arrange - Ensure cache file doesn't exist
		if (file_exists($this->tempCacheFilePath)) {
			unlink($this->tempCacheFilePath);
		}

		// Act
		$result = $this->tokenManager->loadToken();

		// Assert
		$this->assertNull($result);
	}

	public function testLoadTokenReturnsNullWithInvalidJsonContent(): void
	{
		// Arrange - Create cache file with invalid content
		file_put_contents($this->tempCacheFilePath, 'invalid json content');

		// Act
		$result = $this->tokenManager->loadToken();

		// Assert
		$this->assertNull($result);
	}

	public function testLoadTokenReturnsNullWithIncompleteData(): void
	{
		// Arrange - Create cache file with incomplete data
		file_put_contents($this->tempCacheFilePath, json_encode(['token' => 'test_token']));

		// Act
		$result = $this->tokenManager->loadToken();

		// Assert
		$this->assertNull($result);
	}

	public function testSaveTokenCreatesDirectoryIfNotExists(): void
	{
		// Arrange - Set cache path to non-existent directory
		$tempDir = sys_get_temp_dir() . '/token_test_dir_' . $this->uniqueId;
		$tempFilePath = $tempDir . '/token_cache.json';
		$this->tokenManager->setTestCacheFilePath($tempFilePath);

		// Act
		$this->tokenManager->saveToken('test_token', '2023-12-31 23:59:59');

		// Assert
		$this->assertDirectoryExists($tempDir);
		$this->assertFileExists($tempFilePath);

		// Clean up
		unlink($tempFilePath);
		rmdir($tempDir);
	}

	public function testGetTokenFileCacheAddress(): void
	{
		// Create a fresh instance without setting a test path
		$tokenManager = new TestableTokenManager([
			'username' => 'test_user',
			'password' => 'test_password',
			'scopes'   => ['test_scope'],
			'baseUrl'  => 'https://test.example.com',
		]);

		// Test the default path (without setting a test path)
		$defaultPath = $tokenManager->exposedGetTokenFileCacheAddress();

		// Verify the path is correctly formatted and points to the expected location
		$this->assertStringContainsString('cache' . DIRECTORY_SEPARATOR . 'token_cache.json', $defaultPath);

		// Test with a custom path
		$customPath = __DIR__ . '/custom_cache_path.json';
		$tokenManager->setTestCacheFilePath($customPath);
		$this->assertEquals($customPath, $tokenManager->exposedGetTokenFileCacheAddress());

		// Test with a non-existent directory
		$nonExistentDir = sys_get_temp_dir() . '/non_existent_dir_' . uniqid();
		$nonExistentPath = $nonExistentDir . '/token_cache.json';
		$tokenManager->setTestCacheFilePath($nonExistentPath);

		// Should return the path even though the directory doesn't exist yet
		$this->assertEquals($nonExistentPath, $tokenManager->exposedGetTokenFileCacheAddress());
	}
}
