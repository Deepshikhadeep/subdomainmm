# Cloudflare Subdomain Manager - Experience Summary

## Quick Overview

### Admin Experience: Command Center
**Access:** `/admin/extensions/cfsubdomain`  
**Role:** Configure, monitor, and control the entire system

```
┌─────────────────────────────────────────────────────────┐
│  STEP 1: Set Cloudflare Credentials                     │
│  - API Token                                            │
│  - Zone ID                                              │
│  - Account ID (for Tunnel mode)                         │
│  - Base Domain (e.g., yourhost.com)                     │
│  → [Save Global Settings]                              │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│  STEP 2: Configure Each Node                            │
│  For each server:                                       │
│  - Select Mode: Tunneled or DNS-Only                    │
│  - If Tunneled: Enter Tunnel ID                         │
│  - If DNS-Only: Enter Default Domain                    │
│  → [Save Node Settings]                                 │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│  STEP 3: Monitor & Manage                               │
│  View all subdomains:                                   │
│  - Server name                                          │
│  - Connection string (copy button)                      │
│  - Port number                                          │
│  - Mode (Tunnel/DNS)                                    │
│  - Creation date                                        │
│  → [Delete] any binding                                 │
└─────────────────────────────────────────────────────────┘
```

### User Experience: Simple Modal Popup
**Access:** FAB button (🌐 Subdomains) on any server page  
**Role:** Create and manage subdomains for their servers

```
┌───────────────────────────────────────┐
│  Server Dashboard Page                 │
│                                        │
│  [Page Content...]                     │
│                              [Subdomains]
│                                 FAB ↑
└───────────────────────────────────────┘
         (click FAB button)
                ↓
┌────────────────────────────────────────┐
│  🌐 Manage Subdomains          [X]    │
│                                        │
│  Active Subdomains:                    │
│  ┌──────────────────────────────────┐  │
│  │ server.host.com:25565      [Delete]
│  │ Port: 25565  | Mode: DNS         │  │
│  └──────────────────────────────────┘  │
│                                        │
│  [+ Add Subdomain]                     │
└────────────────────────────────────────┘
       (click Add Subdomain)
                ↓
┌────────────────────────────────────────┐
│  Bind New Subdomain                    │
│                                        │
│  Port: [Select port...             ]  │
│  Subdomain: [myserver___] .host.com   │
│                                        │
│  Preview: myserver.host.com           │
│                                        │
│  [Cancel]         [🔗 Bind]            │
└────────────────────────────────────────┘
         (click Bind)
                ↓
┌────────────────────────────────────────┐
│              ✅                         │
│        Subdomain Bound!                │
│                                        │
│  myserver.host.com:25565              │
│  (ready to use!)                       │
│                                        │
│         [← Back]                       │
└────────────────────────────────────────┘
```

---

## What They Can Do

### Admin Can:
✅ Enter Cloudflare credentials (API Token, Zone ID, Account ID)  
✅ Set base domain for all subdomains  
✅ Configure each node (Tunnel or DNS-Only mode)  
✅ Set tunnel IDs for tunneled nodes  
✅ Set default domains for DNS-Only nodes  
✅ View ALL subdomains across all servers  
✅ Monitor creation dates and modes  
✅ Delete any subdomain binding  
✅ Audit and control the entire system  

### User Can:
✅ See their server's existing subdomains  
✅ View connection strings (copy button)  
✅ Create new subdomain for available ports  
✅ Choose port from dropdown  
✅ Enter custom subdomain name  
✅ See real-time preview  
✅ Delete their own subdomains  
✅ Get error messages with solutions  

---

## Data Flow

```
┌─────────────┐
│   Admin     │ Enters credentials & node config
└──────┬──────┘
       │ Saves to database
       ↓
┌──────────────────┐
│ Database Storage │ Encrypted API key, zone ID, modes
└──────┬───────────┘
       │ Loaded when needed
       ↓
┌──────────────────────────┐
│ Cloudflare Service       │ Uses credentials to call Cloudflare API
└──────┬───────────────────┘
       │ Creates records/tunnels
       ↓
┌──────────────────────┐
│ Cloudflare API       │ DNS records (DNS-Only) or Tunnel routes (Tunneled)
└──────────────────────┘

┌─────────────────────────┐
│ User (on server page)   │
└──────┬──────────────────┘
       │ Clicks FAB button
       ↓
┌──────────────────────────┐
│ Modal appears with list  │ Shows existing subdomains
└──────┬───────────────────┘
       │ User clicks "Add Subdomain"
       ↓
┌──────────────────────────┐
│ Form appears             │ Port dropdown, subdomain input
└──────┬───────────────────┘
       │ User clicks "Bind"
       ↓
┌──────────────────────────┐
│ Backend validation       │ Check port, validate subdomain, check auth
└──────┬───────────────────┘
       │ Create database record
       │ Call Cloudflare API
       ↓
┌──────────────────────────┐
│ Cloudflare creates       │ A record or tunnel route
└──────┬───────────────────┘
       │ Return connection string
       ↓
┌──────────────────────────┐
│ Success screen shown     │ "gameserver.host.com:25565"
└──────────────────────────┘
```

---

## Visual Comparison

| Aspect | Admin | User |
|--------|-------|------|
| **Interface** | Bootstrap admin panel | Dark modal popup |
| **Location** | `/admin/extensions/cfsubdomain` | FAB button on server |
| **Visual Style** | Traditional forms | Modern minimal |
| **What they see** | Everything across all servers | Only their server |
| **What they can edit** | Global config, node settings | Just subdomains |
| **Action complexity** | Multiple steps, configuration | Simple 3-step process |
| **Visual feedback** | Alerts, table updates | Modal transitions, success screen |

---

## User Journey Details

### Step 1: Click FAB Button
**Visual:** Blue gradient button appears in bottom-right corner  
**Icon:** 🌐  
**Text:** "Subdomains"  
**Behavior:** Slides up on hover, shadow effect  
**Action:** Click to open modal

### Step 2: Modal Opens
**Visual:** Dark overlay with centered modal (520px wide)  
**Header:** 🌐 Manage Subdomains with X close button  
**Content:** Loads existing subdomains in a table  
**Style:** Modern, dark theme (Pterodactyl colors)  

### Step 3: View Subdomains
**If they have subdomains:**
- Table with connection strings in green code
- Port number
- Mode badge (🔒 Tunnel or 🌍 DNS)
- Delete button (trash icon)
- "+ Add Subdomain" button at bottom

**If they don't:**
- Icon: 🔗
- Message: "No subdomains yet. Add one to get started!"
- "+ Add Subdomain" button

### Step 4: Add Subdomain
**Form sections:**
1. Port selector (dropdown showing available ports with IPs)
2. Subdomain input (with live suffix showing ".yourhost.com")
3. Live preview (updates as they type)
4. Buttons: Cancel, 🔗 Bind

**Validation:**
- Real-time as they type
- Errors shown immediately
- Button disabled if validation fails

### Step 5: Submit
**Button changes:** "⏳ Binding..."  
**Wait time:** 2-5 seconds (calling Cloudflare API)  
**Result:** Success screen or error

### Step 6: Success
**Shows:**
- ✅ Success icon
- "Subdomain Bound!" message
- Connection string (green, copyable)
- Example: "gameserver.yourhost.com:25565"
- "← Back" button

---

## Admin Journey Details

### Step 1: Credential Setup
**Form fields:**
- Cloudflare API Token (password field, secure)
- Cloudflare Zone ID
- Cloudflare Account ID (for Tunnel mode)
- Base Domain (e.g., yourhost.com)

**Help text:** Links to where to find each value  
**Action:** Click "Save Global Settings"

### Step 2: Node Configuration
**For each node:**
- Modal opens with node name
- Mode selector: Tunneled or DNS-Only
- **If Tunneled:** Tunnel ID field appears
- **If DNS-Only:** Default Domain field appears
- Click "Save Node Settings"

**Visual feedback:**
- Mode badge updates (green Tunneled or blue DNS-Only)
- Table refreshes

### Step 3: Monitor & Manage
**Table shows:**
- Server name (server owner)
- Connection string (copyable)
- Port number
- Mode (badge)
- Creation date
- Delete button (red, with confirmation)

**Actions:**
- Delete any binding (removes from Cloudflare too)
- Copy connection strings
- Monitor creation dates

---

## Error Handling

### User Errors
- "No available ports" → Suggest adding more ports
- "Subdomain already taken" → Suggest different name
- "Node not configured" → Tell them to wait for admin
- "Cloudflare API error" → Tell them to retry or contact admin

### Admin Errors
- "Settings won't save" → Validate Zone ID format
- "Tunnel not working" → Check Tunnel ID
- "Records not created" → Check API key permissions

---

## Key Interaction Points

### Modal Behavior
- Opens with fade-in animation
- Loads list automatically
- Smooth transitions between views
- Closes with X button or backdrop click

### Form Behavior
- Real-time validation
- Live preview updates
- Port dropdown is smart (only shows available)
- Subdomain input has max length

### Visual Feedback
- Loading spinner during API calls
- Success screen with connection string
- Error screen with retry button
- Banner at top if subdomains exist

---

## Mobile Responsiveness

### Desktop
- FAB button in corner
- Modal 520px wide, centered
- All features accessible

### Mobile
- FAB button still works
- Modal stretches to 92vw
- Touch-friendly buttons
- Form fields stack nicely

---

## Performance

**Admin Panel Load:** Instant (tables lazy load if 1000+ items)  
**User Modal Open:** < 500ms (pre-cached CSS, JS)  
**Subdomain Creation:** 2-5 seconds (Cloudflare API call)  
**Deletion:** 1-3 seconds (API call)  

---

## Conclusion

**For Admins:** One-time setup, then ongoing monitoring  
**For Users:** One-click to manage subdomains, simple 3-step process  
**Together:** Seamless ecosystem for subdomain management

Both experiences are optimized for their respective users:
- **Admin:** Control and visibility
- **User:** Simplicity and speed
