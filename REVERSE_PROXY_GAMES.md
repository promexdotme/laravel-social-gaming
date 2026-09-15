# ⚡ Games CDN & Reverse Proxy Guide

This casino platform supports streaming slot and table games directly from the official **Hosted Games CDN** (`clients.377.live`), or running them locally on your own server.

---

## Option 1: Stream from Hosted CDN (Recommended - 0GB Local Storage)

Instead of downloading and hosting 20GB-40GB of slot assets, you can reverse-proxy the games directly through your domain. This ensures games run seamlessly under your SSL certificate with **zero mixed-content warnings**.

### Nginx Configuration

Add the following location block inside your casino's Nginx `server { ... }` configuration:

```nginx
location /games/ {
    proxy_pass https://clients.377.live/games/;
    proxy_set_header Host clients.377.live;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_ssl_server_name on;
    proxy_buffering off;
    proxy_cache_bypass $http_upgrade;
    add_header Content-Security-Policy "upgrade-insecure-requests" always;
}
```

Reload Nginx:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

Now, any game request to `https://yourdomain.com/games/...` is securely proxied from the high-speed CDN.

---

## Option 2: Download Local Game Packs

If you prefer hosting games locally on your own storage:

1. Log in to your Casino Admin: `https://yourdomain.com/liteback`
2. Navigate to **Store & License** (`/liteback/store`)
3. Under **Game Packs & Content Repository**, click **Download Pack** next to:
   - Pragmatic Play Bundle
   - Amatic Classic Collection
   - NetEnt Video Slots
   - Provably Fair Mini-Games
4. Packs are verified, downloaded, and unpacked automatically into your `/games/` directory.

---

## Protocol-Relative Slots Engine
All HTML5 game clients (including Pragmatic GS2C slot engines) are pre-patched to use protocol-relative URLs (`//location.hostname`), ensuring 100% clean HTTPS/SSL operation without mixed-content browser blocks.