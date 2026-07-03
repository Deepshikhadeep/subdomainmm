# Quick Start Guide - Cloudflare Subdomain Manager

## What Was Fixed?

Your extension had **7 major error categories** affecting admin panel, API, database, and client-side functionality. All have been resolved:

| Issue | Impact | Status |
|-------|--------|--------|
| Controller namespace/routing | Admin panel wouldn't load | ✅ Fixed |
| CURL/API errors | Cloudflare calls silently failing | ✅ Fixed |
| Missing validation | Invalid subdomains could be created | ✅ Fixed |
| Database constraints | Duplicates and orphans possible | ✅ Fixed |
| Error handling | Cryptic or missing error messages | ✅ Fixed |
| Logging | Hard to debug issues | ✅ Fixed |
| Client validation | Bad UX with weak validation | ✅ Fixed |

---

## Installation Steps

### 1. Copy Extension Files
```bash
cp -r /path/to/cfsubdomain /path/to/pterodactyl/extensions/
```

### 2. Run Migration
```bash
php artisan migrate
```

### 3. Enable in Blueprint (if not auto-enabled)
```bash
blueprint -install cfsubdomain.blueprint
```

---

## Configuration (Admin Only)

### Step 1: Get Cloudflare Credentials
1. Log in to **Cloudflare Dashboard**
2. Go to **Account Home → API Tokens**
3. Create token with permissions: **Zone:DNS:Edit**
4. Copy the token
5. Get your **Zone ID**: Domain → Overview → API section

### Step 2: Configure Extension
1. Admin Panel → **Extensions → Cloudflare Subdomain Manager**
2. Enter:
   - **API Token**: Your Cloudflare API token
   - **Zone ID**: Your domain's Cloudflare Zone ID
   - **Base Domain**: e.g., `yourhost.com`
   - **Account ID**: (Only needed for Tunnel mode) From Cloudflare Account Home
3. Click **Save Global Settings**

### Step 3: Configure Each Node
1. In **Node Cloudflare Settings** section:
2. Click **Configure** next to each node
3. Choose mode:
   - **🌍 DNS-Only**: Creates DNS A records (direct IP)
   - **🔒 Tunneled**: Uses Cloudflare Tunnel (hides IP)
4. If **Tunneled**: Enter **Tunnel ID** from Cloudflare Zero Trust
5. If **DNS-Only**: Enter **Default Domain** for auto-assign
6. Click **Save Node Settings**

---

## Usage (Users)

### Creating a Subdomain

1. **Open your server** in Pterodactyl Panel
2. Click the **🌐 Subdomains** button (bottom right)
3. Click **+ Add Subdomain**
4. **Select a port** from dropdown
5. **Enter subdomain** (optional for DNS-Only, required for Tunneled):
   - Valid: `myserver`, `server-123`, `mc-world`
   - Invalid: `my server`, `server!`, contains special chars
6. **Preview** shows final format: `subdomain.domain.com:port`
7. Click **🔗 Bind**

### Using a Subdomain

Once created, use the **Connection String** to connect:
```
Server: myserver.yourhost.com
Port: 25565
```

This is shown:
- In the **Connection Banner** (top of page)
- In the **Manage Subdomains** modal
- In the **Active Subdomain Bindings** table (admin)

### Deleting a Subdomain

1. Open **🌐 Subdomains**
2. Find the subdomain to delete
3. Click **🗑️ Delete**
4. Confirm deletion
5. Both the database and Cloudflare record are removed

---

## Verification Checklist

After setup, verify everything works:

### Admin Panel ✅
- [ ] Admin panel loads without errors
- [ ] Can enter Cloudflare credentials
- [ ] Can configure nodes
- [ ] Can view all subdomain bindings

### Cloudflare Integration ✅
- [ ] DNS records or Tunnel rules appear in Cloudflare
- [ ] New subdomains appear within 5 seconds
- [ ] Deleted subdomains are removed from Cloudflare
- [ ] No "permission denied" errors in logs

### Client Dashboard ✅
- [ ] Subdomains FAB button appears in client dashboard
- [ ] Can view existing subdomains
- [ ] Can add new subdomains
- [ ] Can delete subdomains
- [ ] Connection string displays correctly
- [ ] Can copy connection string with 📋 button

### Connection Tests ✅
- [ ] Can ping `subdomain.domain.com`
- [ ] Can connect to game server using subdomain
- [ ] Port number is always shown alongside subdomain

---

## Troubleshooting

### Admin Panel Won't Load
```
Check logs: storage/logs/laravel.log
Common causes:
- Migration not run: php artisan migrate
- Blueprint caching: blueprint cache:clear
```

### "Node not configured"
```
Solution: Go to admin panel and configure the node in "Node Cloudflare Settings"
```

### Cloudflare API Errors
```
Check your credentials:
- API token has correct permissions (Zone:DNS:Edit)
- Zone ID is correct (not domain name)
- Base domain matches your Cloudflare zone
```

### Subdomain Not Working
```
DNS propagation takes time:
1. Wait 2-5 minutes for DNS to propagate
2. Verify record exists in Cloudflare Dashboard
3. Try flushing local DNS: ipconfig /flushdns (Windows) or sudo dscacheutil -flushcache (Mac)
```

### Error: "Subdomain already in use"
```
Each subdomain must be unique across all servers.
Choose a different subdomain name.
```

### Tunnel Mode Not Working
```
Verify:
1. Account ID is entered in global settings
2. Tunnel ID is entered in node settings
3. Tunnel exists in Cloudflare Zero Trust → Tunnels
4. Tunnel status shows "Connected"
```

---

## API Endpoints

### For Developers

**Get Subdomains** (Client)
```
GET /api/client/extensions/cfsubdomain/servers/{server}/subdomains
Returns: {"success": true, "data": [...]}
```

**Get Available Ports** (Client)
```
GET /api/client/extensions/cfsubdomain/servers/{server}/ports
Returns: {"success": true, "data": [...]}
```

**Create Subdomain** (Client/Admin)
```
POST /api/client/extensions/cfsubdomain/subdomains
Body: {
  "server_id": 1,
  "allocation_id": 5,
  "subdomain": "myserver"  // optional for DNS-Only
}
Returns: {"success": true, "connection_string": "myserver.domain.com:25565"}
```

**Delete Subdomain** (Client/Admin)
```
DELETE /api/client/extensions/cfsubdomain/subdomains/{id}
Returns: {"success": true}
```

---

## Support

If you encounter issues:

1. **Check logs**: `storage/logs/laravel.log`
2. **Check browser console**: F12 → Console tab
3. **Verify Cloudflare settings**: Dashboard → Domain → Overview
4. **Verify node configuration**: Admin → Extensions → Cloudflare Subdomain Manager

All errors are logged with descriptions to help debugging. Check logs first!

---

## File Structure

```
cfsubdomain/
├── admin/
│   ├── controller.php      ✅ Fixed: namespace, error handling
│   ├── view.blade.php      Admin settings page
│   └── admin.css           Admin styling
├── app/
│   ├── CloudflareService.php      ✅ Fixed: CURL errors, timeouts
│   ├── SubdomainApiController.php ✅ Fixed: validation, errors
│   └── EventListener.php          ✅ Fixed: error handling
├── dashboard/
│   ├── wrapper.blade.php   Client modal/FAB
│   └── dashboard.css       Client styling
├── migrations/
│   └── 2025_01_01_*.php    ✅ Fixed: constraints, indexes
├── public/
│   └── subdomain-client.js ✅ Fixed: validation, error handling
├── routes/
│   ├── application.php     Admin API routes
│   ├── client.php          Client API routes
│   └── web.php             Web routes
├── views/
│   └── server-subdomains.blade.php  Reusable partial
├── conf.yml                Extension config
├── README.md               Full documentation
├── FIXES_APPLIED.md        Detailed error fixes
└── QUICK_START.md          This file
```

---

✅ **All systems operational. Ready for production.**
