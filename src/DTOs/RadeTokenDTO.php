<?php

namespace Radeir\DTOs;

class RadeTokenDTO
{

	private string $token;

	private int    $expireAt;

	public function setAccessToken(string $token): void {
		$this->token = $token;
	}

	public function getAccessToken(): string {
		return $this->token;
	}

	public function setExpireAt(string $expireAt): void {
		$this->expireAt = strtotime($expireAt) ?? 0;
	}

	public function valid(): bool {
		return isset($this->token, $this->expireAt) && $this->expireAt > time();
	}

	public function getExpireAt(): string {
		return date('Y-m-d H:i:s', $this->expireAt);
	}
}
