#!/usr/bin/env python3
"""Replace Moodle database session handler with Redis in config.php."""
from __future__ import annotations

import re
import sys
from pathlib import Path

config = Path(sys.argv[1] if len(sys.argv) > 1 else "/var/www/moodle/config.php")
text = config.read_text()

redis_block = """$CFG->session_handler_class = '\\core\\session\\redis';
$CFG->session_redis_host = getenv('MOODLE_REDIS_HOST') ?: 'understandtech-redis-prod.eastus2.redis.azure.net';
$CFG->session_redis_port = (int) (getenv('MOODLE_REDIS_PORT') ?: 10000);
$CFG->session_redis_auth = getenv('MOODLE_REDIS_PASSWORD') ?: '';
$CFG->session_redis_encrypt = ['verify_peer' => false, 'verify_peer_name' => false];
"""

if "\\core\\session\\redis" in text:
    print("already_redis")
    sys.exit(0)

if "$CFG->session_handler_class" not in text:
    needle = "require_once(__DIR__ . '/lib/setup.php');"
    if needle not in text:
        print("ERROR: session handler and setup.php needle missing", file=sys.stderr)
        sys.exit(1)
    text = text.replace(needle, redis_block + "\n" + needle, 1)
else:
    text = re.sub(
        r"\$CFG->session_handler_class\s*=\s*'[^']*';\n",
        redis_block,
        text,
        count=1,
    )

config.write_text(text)
print("migrated_to_redis")
