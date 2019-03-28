<?php

namespace SonicSearch;

use RuntimeException;

class CommandFailedException extends RuntimeException {
	public function __construct(SonicMessage &$request, SonicMessage &$response) {
		parent::__construct("Request: ". $request->serialize() .
							" failed:\nResponse: ". $response->serialize() . "\n");
	}
};
