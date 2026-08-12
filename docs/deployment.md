# Deployment — Hostinger VPS

Everything in `docs/operations.md` still applies once this is live; that page is
what has to keep *running*, this one is how it gets there. Development is on
Windows and XAMPP, production is Linux and nginx, and most of what goes wrong in
that move is silent on both ends.

## What the box needs

Ubuntu 22.04 or 24.04. Match the PHP version to `composer.json` (`^8.2`) and use
the same one everywhere — the CLI that runs the scheduler must be the same
binary family as the FPM pool, or a migration passes and a page still fails.

```bash
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl unzip git
```

`php8.3-gd` is not optional — QR codes and the DomPDF receipts both need it, and
the failure only shows up when a guest tries to download a receipt.

Node is required **on the server**, because `public/build` is gitignored and
therefore does not travel with the repo. A clone with no build step renders the
site completely unstyled.

## HTTPS, and why it is an Alpine problem

`bootstrap/app.php` now declares `trustProxies` at the loopback. Whether you
strictly need it depends on your topology, and it is worth knowing which case
you are in:

| Topology | Does Laravel see HTTPS? |
|---|---|
| nginx `listen 443 ssl` → PHP-FPM over FastCGI | Yes on its own — Debian's `fastcgi_params` sets `HTTPS on` |
| Anything else in front (Cloudflare, a load balancer, a second proxy hop) | **Only via `X-Forwarded-Proto`, which is what `trustProxies` enables** |

The second row is the common one, because free Cloudflare in front of a VPS is
the usual setup — and Cloudflare's "Flexible" SSL mode reaches your origin on
plain **port 80** while telling the browser the page is HTTPS. Then PHP never
sets `HTTPS`, `$request->secure()` is false, `asset()` emits `http://`, and the
browser blocks every one of those as mixed content. Alpine never loads: the five
views using `x-cloak` stay invisible, the nine using `x-data` ignore clicks.
Nothing errors.

**If you put Cloudflare in front, loopback is not enough** — the forwarding
request arrives from a Cloudflare address, not `127.0.0.1`, so add their ranges
to the `at:` list in `bootstrap/app.php`. Better: use Cloudflare's **Full
(strict)** mode with a real certificate on the origin, so the origin is HTTPS
too and the question stops mattering.

Set `APP_URL=https://…` regardless. During a web request `asset()` follows the
request, but the scheduler and any queued mail have no request to follow and
fall back to `APP_URL` — get it wrong and guests receive booking links that
point at `http://localhost`.

## nginx

`/etc/nginx/sites-available/farmershostel` — replace the domain and the PHP
socket version.

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name example.com www.example.com;

    root /var/www/aux_system/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    charset utf-8;

    # Payment proofs are photographed receipts and arrive large. nginx's 1M
    # default rejects them with a 413 before PHP ever sees the upload.
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Reverb. /app is the browser's websocket, /apps is the server-to-server
    # events API — both are required, and proxying only the first gives you a
    # console that connects and then never receives anything.
    location ~ ^/(app|apps) {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* { deny all; }

    error_page 404 /index.php;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/farmershostel /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx
```

Deliberately **not** in the `\.php$` block: an explicit
`fastcgi_param HTTP_X_FORWARDED_PROTO $scheme`. It reads like sensible
hardening and it is a trap. nginx already forwards a client's real
`X-Forwarded-Proto` to FastCGI as `HTTP_X_FORWARDED_PROTO` with no
configuration at all, and setting it explicitly *overwrites* that with nginx's
own scheme — which behind Cloudflare Flexible is `http`, because Cloudflare
reached this box on port 80. The header that said "the browser is on HTTPS"
gets replaced with "no it isn't", and the mixed-content bug comes straight
back. Leave it out; `trustProxies` in `bootstrap/app.php` is the thing that
decides whether that header can be believed, and it is the right place for the
decision.

The port-80 block above also assumes the origin terminates TLS. Behind
Cloudflare Flexible it is an infinite redirect loop — another reason to use
Full (strict).

Note there is no `location /storage`. Payment proofs and receipts live on the
`local` disk and are served through controllers that check who is asking
(`PaymentVerificationController`, `ReceiptController`). Do not "fix" this by
running `storage:link` and serving them statically — that publishes every
guest's uploaded receipt to anyone who can guess a filename.

## The three background processes

### Scheduler — the one that costs money

`sudo -u www-data crontab -e`:

```bash
* * * * * cd /var/www/aux_system && /usr/bin/php artisan schedule:run >> /var/log/farmershostel/scheduler.log 2>&1
```

**Not `>> /dev/null`.** A cron entry with a wrong path fails silently and
`operations.md` documents a full year lost to exactly that — the whole point of
this line is that a broken path leaves evidence. Check the log has recent
entries, then use the data-level check in `operations.md` (the count of holds
that should already have expired, which must be `0`) as the real proof.

### Reverb

`/etc/systemd/system/farmershostel-reverb.service`:

```ini
[Unit]
Description=Farmers Hostel - Reverb websocket server
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/aux_system
ExecStart=/usr/bin/php /var/www/aux_system/artisan reverb:start
Restart=always
RestartSec=3
StandardOutput=append:/var/log/farmershostel/reverb.log
StandardError=append:/var/log/farmershostel/reverb.log

[Install]
WantedBy=multi-user.target
```

It binds to `127.0.0.1:8081` per `REVERB_SERVER_HOST`, so it is unreachable from
the internet except through nginx — which is what gives it TLS. Do not open 8081
in the firewall.

### Queue worker

`/etc/systemd/system/farmershostel-queue.service` — same as above with:

```ini
Description=Farmers Hostel - queue worker
ExecStart=/usr/bin/php /var/www/aux_system/artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Nothing implements `ShouldQueue` today, so this works an empty queue. Install it
anyway: `operations.md` is explicit that the staff OTP is the first thing likely
to be queued, and an unworked queue there means nobody can log in at all. Having
the worker already supervised is what makes that change safe later.

`--max-time=3600` recycles the process hourly. A long-lived worker holds the
code it booted with, so without it a deploy's new code is never picked up.

```bash
sudo mkdir -p /var/log/farmershostel && sudo chown www-data:www-data /var/log/farmershostel
sudo systemctl daemon-reload && sudo systemctl enable --now farmershostel-reverb farmershostel-queue
```

## First deploy

```bash
sudo git clone <repo> /var/www/aux_system && cd /var/www/aux_system
composer install --no-dev --optimize-autoloader
npm ci
cp .env.production.example .env    # then fill in every CHANGEME
php artisan key:generate
php artisan migrate --force
```

Then permissions — PHP-FPM and the CLI both run as `www-data`, and a writable
`storage` is the difference between a working site and a 500 with nothing in the
log (because the log is what it could not write):

```bash
sudo chown -R www-data:www-data /var/www/aux_system
sudo chmod -R 775 /var/www/aux_system/storage /var/www/aux_system/bootstrap/cache
sudo chmod 640 /var/www/aux_system/.env
```

Only then build and cache — **in this order**:

```bash
npm run build
php artisan config:cache
php artisan route:cache
```

Two ordering rules, both load-bearing:

- `.env` must be final **before** `npm run build`, because every `VITE_*` value
  is compiled into the JavaScript bundle. Editing `.env` afterwards does not
  change the bundle, and the browser keeps dialling the old Reverb host.
- `npm run build` runs `php artisan view:cache` first, and Tailwind scans
  *compiled* Blade. Building against a cold view cache silently drops utilities
  only referenced inside components. Never run a bare `vite build`.

## Subsequent deploys

```bash
php artisan down --render="errors::503"
git pull
composer install --no-dev --optimize-autoloader
npm ci
php artisan migrate --force
npm run build
php artisan config:cache
php artisan route:cache
sudo systemctl restart farmershostel-reverb farmershostel-queue
php artisan up
```

Restarting the two services is not optional. Both are long-lived PHP processes
holding the old code in memory; skip it and they keep serving the previous
deploy indefinitely, with no indication that they are.

## Proving it actually works

Do these in order. The first two are the ones that catch the Alpine failure
before a guest does.

```bash
curl -sI https://example.com | grep -i strict-transport
```

HSTS is only sent when `$request->secure()` is true, so its presence is a
one-line proof that the proxy configuration is right. **Missing means Alpine is
about to be broken** — go back to the HTTPS section.

```bash
curl -s https://example.com | grep -o 'src="[^"]*alpine[^"]*"'
```

Must print an `https://` URL. An `http://` one is the mixed-content bug.

Then in the browser console on a public page:

- `typeof Alpine` → `"object"`. Anything else and every `x-data` block is inert.
- Console must show no "Blocked loading mixed active content".
- On a staff console page, `Echo.connector.pusher.connection.state` →
  `"connected"`. `"connecting"` that never settles means nginx is not proxying
  `/app`, or the bundle was built with the wrong `VITE_REVERB_*`.

Finally, confirm the things that fail silently:

```bash
systemctl status farmershostel-reverb farmershostel-queue --no-pager
tail -5 /var/log/farmershostel/scheduler.log
```

And log in as staff end to end — with `STAFF_OTP_ENABLED=true` a broken mailer
locks out every staff account, and the only way to find out is to try it.

## If the site renders unstyled

Check `public/hot` first, before anything else. A file left behind by a dead
`npm run dev` points every asset at a Vite server that is not listening. It is
gitignored, so `git pull` cannot bring it — but a manual folder upload can.
Delete it.
