# Complete User & Admin Experience Walkthrough

## TABLE OF CONTENTS
1. Admin Experience (Setup & Configuration)
2. User Experience (Subdomain Management)
3. Different Scenarios (Tunneled vs DNS-Only)
4. Error Handling
5. Complete End-to-End Workflow

---

## PART 1: ADMIN EXPERIENCE

### Step 1: Accessing the Admin Panel

Admin navigates to: `/admin/extensions/cfsubdomain`

They see a professional panel with:
- Breadcrumb navigation: Admin > Extensions > Cloudflare Subdomain Manager
- Header: "Cloudflare Subdomain Manager - Manage subdomains for your server ports"
- Three main sections below

---

### Step 2: Section 1 - Global Cloudflare Configuration

**What the admin sees:**

A box titled "Global Cloudflare Configuration" with 4 fields:

```
┌─────────────────────────────────────────────────────────────┐
│ ☁ GLOBAL CLOUDFLARE CONFIGURATION                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [Left Column]              [Right Column]                   │
│  ─────────────────────────  ─────────────────────────────   │
│  Cloudflare API Token       Cloudflare Zone ID              │
│  [••••••••••••••] (masked)   [a1b2c3d4e5f6..................] │
│  Stored securely. Used      Found in your Cloudflare       │
│  for all API calls.         dashboard → Overview → API      │
│                                                              │
│  [Left Column]              [Right Column]                   │
│  ─────────────────────────  ─────────────────────────────   │
│  Cloudflare Account ID      Base Domain                     │
│  [x9y8z7w6v5u4............] [yourhost.com.................] │
│  Required for Tunnel mode.  Subdomains created as          │
│  Found in Account Home.     name.yourhost.com              │
│                                                              │
│  [Save Global Settings] (button, bottom right)              │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**What the admin does:**

1. Enters Cloudflare API Token (password field for security)
2. Enters Zone ID (found in Cloudflare dashboard)
3. Enters Account ID (for Tunnel mode support)
4. Enters Base Domain (e.g., "yourhost.com")
5. Clicks "Save Global Settings"
6. Sees success alert: "Cloudflare settings saved successfully"

**Behind the scenes:**
- API token is encrypted in database
- Zone ID, Account ID, Base Domain stored securely
- Used for all subsequent subdomain operations

---

### Step 3: Section 2 - Per-Node Configuration

**What the admin sees:**

A table showing all nodes in the system:

```
┌──────────────────────────────────────────────────────────────────┐
│ 🖥 NODE CLOUDFLARE SETTINGS                     [7 node(s)]      │
├───────┬──────────────┬──────────────┬──────────┬──────────┬─────┤
│ Node  │ FQDN         │ Mode         │ Tunnel   │ Default  │ Act │
│ Name  │              │              │ ID       │ Domain   │     │
├───────┼──────────────┼──────────────┼──────────┼──────────┼─────┤
│Node 1 │node1.com     │ 🔒 Tunneled  │ abc123   │ —        │ ⚙️  │
├───────┼──────────────┼──────────────┼──────────┼──────────┼─────┤
│Node 2 │node2.com     │ 🌍 DNS-Only  │ —        │ server   │ ⚙️  │
├───────┼──────────────┼──────────────┼──────────┼──────────┼─────┤
│Node 3 │node3.com     │ ❓ Not       │ —        │ —        │ ⚙️  │
│       │              │ Configured   │          │          │     │
├───────┼──────────────┼──────────────┼──────────┼──────────┼─────┤
│Node 4 │node4.com     │ 🔒 Tunneled  │ def456   │ —        │ ⚙️  │
└───────┴──────────────┴──────────────┴──────────┴──────────┴─────┘
```

**What the admin does:**

1. Identifies a node to configure
2. Clicks the ⚙️ (gear) icon in the Actions column

**Modal opens:**

```
┌──────────────────────────────────────────────┐
│ ⚙️  CONFIGURE NODE: Node 1                   │
├──────────────────────────────────────────────┤
│                                              │
│ Node Mode                                    │
│ ◉ 🔒 Tunneled (Cloudflare Tunnel)           │
│ ○ 🌍 DNS-Only (Direct IP)                   │
│                                              │
│ Cloudflare Tunnel ID                         │
│ [abc123-def456-ghi789...................]     │
│ Find this in Cloudflare → Zero Trust →       │
│ Tunnels                                      │
│                                              │
│ [Cancel]  [Save Node Settings]               │
│                                              │
└──────────────────────────────────────────────┘
```

**If admin selects TUNNELED:**
- "Tunnel ID" field appears (required for tunnel routing)
- Help text explains how to find the ID
- Admin pastes the tunnel ID from Cloudflare

**If admin selects DNS-ONLY:**
- "Tunnel ID" field disappears
- "Default Domain" field appears
- Help text: "This will be used if user doesn't provide a subdomain"
- Admin enters: "server" (for example)

**Admin clicks "Save Node Settings":**
- Modal closes
- Table updates to show new mode
- Success alert appears

---

### Step 4: Section 3 - Active Subdomain Bindings (Monitoring)

**What the admin sees:**

A read-only monitoring table:

```
┌────────────────────────────────────────────────────────────┐
│ 🔗 ACTIVE SUBDOMAIN BINDINGS                  [42 total]   │
├──────────┬──────────────────────┬─────┬─────────┬──────────┤
│ Server   │ Connection String    │Port │ Mode    │ Created  │
├──────────┼──────────────────────┼─────┼─────────┼──────────┤
│ MC-001   │ gameserver.host.com  │25565│ DNS     │ Feb 14   │
├──────────┼──────────────────────┼─────┼─────────┼──────────┤
│ MC-002   │ pvp.host.com         │25566│ Tunnel  │ Feb 15   │
├──────────┼──────────────────────┼─────┼─────────┼──────────┤
│ MC-003   │ server.host.com      │27015│ DNS     │ Feb 16   │
└──────────┴──────────────────────┴─────┴─────────┴──────────┘
```

**What the admin can do:**

- View all subdomains across all servers
- See which mode each binding uses
- See creation dates
- Delete any binding (trash icon)
- Copy connection strings

**When admin deletes a binding:**
- Confirmation dialog appears
- If confirmed, Cloudflare record is cleaned up
- Database entry is deleted
- Table updates in real-time

---

## PART 2: USER EXPERIENCE

### User Scenario Setup

Admin has configured:
- Node 1: Tunneled mode (Tunnel ID: abc123)
- Node 2: DNS-Only mode (Default Domain: "server")

User owns a server on each node.

---

### SCENARIO A: User with TUNNELED Node Server

**Step 1: User opens server**

URL: `/server/a1b2c3d4e5`

User sees the normal server dashboard with:
- Server console, files, etc.
- Bottom-right corner: Blue FAB button with "🌐 Subdomains"
- Banner at top (if subdomains exist): "🔗 gameserver.host.com:25565" with copy button

**Step 2: User clicks FAB button**

Click location: Bottom-right "🌐 Subdomains" button

**Modal opens (loading state):**

```
┌─────────────────────────────────────┐
│ 🌐 Manage Subdomains         [X]   │
├─────────────────────────────────────┤
│                                     │
│         ⟳ Loading...                │
│                                     │
└─────────────────────────────────────┘
```

API call happens: `/api/client/extensions/cfsubdomain/servers/{uuid}/subdomains`

**Step 3: Modal loads (subdomains list)**

Since this is first time (no subdomains):

```
┌─────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                     [X]   │
├─────────────────────────────────────────────────┤
│                                                 │
│  Active Subdomains                              │
│  ┌───────────────────────────────────────────┐ │
│  │                                           │ │
│  │              No subdomains yet.           │ │
│  │         Add one to get started!           │ │
│  │                                           │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│  [+ Add Subdomain]                              │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Step 4: User clicks "+ Add Subdomain"**

Form appears:

```
┌──────────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                         [X]   │
├──────────────────────────────────────────────────────┤
│                                                      │
│ Bind New Subdomain                                   │
│                                                      │
│ ┌────────────────────────────────────────────────┐  │
│ │ 🔒 Tunneled Mode: Subdomain required for       │  │
│ │    tunnel routing.                              │  │
│ └────────────────────────────────────────────────┘  │
│ (Blue hint box with left border)                    │
│                                                      │
│ Port *                                               │
│ ┌────────────────────────────────────────────────┐  │
│ │ Select port...                              ▼ │  │
│ └────────────────────────────────────────────────┘  │
│                                                      │
│ Subdomain * (RED ASTERISK - REQUIRED)              │
│ ┌──────────────────────┐ ┌──────────────────────┐  │
│ │ myserver          │ │ .yourhost.com      │  │
│ └──────────────────────┘ └──────────────────────┘  │
│ (Note: Required indicator, red asterisk)            │
│                                                      │
│ Preview                                              │
│ ┌────────────────────────────────────────────────┐  │
│ │ myserver.yourhost.com                      │  │
│ │ (Shows in real-time as user types)             │  │
│ └────────────────────────────────────────────────┘  │
│                                                      │
│ [Cancel]  [🔗 Bind]                                │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Key differences from DNS-Only mode:**
- Blue hint box says: "🔒 Tunneled Mode: Subdomain required..."
- Label has RED ASTERISK: "Subdomain *"
- Placeholder: "myserver (required)"
- Cannot submit without subdomain
- Button will be disabled if subdomain is empty

**Step 5: User selects port**

Clicks dropdown:

```
┌────────────────────────────┐
│ Port 25565 (0.0.0.0)       │ ← Selected
│ Port 25566 (0.0.0.0)       │
│ Port 27015 (192.168.1.1)   │
└────────────────────────────┘
```

User selects: "Port 25565 (0.0.0.0)"

Dropdown closes, port shows: "25565"

**Step 6: User enters subdomain**

Types in subdomain field: "gameserver"

As they type, preview updates:
- Typed "g" → "g.yourhost.com"
- Typed "ga" → "ga.yourhost.com"
- ...continues...
- Typed "gameserver" → "gameserver.yourhost.com"

**Validation happens in real-time:**
- Must be letters, numbers, hyphens only ✓ (passes)
- Can't start/end with hyphen ✓ (passes)
- Must be 3-63 characters ✓ (passes)
- Can't already exist ✓ (passes)

**Step 7: User clicks [🔗 Bind]**

Button shows loading state: "⏳ Binding..."
Button is disabled during API call

API call: `POST /api/client/extensions/cfsubdomain/subdomains`

**Backend processes:**
1. Validates: subdomain is not empty (tunneled requirement)
2. Validates: format is correct
3. Checks: subdomain doesn't already exist
4. Gets node settings: mode = "tunneled", tunnel_id = "abc123"
5. Creates Cloudflare tunnel rule
6. Stores binding in database
7. Returns connection string

**Step 8: Success Screen**

```
┌─────────────────────────────────────┐
│ 🌐 Manage Subdomains         [X]   │
├─────────────────────────────────────┤
│                                     │
│                 ✅                  │
│          Subdomain Bound!            │
│                                     │
│       gameserver.yourhost.com       │
│         (green, clickable)           │
│                                     │
│  Connection string is copied to     │
│  clipboard for easy sharing.        │
│                                     │
│           [← Back]                  │
│                                     │
└─────────────────────────────────────┘
```

**Step 9: User returns to list**

Clicks "← Back"

Back to modal showing:

```
┌─────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                     [X]   │
├─────────────────────────────────────────────────┤
│                                                 │
│  Active Subdomains                              │
│  ┌───────────────────────────────────────────┐ │
│  │ gameserver.yourhost.com:25565  📋 [Copy] │ │
│  │ Port: 25565  |  Mode: 🔒 Tunnel      [🗑] │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│  [+ Add Subdomain]                              │
│                                                 │
└─────────────────────────────────────────────────┘
```

User can now:
- Copy connection string: "gameserver.yourhost.com:25565"
- Share with other players
- Delete it if needed
- Add another subdomain

---

### SCENARIO B: User with DNS-ONLY Node Server

**Same steps 1-3, but modal shows:**

Since no subdomains yet (same as Tunneled):

```
No subdomains yet. Add one to get started!
[+ Add Subdomain]
```

**Step 4: User clicks "+ Add Subdomain"**

Form appears with DIFFERENT configuration:

```
┌──────────────────────────────────────────────────────┐
│ 🌐 Manage Subdomains                         [X]   │
├──────────────────────────────────────────────────────┤
│                                                      │
│ Bind New Subdomain                                   │
│                                                      │
│ ┌────────────────────────────────────────────────┐  │
│ │ 🌍 DNS-Only Mode: Subdomain optional. Leave    │  │
│ │    blank to use: server.yourhost.com           │  │
│ └────────────────────────────────────────────────┘  │
│ (Blue hint box with different message)              │
│                                                      │
│ Port *                                               │
│ ┌────────────────────────────────────────────────┐  │
│ │ Select port...                              ▼ │  │
│ └────────────────────────────────────────────────┘  │
│                                                      │
│ Subdomain (optional) (GRAY - OPTIONAL)             │
│ ┌──────────────────────┐ ┌──────────────────────┐  │
│ │                   │ │ .yourhost.com      │  │
│ └──────────────────────┘ └──────────────────────┘  │
│ (Note: No asterisk, gray "(optional)" label)        │
│ Placeholder: "(or leave blank for default)"         │
│                                                      │
│ Preview                                              │
│ ┌────────────────────────────────────────────────┐  │
│ │ server.yourhost.com                        │  │
│ │ (Shows default, updates if user types)          │  │
│ └────────────────────────────────────────────────┘  │
│                                                      │
│ [Cancel]  [🔗 Bind]                                │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Key differences from Tunneled:**
- Blue hint shows default domain to use
- Label says: "Subdomain (optional)" in gray
- Placeholder: "(or leave blank for default)"
- Can submit EMPTY - no validation error
- Can also submit WITH subdomain

**Option 1: User leaves subdomain empty**

1. Selects port: "8080"
2. LEAVES subdomain field empty
3. Preview shows: "server.yourhost.com:8080"
4. Clicks [🔗 Bind]
5. API processes: "subdomain is empty, use default = 'server'"
6. Cloudflare creates A record for "server"
7. Connection string: "server.yourhost.com:8080"

**Option 2: User provides custom subdomain**

1. Selects port: "9000"
2. Types subdomain: "api"
3. Preview shows: "api.yourhost.com:9000"
4. Clicks [🔗 Bind]
5. API processes: "subdomain = 'api'"
6. Cloudflare creates A record for "api"
7. Connection string: "api.yourhost.com:9000"

Both options work equally well for DNS-Only nodes!

---

## PART 3: ERROR SCENARIOS

### Error 1: Tunneled Mode - Trying to submit empty subdomain

**User tries to bind without entering subdomain:**

1. Selects port: "25565"
2. Leaves subdomain empty
3. Clicks [🔗 Bind]
4. Client-side validation runs first
5. Shows error:

```
┌────────────────────────────────┐
│ ❌ Error                       │
├────────────────────────────────┤
│ Subdomain is required for      │
│ tunneled mode.                 │
│                                │
│         [Retry]                │
└────────────────────────────────┘
```

**User must fill in subdomain before proceeding.**

---

### Error 2: Invalid subdomain format

**User enters "game server" (with space):**

1. Types: "game server"
2. As they type, validation fails
3. Error shows:

```
❌ Subdomain must contain only letters, numbers, and hyphens.
```

**User must fix format before submitting.**

---

### Error 3: Subdomain already exists

**User tries to use a subdomain that's already in use:**

1. Enters: "gameserver"
2. Clicks [🔗 Bind]
3. API checks database
4. Finds duplicate
5. Returns error:

```
┌────────────────────────────────┐
│ ❌ Error                       │
├────────────────────────────────┤
│ Subdomain "gameserver" is      │
│ already in use.                │
│                                │
│         [Retry]                │
└────────────────────────────────┘
```

**User can retry with different name.**

---

### Error 4: Node not configured

**Admin hasn't configured the node yet:**

```
┌────────────────────────────────┐
│ ❌ Error                       │
├────────────────────────────────┤
│ Node not configured for        │
│ Cloudflare. Please ask admin   │
│ to configure it in admin panel.│
│                                │
│         [Retry]                │
└────────────────────────────────┘
```

**User must ask admin to configure the node.**

---

## PART 4: COMPLETE END-TO-END WORKFLOW

### Timeline

```
T=0:00  Admin goes to /admin/extensions/cfsubdomain
        └─ Sees empty form

T=0:15  Admin enters Cloudflare credentials
        ├─ API Token: "v1.xxxxx..."
        ├─ Zone ID: "a1b2c3..."
        ├─ Account ID: "x9y8..."
        └─ Base Domain: "gamehost.com"
        └─ Clicks [Save Global Settings]

T=0:30  Admin configures nodes
        ├─ Node 1 (game servers):
        │  ├─ Mode: Tunneled
        │  └─ Tunnel ID: "abc123..."
        ├─ Node 2 (web servers):
        │  ├─ Mode: DNS-Only
        │  └─ Default Domain: "web"
        └─ Clicks [Save] for each

T=1:00  Admin creates new Pterodactyl server
        ├─ Server: "MC-Survival"
        ├─ Node: Node 1 (Tunneled)
        ├─ Port: 25565 allocated by Pterodactyl
        └─ Gives server access to user

T=2:00  User receives server
        ├─ Visits: /server/a1b2c3d4
        ├─ Sees FAB button: "🌐 Subdomains"
        └─ Clicks it

T=2:15  Modal opens showing no subdomains
        ├─ User sees: "No subdomains yet"
        └─ Clicks: [+ Add Subdomain]

T=2:30  Form appears (Tunneled mode)
        ├─ Blue hint: "Subdomain required..."
        ├─ Selects port: 25565
        ├─ Types: "survival"
        ├─ Preview: "survival.gamehost.com"
        └─ Clicks [🔗 Bind]

T=2:45  Backend processes
        ├─ Validates: subdomain not empty ✓
        ├─ Validates: format correct ✓
        ├─ Creates tunnel rule in Cloudflare
        ├─ Stores in database
        └─ Returns success

T=3:00  Success screen shows
        ├─ "survival.gamehost.com:25565"
        ├─ User copies to clipboard
        └─ Shares with other players

T=3:30  Other players use connection string
        ├─ Connect to: "survival.gamehost.com:25565"
        ├─ Cloudflare routes via tunnel
        ├─ Reaches actual server IP:port
        └─ Game server loads!

T=∞     User can delete, add more, manage
        └─ Anytime via same modal
```

---

## Summary

**Admin Experience:**
1. One-time setup (3-5 minutes)
2. Configure global Cloudflare settings
3. Configure each node (mode, tunnel ID/default)
4. Monitor all bindings (read-only)

**User Experience:**
1. Click FAB button (0.5 seconds)
2. Select port (1 second)
3. Enter/skip subdomain (depends on mode)
4. Click Bind (2-5 seconds API call)
5. Get connection string (instant)
6. Share with others

**Smart Mode Behavior:**
- Tunneled: Required subdomain, prevents errors
- DNS-Only: Optional subdomain, uses defaults
- Each mode shows appropriate form
- Clear hints guide users
- Validation prevents invalid configs

This creates an intelligent, intuitive experience that adapts to how the node is configured.
