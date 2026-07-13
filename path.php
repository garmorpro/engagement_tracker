<?php

define("ROOT_PATH", realpath(dirname(__FILE__)));

// Allows overriding the production URL for local/staging environments by
// setting a real BASE_URL environment variable (e.g. in .env, if something
// earlier in the request already loaded it via Dotenv's putenv, or in the
// web server's own vhost/service config). Falls back to production if unset.
define("BASE_URL", getenv('BASE_URL') ?: "https://engagements.morganserver.com");