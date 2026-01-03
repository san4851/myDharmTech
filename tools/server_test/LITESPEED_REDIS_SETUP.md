# LiteSpeed Redis Cache Configuration Guide

## Overview

LiteSpeed Cache uses Redis for object caching, and it's configured to use a Unix socket at `/tmp/redis.sock` by default. This is more efficient than TCP/IP connections for local Redis instances.

## Current Configuration

Your monitoring script is now configured to connect to Redis via Unix socket at `/tmp/redis.sock`, which matches your LiteSpeed setup.

## Verifying Redis Socket

To verify the Redis socket exists and is accessible:

```bash
# Check if socket file exists
ls -la /tmp/redis.sock

# Test Redis connection via socket
redis-cli -s /tmp/redis.sock ping
# Should return: PONG

# Get Redis info
redis-cli -s /tmp/redis.sock info server
```

## LiteSpeed Redis Configuration

### Via WordPress Admin (LiteSpeed Cache Plugin)

1. **Navigate to**: WordPress Admin → LiteSpeed Cache → Cache → Object
2. **Enable Object Cache**: Toggle ON
3. **Object Cache Method**: Select "Redis"
4. **Redis Hostname**: Leave empty or use `/tmp/redis.sock`
5. **Redis Port**: Leave empty (not needed for socket)
6. **Redis Password**: Leave empty if not set
7. **Save Changes**

### Via wp-config.php

Add these constants to your `wp-config.php`:

```php
// Redis Object Cache for LiteSpeed
define('WP_CACHE', true);
define('WP_CACHE_KEY_SALT', 'udaipurupdate.com');
define('WP_REDIS_HOST', '/tmp/redis.sock');
define('WP_REDIS_PORT', 0); // 0 for socket
define('WP_REDIS_PASSWORD', ''); // Leave empty if no password
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);
```

## Redis Service Management

### Check Redis Status

```bash
# Check if Redis is running (via socket)
redis-cli -s /tmp/redis.sock ping

# Check Redis process
ps aux | grep redis

# Check Redis logs (location varies by installation)
tail -f /var/log/redis/redis-server.log
```

### Restart Redis Service

```bash
# For systemd
sudo systemctl restart redis
# or
sudo systemctl restart redis-server

# For cPanel/WHM
/scripts/restartsrv_redis

# For LiteSpeed Web Admin
# Use the "Disable Redis Service" / "Enable Redis Service" option
```

### Flush Redis Cache

**Via WordPress Admin:**
- LiteSpeed Cache → Toolbox → Flush → Flush Object Cache

**Via Command Line:**
```bash
redis-cli -s /tmp/redis.sock FLUSHALL
```

**Via PHP (in your monitoring script):**
```php
$redis = new Redis();
$redis->connect('/tmp/redis.sock');
$redis->flushAll();
```

## Monitoring Script Configuration

Your monitoring script (`monitor_config.php`) is already configured for LiteSpeed Redis:

```php
'redis' => [
    'enabled' => true,
    'socket' => '/tmp/redis.sock',  // Unix socket for LiteSpeed
    'host' => 'localhost',          // Fallback TCP/IP (not used if socket exists)
    'port' => 6379,                 // Fallback TCP/IP (not used if socket exists)
    'password' => '',                // Leave empty if no password
    'timeout' => 5,
],
```

## Troubleshooting

### Socket Permission Issues

If you get permission errors:

```bash
# Check socket permissions
ls -la /tmp/redis.sock

# Fix permissions (adjust user/group as needed)
sudo chown redis:redis /tmp/redis.sock
sudo chmod 666 /tmp/redis.sock  # Or 660 for more security
```

### Redis Not Responding

1. **Check if Redis is running:**
   ```bash
   redis-cli -s /tmp/redis.sock ping
   ```

2. **Check Redis configuration:**
   ```bash
   # Find Redis config file
   redis-cli -s /tmp/redis.sock CONFIG GET "*"
   
   # Check socket path in config
   grep "unixsocket" /etc/redis/redis.conf
   ```

3. **Restart Redis:**
   ```bash
   sudo systemctl restart redis
   ```

### Connection Refused

If you see "Connection refused" errors:

1. Verify Redis is listening on the socket:
   ```bash
   netstat -an | grep redis.sock
   # or
   ss -x | grep redis.sock
   ```

2. Check Redis logs for errors:
   ```bash
   tail -f /var/log/redis/redis-server.log
   ```

3. Verify socket path in Redis config matches `/tmp/redis.sock`

## Performance Tips

1. **Socket vs TCP/IP**: Unix sockets are faster for local connections (no network overhead)
2. **Persistent Connections**: The monitoring script opens/closes connections. For production apps, use persistent connections
3. **Memory Limits**: Monitor Redis memory usage:
   ```bash
   redis-cli -s /tmp/redis.sock INFO memory
   ```

## Testing Your Configuration

Run the monitoring script to test Redis connectivity:

```bash
php hosting_monitor.php
```

Look for the "Redis Connectivity" test result. It should show:
- Status: Success
- Type: unix_socket
- Socket: /tmp/redis.sock

## Additional Resources

- [LiteSpeed Cache Documentation](https://docs.litespeedtech.com/lscache/)
- [Redis Documentation](https://redis.io/documentation)
- [WordPress Object Cache](https://wordpress.org/support/article/editing-wp-config-php/#cache)
