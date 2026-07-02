# 🌐 Cloudflare Subdomain Manager

A **Blueprint extension** for Pterodactyl Panel that automatically binds Cloudflare subdomains to server ports.

## Features

- **Automatic subdomain binding** — when a port is created, prompt to assign a subdomain
- **Two routing modes per node:**
  - 🔒 **Tunneled** — routes through Cloudflare Tunnel (hides real IP)
  - 🌍 **DNS-Only** — creates DNS A record (direct IP)
- **Dynamic port support** — port is always shown alongside the subdomain
- **Connection string display** — `myserver.yourhost.com:31456` shown everywhere
- **Admin panel** — configure Cloudflare API key, per-node settings, manage all bindings
- **Client dashboard** — users can manage their own server subdomains via popup modal
- **Auto-cleanup** — when servers/ports are deleted, Cloudflare records are automatically removed

## Requirements

- [Pterodactyl Panel](https://pterodactyl.io/) v1.x
- [Blueprint Framework](https://blueprint.zip/) installed on your panel
- A Cloudflare account with:
  - API Token (with DNS edit permissions)
  - Zone ID for your domain
  - (Optional) Account ID + Tunnel for tunneled mode

## Installation

1. Copy this extension folder to your Pterodactyl panel server
2. Enable Developer Mode in Blueprint settings (`/admin/extensions` → Blueprint → developer: true)
3. Run: `blueprint -install cfsubdomain.blueprint`
4. Navigate to **Admin → Extensions → Cloudflare Subdomain Manager**
5. Enter your Cloudflare API Token, Zone ID, and Base Domain
6. Configure each node's mode (Tunneled or DNS-Only)

## File Structure

```
extention/
├── conf.yml                    # Blueprint extension configuration
├── assets/
│   └── icon.png                # Extension icon
├── admin/
│   ├── controller.php          # Admin panel controller (CRUD)
│   ├── view.blade.php          # Admin settings page
│   └── admin.css               # Admin panel styles
├── dashboard/
│   ├── wrapper.blade.php       # Client dashboard popup/FAB
│   └── dashboard.css           # Client dashboard styles
├── app/
│   ├── CloudflareService.php   # Cloudflare API service layer
│   ├── SubdomainApiController.php  # REST API controller
│   └── EventListener.php       # Server/port event handlers
├── routes/
│   ├── application.php         # Admin API routes
│   ├── client.php              # Client API routes
│   └── web.php                 # Web routes (reserved)
├── migrations/
│   └── 2025_01_01_000000_create_cfsubdomain_tables.php
├── views/
│   └── server-subdomains.blade.php  # Reusable partial
├── public/
│   └── subdomain-client.js     # Client-side JS module
└── private/
    └── README.md
```

## How It Works

1. **Admin configures** Cloudflare credentials + per-node mode
2. **User creates port** in Pterodactyl → extension shows subdomain popup
3. **Extension calls Cloudflare API** → creates DNS record or Tunnel rule
4. **Connection string stored** in DB → shown in UI as `subdomain.domain.com:PORT`
5. **Port is ALWAYS displayed** — subdomain is a friendly alias for the IP, not a port replacement

## API Endpoints

### Admin API (`/api/application/extensions/cfsubdomain/`)
- `GET /servers/{id}/subdomains` — List server subdomains
- `GET /servers/{id}/ports` — List available (unbound) ports
- `POST /subdomains` — Create subdomain binding
- `DELETE /subdomains/{id}` — Delete subdomain binding
- `GET /nodes/{id}/settings` — Get node CF settings

### Client API (`/api/client/extensions/cfsubdomain/`)
- Same endpoints, scoped to user's own servers

## License

MIT
