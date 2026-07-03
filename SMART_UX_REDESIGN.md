# Smart Mode-Based UX Redesign

## Overview

The Cloudflare Subdomain Manager now includes an intelligent, mode-aware user experience that automatically adapts the form based on the node's Cloudflare mode (Tunneled or DNS-Only).

---

## The Problem Solved

Previously:
- Same form for both Tunneled and DNS-Only nodes
- Users could create invalid configurations
- No guidance on what was required vs optional
- Generic error messages instead of preventive UI

Now:
- Dynamic form adapts to node mode
- Impossible to misconfigure
- Clear visual hints for what's required
- Proactive validation prevents errors

---

## TUNNELED MODE: Subdomain Required

When a user opens a server on a **Tunneled** node:

### Form Appearance
```
┌─────────────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                              [X]   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Bind New Subdomain                                      │
│                                                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🔒 Tunneled Mode: Subdomain is required for         │ │
│ │ tunnel routing.                                      │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                          │
│ Port                                                    │
│ ┌───────────────────────────────────────────┐           │
│ │ Select port...                          ▼ │           │
│ └───────────────────────────────────────────┘           │
│                                                          │
│ Subdomain *  (RED ASTERISK - REQUIRED)                  │
│ ┌──────────────────────────┐  ┌─────────────────────┐  │
│ │ e.g. gameserver       │  │ .yourhost.com       │  │
│ └──────────────────────────┘  └─────────────────────┘  │
│                                                          │
│ Preview                                                │
│ ┌───────────────────────────────────────────┐           │
│ │ gameserver.yourhost.com:25565             │           │
│ └───────────────────────────────────────────┘           │
│                                                          │
│ [Cancel]  [🔗 Bind]                                     │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Behavior

**Port Selection:**
- User clicks dropdown → sees available ports
- Ports auto-filled (no empty form)
- Shows IP address: "Port 25565 (0.0.0.0)"

**Subdomain Input:**
- **REQUIRED** - has red asterisk
- Placeholder: "e.g. gameserver (required)"
- Live validation as user types
- Must match pattern: `^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$`
- Max 63 characters

**Form Submission:**
- If subdomain is empty → Shows error: "Subdomain is required for tunneled mode"
- If format is invalid → Shows error: "Subdomain must contain only letters, numbers, and hyphens"
- If valid → Submits and creates tunnel rule

**Result Display:**
- Success shows: `gameserver.yourhost.com:25565`
- This is the connection string users share with players
- Direct connection through Cloudflare tunnel

### Example Flow

```
User opens server on Tunneled Node:
├─ Clicks "🌐 Subdomains"
├─ Modal opens, shows form
├─ Sees "🔒 Tunneled Mode" hint
├─ Selects port: "25565"
├─ Enters subdomain: "gameserver"
├─ Sees preview: "gameserver.yourhost.com:25565"
├─ Clicks [🔗 Bind]
├─ System validates: subdomain present ✓, format valid ✓
├─ Creates Cloudflare tunnel rule
└─ Success! Connection string ready
```

---

## DNS-ONLY MODE: Subdomain Optional

When a user opens a server on a **DNS-Only** node:

### Form Appearance
```
┌─────────────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                              [X]   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Bind New Subdomain                                      │
│                                                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🌍 DNS-Only Mode: Subdomain is optional. Leave      │ │
│ │ blank to use: server.yourhost.com                   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                          │
│ Port                                                    │
│ ┌───────────────────────────────────────────┐           │
│ │ Select port...                          ▼ │           │
│ └───────────────────────────────────────────┘           │
│                                                          │
│ Subdomain (optional) (GRAY TEXT - NOT REQUIRED)        │
│ ┌──────────────────────────┐  ┌─────────────────────┐  │
│ │ or leave blank for       │  │ .yourhost.com       │  │
│ │ default                  │  │                     │  │
│ └──────────────────────────┘  └─────────────────────┘  │
│                                                          │
│ Preview                                                │
│ ┌───────────────────────────────────────────┐           │
│ │ server.yourhost.com:25565                 │           │
│ │ (shows default if empty)                  │           │
│ └───────────────────────────────────────────┘           │
│                                                          │
│ [Cancel]  [🔗 Bind]                                     │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Behavior

**Port Selection:**
- Same as tunneled mode
- User clicks dropdown
- Shows: "Port 25566 (192.168.1.1)"

**Subdomain Input:**
- **OPTIONAL** - gray "(optional)" label
- Placeholder: "or leave blank for default"
- User can leave empty
- If they type something, validates format

**Two Scenarios:**

**Scenario A: User Provides Subdomain**
```
User types: "pvp"
↓
Preview shows: pvp.yourhost.com:25566
↓
Clicks [🔗 Bind]
↓
Success shows: pvp.yourhost.com:25566
↓
Creates DNS A record for "pvp" → node IP:port
```

**Scenario B: User Leaves Empty (DEFAULT)**
```
User leaves field blank
↓
Preview shows: server.yourhost.com:25566
(uses default domain from node config)
↓
Clicks [🔗 Bind]
↓
Success shows: server.yourhost.com:25566
↓
Creates DNS A record for "server" → node IP:port
```

### Example Flow - With Subdomain

```
User opens server on DNS-Only Node:
├─ Clicks "🌐 Subdomains"
├─ Modal opens, shows form
├─ Sees "🌍 DNS-Only Mode" hint showing default
├─ Selects port: "25566"
├─ Enters subdomain: "pvp"
├─ Sees preview: "pvp.yourhost.com:25566"
├─ Clicks [🔗 Bind]
├─ System validates: format valid ✓ (if provided)
├─ Creates Cloudflare DNS A record
└─ Success! Connection string ready
```

### Example Flow - Default

```
User opens server on DNS-Only Node:
├─ Clicks "🌐 Subdomains"
├─ Modal opens, shows form
├─ Sees "🌍 DNS-Only Mode" hint
├─ Selects port: "27015"
├─ LEAVES subdomain EMPTY
├─ Sees preview: "server.yourhost.com:27015"
├─ Clicks [🔗 Bind]
├─ System validates: field empty → use default
├─ Creates Cloudflare DNS A record for "server"
└─ Success! Shows: server.yourhost.com:27015
```

---

## Visual Differences

### Form State Comparison

| Element | Tunneled | DNS-Only |
|---------|----------|----------|
| **Hint Box Color** | Blue (🔒) | Blue (🌍) |
| **Hint Text** | "Subdomain required for tunnel routing" | "Subdomain optional. Leave blank to use: ..." |
| **Label** | "Subdomain *" (red asterisk) | "Subdomain (optional)" (gray) |
| **Placeholder** | "(required)" | "(or leave blank for default)" |
| **Submit with Empty** | ERROR | SUCCESS (uses default) |
| **Cloudflare Action** | Create tunnel rule | Create DNS A record |

---

## Implementation Details

### Backend Changes

**API Endpoint:** `GET /api/client/extensions/cfsubdomain/servers/{uuid}/ports`

Now returns:
```json
{
  "success": true,
  "data": [
    { "id": 1, "port": 25565, "ip": "0.0.0.0" },
    { "id": 2, "port": 25566, "ip": "0.0.0.0" }
  ],
  "nodeMode": "tunneled",           // NEW
  "defaultDomain": "server"         // NEW
}
```

### Frontend Changes

**displayFormByMode() Function:**
```javascript
// Checks nodeMode and sets:
// - cf-subdomain-label (required vs optional)
// - cf-subdomain-input (required attribute, placeholder)
// - cf-mode-hint (shows appropriate message)
```

**Form Validation:**
```javascript
// Tunneled: empty subdomain → error
// DNS-Only: empty subdomain → OK (uses default)
```

### Service Layer

**CloudflareService::createSubdomain()**
```php
// Tunneled: requires non-empty $subdomain
// DNS-Only: if empty, uses $nodeSettings->default_domain
```

---

## Error Scenarios

### Tunneled Mode Errors

**Empty Subdomain:**
```
❌ Subdomain is required for tunneled mode.
```

**Invalid Format:**
```
❌ Subdomain must contain only letters, numbers, and hyphens.
```

**Already Exists:**
```
❌ Subdomain "gameserver" is already in use.
```

### DNS-Only Mode Errors

**Invalid Format (if provided):**
```
❌ Subdomain must contain only letters, numbers, and hyphens.
```

**Already Exists (if provided):**
```
❌ Subdomain "pvp" is already in use.
```

**Node Not Configured:**
```
❌ Node not configured for Cloudflare.
Please ask admin to configure it in the admin panel.
```

---

## User Benefits

### Tunneled Node Users
- **Prevents misconfiguration** - Can't accidentally skip subdomain
- **Clear requirements** - Red asterisk + hint message
- **Guided workflow** - Form guides them step-by-step
- **Consistent setup** - Everyone who uses same node gets same experience

### DNS-Only Node Users
- **Flexibility** - Can use default or customize
- **Choice** - See what default is before committing
- **Less friction** - Can leave empty and move forward
- **Preview** - See exactly what domain will be used
- **Option to customize** - Add memorable subdomains if wanted

### All Users
- **Less errors** - Preventive UI instead of reactive errors
- **Better UX** - Clearer, more intuitive forms
- **Visual feedback** - Mode hints + live preview
- **Smart defaults** - System makes good choices

---

## Admin Configuration Notes

For this to work, admins must:

1. **Set global config:**
   - Cloudflare API Token
   - Zone ID
   - Account ID (for Tunnel)
   - Base Domain

2. **Configure each node:**
   - Choose Tunnel or DNS-Only
   - If Tunnel: Enter Tunnel ID
   - If DNS-Only: Enter default domain (e.g., "server")

---

## Examples

### Example 1: Tunneled Network Game Server

```
Admin Setup:
  Node: "Game-Node-1"
  Mode: Tunneled
  Tunnel ID: abc123xyz789

User Experience:
  1. Clicks "Subdomains"
  2. Sees hint: "🔒 Tunneled Mode: Subdomain required..."
  3. Selects port: 25565
  4. Enters subdomain: "survival"
  5. Preview shows: "survival.games.com:25565"
  6. Clicks Bind
  7. Success! Tunnel rule created
  8. Players connect to: survival.games.com:25565
```

### Example 2: DNS-Only Web Server

```
Admin Setup:
  Node: "Web-Node-2"
  Mode: DNS-Only
  Default Domain: "myapp"

User Experience:
  1. Clicks "Subdomains"
  2. Sees hint: "🌍 DNS-Only Mode: ... default: myapp.host.com"
  3. Selects port: 8080
  4. LEAVES subdomain EMPTY
  5. Preview shows: "myapp.host.com:8080"
  6. Clicks Bind
  7. Success! DNS record created
  8. Accessible at: myapp.host.com:8080
```

### Example 3: DNS-Only Custom Subdomain

```
Admin Setup:
  Node: "Web-Node-2"
  Mode: DNS-Only
  Default Domain: "myapp"

User Experience:
  1. Clicks "Subdomains"
  2. Sees hint about default: "myapp.host.com"
  3. Selects port: 9000
  4. Enters subdomain: "api"
  5. Preview shows: "api.host.com:9000"
  6. Clicks Bind
  7. Success! DNS record created
  8. Accessible at: api.host.com:9000
  9. Note: Didn't use default, used custom "api"
```

---

## Summary

The Smart Mode-Based UX redesign creates an intelligent, adaptive experience where:

- **Tunneled Nodes** enforce required subdomains with clear UI
- **DNS-Only Nodes** offer flexible optional subdomains with sensible defaults
- **All Users** get preventive validation and clear guidance
- **Errors** become impossible instead of just being handled better
- **Defaults** work when users want simplicity
- **Customization** available when users want control

Result: A production-ready UX that's both powerful and intuitive.
