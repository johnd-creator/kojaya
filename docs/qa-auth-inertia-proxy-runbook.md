# QA Authentication and Inertia Proxy Runbook

This runbook covers the web application at `qa.kojaya.id` when traffic follows
Cloudflare, a Cloudflare Tunnel, Nginx, and PHP-FPM.

## Diagnosis

The QA responses observed before this fix were `302` redirects with
`Location: http://qa.kojaya.id/...` even though the public request was HTTPS.
The login and logout responses were `Cache-Control: no-cache, private`, had
`Vary: X-Inertia`, and reported `cf-cache-status: DYNAMIC`. This indicates a
trusted-proxy/scheme problem rather than a Cloudflare cache hit or a failed
Laravel session. The login POST can establish the session while the browser
cannot follow the generated HTTP transition from an HTTPS page.

Laravel now trusts only the proxy addresses configured by `TRUSTED_PROXIES`.
The default is loopback because the expected cloudflared-to-Nginx hop is local;
the operator must confirm the address PHP receives from Nginx before deploying.

## Laravel environment contract

Set these values in the QA secret/environment configuration. Do not commit the
real `.env` file or secret values:

```dotenv
APP_URL=https://qa.kojaya.id
SESSION_DRIVER=database
SESSION_COOKIE=kojaya_qa_session
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=null
TRUSTED_PROXIES=127.0.0.1,::1
```

`SESSION_DOMAIN` should remain unset or `null` so the cookie is host-only. Do
not use `.kojaya.id`; QA and production must not share a session cookie. A
future production deployment should use its own host-only cookie name, for
example `kojaya_prod_session`.

`TRUSTED_PROXIES` must contain the exact IP address or CIDR of the proxy that
connects to PHP/Nginx. If that hop is not loopback, replace the example with
the verified private address. Do not use `*` unless the deployment topology
has no stable proxy address and the increased trust is explicitly accepted.

After changing environment/configuration values, deploy the application and
run the normal cache-clear step:

```bash
php artisan optimize:clear
```

## Nginx contract

Nginx configuration is outside this repository. Before changing it, inspect
the active configuration and identify the address PHP receives:

```bash
sudo nginx -T
```

For a FastCGI server, the effective configuration must preserve the public
host and scheme. The following is an intent-level example; verify the PHP-FPM
socket, server name, and include paths on the QA host:

```nginx
# http context: preserve the scheme supplied by the tunnel.
map $http_x_forwarded_proto $kojaya_forwarded_proto {
    default $http_x_forwarded_proto;
    ""      $scheme;
}

server {
    server_name qa.kojaya.id;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param HTTP_HOST              $host;
        fastcgi_param HTTP_X_REAL_IP         $remote_addr;
        fastcgi_param HTTP_X_FORWARDED_FOR   $proxy_add_x_forwarded_for;
        fastcgi_param HTTP_X_FORWARDED_HOST  $host;
        fastcgi_param HTTP_X_FORWARDED_PROTO $kojaya_forwarded_proto;

        # The tunnel usually reaches Nginx over HTTP. Do not replace the
        # forwarded HTTPS scheme with the internal $scheme value.
        fastcgi_param HTTPS $https if_not_empty;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock; # verify this path
    }
}
```

If the active configuration uses `proxy_pass` instead of FastCGI, preserve the
same `Host`, `X-Real-IP`, `X-Forwarded-For`, `X-Forwarded-Host`, and
`X-Forwarded-Proto` semantics with `proxy_set_header`. The exact config must be
reviewed on the QA host; this repository change does not apply or claim any
server change.

Validate and reload only after reviewing the rendered configuration:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Cloudflare cache contract

The observed QA responses were dynamic and private, so no Cloudflare cache
change is required by the evidence from this incident. If a QA Cache Rule is
present or added, it must bypass authenticated and Inertia traffic. A suitable
rule expression to validate in the Cloudflare dashboard is:

```text
(http.host eq "qa.kojaya.id" and (
  http.request.method ne "GET" or
  http.request.headers["x-inertia"][0] eq "true" or
  http.cookie contains "kojaya_qa_session=" or
  http.request.uri.path in {"/login" "/logout" "/dashboard"}
))
```

Set the matching action to **Cache eligibility: Bypass**. POST login/logout
responses must never be cached, and authenticated HTML or Inertia responses
must not be served interchangeably. Confirm the expression against the
Cloudflare account's current Rules language before saving it; no Cloudflare
rule was changed by this task.

## Header-only verification

These checks intentionally discard the response body and redact cookie values:

```bash
curl -sS -D - -o /dev/null https://qa.kojaya.id/login \
  | sed -E 's/(Set-Cookie: [^=]+=)[^;]*/\1<redacted>/I'
```

For an HTTPS request reaching Laravel through the verified proxy, generated
redirects must remain `https://qa.kojaya.id/...`, and responses should retain
private/no-cache behavior and the Inertia `Vary` header.
