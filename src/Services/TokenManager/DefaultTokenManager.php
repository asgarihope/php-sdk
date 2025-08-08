<?php

namespace Radeir\Services\TokenManager;

use Radeir\DTOs\RadeTokenDTO;

class DefaultTokenManager extends AbstractTokenManagerService
{
	public function loadToken(): ?RadeTokenDTO {
		$filePath = $this->getTokenFileCacheAddress();

		if (!file_exists($filePath)) {
			return null;
		}

		$content = file_get_contents($filePath);
		if ($content === false) {
			return null;
		}

		$data = json_decode($content, true);
		if (!is_array($data) || !isset($data['token'], $data['expireAt'])) {
			return null;
		}

		$radeTokenDTO = new RadeTokenDTO();
		$radeTokenDTO->setAccessToken($data['token']);
		$radeTokenDTO->setExpireAt($data['expireAt']);

		return $radeTokenDTO;
	}

	public function saveToken(string $access_token, string $expire_at): RadeTokenDTO {
		$radeTokenDTO = new RadeTokenDTO();
		$radeTokenDTO->setAccessToken($access_token);
		$radeTokenDTO->setExpireAt($expire_at);

		// Ensure directory exists
		$filePath = $this->getTokenFileCacheAddress();
		$dir = dirname($filePath);

		// Only try to create directory if it doesn't exist
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0755, true)) {
				throw new \RuntimeException('Failed to create directory: ' . $dir);
			}
		} elseif (!is_dir($dir)) {
			throw new \RuntimeException('Path exists but is not a directory: ' . $dir);
		}

		// Save the token to the cache file
		file_put_contents($filePath, json_encode([
			'token'    => $access_token,
			'expireAt' => $expire_at,
		]));

		return $radeTokenDTO;
	}

	// Change from private to protected to allow proper overriding in subclasses
	protected function getTokenFileCacheAddress(): string {
		// Use the original constant with proper directory separator normalization
		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, __DIR__ . '/../../../../cache/token_cache.json');
		// Normalize the path to remove any redundant elements
		$normalizedPath = realpath(dirname($path));

		// If normalizedPath is false, the directory doesn't exist yet
		if ($normalizedPath === false) {
			// Return the non-normalized but correctly formatted path
			return $path;
		}

		// Return the normalized path with the filename
		return $normalizedPath . DIRECTORY_SEPARATOR . 'token_cache.json';
	}
}
