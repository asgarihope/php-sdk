<?php

namespace Radeir\Services\TokenManager;

use Radeir\DTOs\RadeTokenDTO;

interface TokenManagerInterface
{

	public function saveToken(string $access_token, string $expire_at): RadeTokenDTO;

	public function loadToken(): ?RadeTokenDTO;

}
