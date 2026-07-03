# Cloudflare Subdomain Manager - Complete User & Admin Experience Walkthrough

## Table of Contents
- [Admin Experience](#admin-experience)
- [User Experience](#user-experience)
- [Visual Flow](#visual-flow)

---

## ADMIN EXPERIENCE

### Where to Access
**URL:** `/admin/extensions/cfsubdomain`  
**Access:** Admin panel → Extensions → Cloudflare Subdomain Manager

---

### Step 1: Initial Global Setup

**Location:** Global Cloudflare Configuration Section (top of page)

Admin lands on the extension page and sees a prominent box titled "Global Cloudflare Configuration" with four input fields:

#### What Admin Sees:
1. **Cloudflare API Token** (password field)
   - Input masked for security
   - Placeholder: "Enter your Cloudflare API token"
   - Help text: "Stored securely. Used for all Cloudflare API calls (server-side only)."
   - This is required for all Cloudflare operations

2. **Cloudflare Zone ID** (text field)
   - Placeholder: "e.g. a1b2c3d4e5f6..."
   - Help text: "Found in your Cloudflare dashboard → Domain → Overview → API section."
   - This identifies which domain the extension manages

3. **Cloudflare Account ID** (text field)
   - Placeholder: "e.g. x9y8z7w6v5u4..."
   - Help text: "Required for Tunnel mode. Found in Cloudflare dashboard → Account Home."
   - Only needed if using Tunneled mode

4. **Base Domain** (text field)
   - Placeholder: "e.g. yourhost.com"
   - Help text: "Subdomains will be created as name.yourhost.com"
   - This is the root domain all subdomains will use

#### Admin Action:
- Fills in all four fields with Cloudflare credentials
- Clicks "Save Global Settings" button (blue button, bottom right)
- Gets success alert: "Cloudflare settings saved successfully."

**What happens behind the scenes:**
- Settings are encrypted and stored in database
- Used for all subsequent Cloudflare API calls
- Can be updated anytime

---

### Step 2: Configure Per-Node Settings

**Location:** Node Cloudflare Settings Section (middle of page)

Admin sees a table listing all nodes with current configuration status.

#### What Admin Sees:
- **Node list table** with columns:
  - Node (server name)
  - FQDN (fully qualified domain name)
  - Mode (Tunneled, DNS-Only, or Not Configured)
  - Tunnel ID (if tunneled)
  - Default Domain (if DNS-Only)
  - Actions (Configure button)

- **Badge system:**
  - 🔒 Green "Tunneled" badge if tunneled mode
  - 🌍 Blue "DNS-Only" badge if DNS-only mode
  - Gray "Not Configured" badge if not set up

#### Admin Action for Each Node:

**For DNS-Only Nodes:**
1. Clicks "Configure" button
2. Modal opens with:
   - Mode selector (radio buttons)
   - Default Domain input
3. Selects "🌍 DNS-Only (Direct IP)"
4. Enters default domain (e.g., "node1.yourhost.com")
5. Clicks "Save Node Settings"
6. Mode changes to blue "DNS-Only" badge

**For Tunneled Nodes:**
1. Clicks "Configure" button
2. Modal opens
3. Selects "🔒 Tunneled (Cloudflare Tunnel)"
4. Tunnel ID field appears (previously hidden)
5. Enters Tunnel ID from Cloudflare Zero Trust
6. Help text: "Find this in Cloudflare → Zero Trust → Tunnels"
7. Clicks "Save Node Settings"
8. Mode changes to green "Tunneled" badge

**Dynamic behavior:**
- Selecting "Tunneled" hides the "Default Domain" field
- Selecting "DNS-Only" hides the "Tunnel ID" field
- Prevents confusion about which settings apply to which mode

---

### Step 3: Monitor Active Subdomain Bindings

**Location:** Active Subdomain Bindings Section (bottom of page)

This table shows **all subdomains across all servers** with full details.

#### What Admin Sees:

**Table Columns:**
- **Server:** Server name that owns the subdomain
- **Connection String:** Full connection string (e.g., "myserver.yourhost.com:25565")
- **Port:** The port number being bound (e.g., 25565)
- **Mode:** 🔒 Tunnel (green) or 🌍 DNS (blue) badge
- **Created:** Timestamp of when binding was created
- **Actions:** Delete button

#### Admin Capabilities:

1. **View all subdomains** - See everything at a glance across entire infrastructure
2. **Copy connection strings** - Click icon next to connection string to copy
3. **Delete bindings** - Click red "Delete" button
   - Confirmation dialog: "Delete this subdomain binding? The Cloudflare record will also be removed."
   - When deleted: Record removed from Cloudflare, database entry deleted
   - Users no longer see this subdomain in their dashboard

#### Admin Decision Points:

Admin can:
- **Revoke access** by deleting a binding
- **Monitor usage** by seeing which subdomains are active
- **Audit** by checking creation dates and modes
- **Manage** the entire subdomain ecosystem from one place

---

## USER EXPERIENCE

### Where to Access
**Location:** Client dashboard → Any server page  
**Access:** Automatic (no separate URL needed)

---

### Step 0: Before User Clicks Anything

When user navigates to any server page:

**What loads automatically:**
1. Extension CSS is injected into page
2. FAB button appears in bottom-right corner
3. If server has existing subdomains, connection banner appears at top

#### Visual Elements Present:

**FAB Button (bottom right):**
- Blue gradient button with globe icon 🌐
- Text: "Subdomains"
- Floating Action Button style with subtle shadow
- Hovers up slightly on hover for feedback

**Connection Banner (if subdomains exist):**
- Appears at top-center of page
- Black background with subdomain connection string
- Shows primary subdomain's connection info
- Has copy button (📋)
- Slides in with animation

---

### Step 1: Open the Modal

**User Action:** Clicks FAB button in bottom-right

#### What Happens:

1. **Modal appears** with animated fade-in
2. **Modal shows title:** "Manage Subdomains" with globe icon 🌐
3. **Close button (X)** in top-right
4. **Modal automatically loads subdomains list**

**Visual design:**
- Dark theme (Pterodactyl styling)
- 520px wide (responsive to mobile)
- Centered on screen with dark overlay
- Smooth animations

---

### Step 2: View Existing Subdomains (List View)

#### What User Sees:

**"Active Subdomains" section** with a table showing:
- **Connection string** (green code, e.g., "gameserver.yourhost.com:25565")
- **Port** (white label, e.g., "25565")
- **Mode badge** (🔒 Tunnel or 🌍 DNS)
- **Delete button** (red trash icon)

**If no subdomains exist:**
- Icon: 🔗
- Message: "No subdomains yet. Add one to get started!"

**Add button at bottom:**
- Blue button: "+ Add Subdomain"
- Ready to create new binding

**User decision:**
- Can delete existing subdomains individually
- Can add new subdomain
- Can close modal

---

### Step 3a: View Existing Subdomains - Delete Flow

**User Action:** Clicks red delete button on a subdomain

#### What Happens:

1. **Confirmation dialog:** "Delete this subdomain binding? The Cloudflare record will be removed."
2. Two options: "Cancel" or proceed with deletion
3. If confirmed:
   - API call made to delete
   - Cloudflare record removed
   - Modal shows loading spinner briefly
   - Page refreshes subdomain list
   - Subdomain no longer visible

#### Result:
User has successfully revoked access to that server on that connection string.

---

### Step 3b: Add New Subdomain - Form View

**User Action:** Clicks "+ Add Subdomain" button

#### Modal Changes to Show "Bind New Subdomain" Form

**Section 1: Port Selection**
- Label: "Port"
- Dropdown: "Select port..."
- Shows all available ports with IP addresses
  - Example: "Port 25565 (0.0.0.0)"
  - Example: "Port 25566 (0.0.0.0)"
- Only shows ports NOT already bound to a subdomain
- User must select one

**Section 2: Subdomain Input**
- Label: "Subdomain"
- Text input with inline suffix
- Input: placeholder "myserver" (e.g., user types "gameserver")
- Suffix: ".yourhost.com" (shown as gray text)
- Max length: 63 characters (DNS limit)
- Validation rules:
  - Only letters, numbers, hyphens allowed
  - Cannot start or end with hyphen
  - Cannot already exist

**Section 3: Preview (appears when user types)**
- Label: "Preview"
- Shows real-time preview of connection string
  - Example: "gameserver.yourhost.com"
- Updates as user types
- Shows actual subdomain user will get

**Section 4: Form Actions**
- "Cancel" button (gray) - goes back to list
- "🔗 Bind" button (blue) - submits form

---

### Step 3c: Add New Subdomain - Validation

**As user types in subdomain field:**

Real-time validation checks:
- Must be 3-63 characters
- Only alphanumeric and hyphens
- Cannot start/end with hyphen
- Must be unique (not already taken)

**Errors shown inline:**
- "Subdomain must contain only letters, numbers, and hyphens."
- "Subdomain must be 63 characters or less."
- "Subdomain 'gameserver' is already in use."

**User can't submit if:**
- No port selected
- Subdomain fails validation
- Button becomes disabled with error

---

### Step 4: Bind Subdomain (Submit)

**User Action:** Clicks "🔗 Bind" button

#### What Happens:

1. **Button changes text:** "⏳ Binding..." (disabled)
2. **Loading state** during API call
3. **Backend actions:**
   - Creates database record linking server → allocation → subdomain
   - Calls Cloudflare API to create record/rule:
     - **DNS-Only mode:** Creates A record pointing to node IP with port
     - **Tunneled mode:** Creates tunnel route in Cloudflare
   - Generates connection string
4. **After success (2-5 seconds):**

---

### Step 5: Success Screen

**Modal shows:**
- Success icon: ✅
- Message: "Subdomain Bound!"
- **Connection string** displayed prominently in green code box
  - Example: "gameserver.yourhost.com:25565"
- Button: "← Back" (goes back to subdomain list)

**User can:**
- See their new connection string
- Go back to list to see it in the table
- Close modal entirely

**Behind the scenes:**
- Connection banner at top is also updated
- Shows new binding
- Copy button ready

---

### Step 6: Error Handling

**If something goes wrong:**

Modal shows:
- Error icon: ❌
- Error message (specific to problem):
  - "Node not configured for Cloudflare. Please configure it in admin panel."
  - "No available ports. All ports already have subdomains bound."
  - "Cloudflare API error. The token may be invalid."
  - "A network error occurred. Please try again."

**User can:**
- Click "Retry" to try again
- Close modal
- Alert admin if persistent error

---

### Step 7: Full User Workflow Summary

```
Visit Server Page
    ↓
See FAB Button (bottom-right)
    ↓
Click FAB Button
    ↓
Modal Opens, List Shows
    ├─ See Existing Subdomains?
    │  ├─ Yes: Delete ones you don't want
    │  └─ View connection strings
    └─ Add New Subdomain?
       ├─ Click "+ Add Subdomain"
       ├─ Select Port from dropdown
       ├─ Type Subdomain Name
       ├─ See Preview
       ├─ Click "🔗 Bind"
       ├─ See Success Screen
       └─ New subdomain ready to use!
```

---

## VISUAL FLOW

### Admin Experience Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  Admin Panel: /admin/extensions/cfsubdomain                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 1. GLOBAL CLOUDFLARE CONFIG                             │   │
│  │   ┌─────────────────┐  ┌──────────────────┐             │   │
│  │   │ API Token       │  │ Zone ID          │             │   │
│  │   │ (password)      │  │ (abc123...)      │             │   │
│  │   └─────────────────┘  └──────────────────┘             │   │
│  │   ┌─────────────────┐  ┌──────────────────┐             │   │
│  │   │ Account ID      │  │ Base Domain      │             │   │
│  │   │ (xyz789...)     │  │ (yourhost.com)   │             │   │
│  │   └─────────────────┘  └──────────────────┘             │   │
│  │                         [Save Global Settings]           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 2. NODE SETTINGS (for each node)                        │   │
│  │   ┌──────┬────────────┬──────────┬──────────┬──────┐    │   │
│  │   │ Node │ FQDN       │ Mode     │ Tunnel   │ Cfg  │    │   │
│  │   │ Name │            │          │ ID       │ Btn  │    │   │
│  │   ├──────┼────────────┼──────────┼──────────┼──────┤    │   │
│  │   │ Node1│ node1.com  │ Tunneled │ abc123   │ [⚙️] │    │   │
│  │   │ Node2│ node2.com  │ DNS-Only │ —        │ [⚙️] │    │   │
│  │   └──────┴────────────┴──────────┴──────────┴──────┘    │   │
│  │                                                          │   │
│  │   Modal when [⚙️] clicked:                              │   │
│  │   ┌────────────────────────────┐                        │   │
│  │   │ Mode: [Tunneled / DNS-Only]│                        │   │
│  │   │ Tunnel ID: ________        │                        │   │
│  │   │ OR                         │                        │   │
│  │   │ Default Domain: ________   │                        │   │
│  │   │        [Save]              │                        │   │
│  │   └────────────────────────────┘                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 3. ACTIVE SUBDOMAIN BINDINGS (read-only monitoring)     │   │
│  │   ┌─────────┬──────────────┬──────┬──────┬──────┬───┐   │   │
│  │   │ Server  │ Connection   │ Port │ Mode │ Date │ X │   │   │
│  │   ├─────────┼──────────────┼──────┼──────┼──────┼───┤   │   │
│  │   │ MC-001  │ game1.com... │ 25565│ DNS  │ 2/14 │[🗑]   │   │
│  │   │ MC-002  │ game2.com... │ 25566│Tunl. │ 2/15 │[🗑]   │   │
│  │   └─────────┴──────────────┴──────┴──────┴──────┴───┘   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### User Experience Architecture

```
┌──────────────────────────────────────────────────────────┐
│ Pterodactyl Client Dashboard: /server/uuid               │
├──────────────────────────────────────────────────────────┤
│                                                           │
│  🌐 FAB Button (bottom-right)          🔗 Banner (top)   │
│  [Subdomains]                   gameserver.com:25565     │
│     ↑ click                             ↑ visible if      │
│     └→ Opens Modal                        subdomains      │
│                                          exist             │
│  ┌────────────────────────────────────────────────────┐  │
│  │  🌐 Manage Subdomains              [X]             │  │
│  ├────────────────────────────────────────────────────┤  │
│  │                                                     │  │
│  │  Active Subdomains:                                │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │ Connection: gameserver.com:25565    [Copy]  │  │  │
│  │  │ Port: 25565              Mode: 🌍 DNS      │  │  │
│  │  │                           [Delete]           │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  │                                                     │  │
│  │  [+ Add Subdomain]                                 │  │
│  │                                                     │  │
│  └────────────────────────────────────────────────────┘  │
│                                                           │
│  After clicking [+ Add Subdomain]:                       │
│  ┌────────────────────────────────────────────────────┐  │
│  │  Bind New Subdomain                               │  │
│  ├────────────────────────────────────────────────────┤  │
│  │                                                     │  │
│  │  Port:  [Select port... ▼]                         │  │
│  │         Port 25565                                 │  │
│  │         Port 25566                                 │  │
│  │                                                     │  │
│  │  Subdomain: [gameserver________] .yourhost.com    │  │
│  │                                                     │  │
│  │  Preview:                                          │  │
│  │  gameserver.yourhost.com                          │  │
│  │                                                     │  │
│  │  [Cancel]  [🔗 Bind]                              │  │
│  │                                                     │  │
│  └────────────────────────────────────────────────────┘  │
│                                                           │
│  After clicking [Bind]:                                  │
│  ┌────────────────────────────────────────────────────┐  │
│  │                                                     │  │
│  │                    ✅                              │  │
│  │            Subdomain Bound!                        │  │
│  │                                                     │  │
│  │      gameserver.yourhost.com:25565                │  │
│  │      (in green, copyable)                          │  │
│  │                                                     │  │
│  │            [← Back]                                │  │
│  │                                                     │  │
│  └────────────────────────────────────────────────────┘  │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

---

## Key Differences: Admin vs User

| Feature | Admin | User |
|---------|-------|------|
| **Access** | `/admin/extensions/cfsubdomain` | FAB button on server page |
| **Global Config** | Can set API key, Zone ID, etc. | Cannot access |
| **Node Config** | Can set mode (Tunnel/DNS-Only) | Cannot access |
| **Monitor All** | Sees all subdomains across all servers | Sees only their own server's subdomains |
| **Create Subdomains** | Can view but users create them | Creates their own for their server |
| **Delete Subdomains** | Can delete any subdomain | Can only delete their own |
| **Revoke Access** | Delete any binding | Delete from their server |
| **Interface** | Traditional admin panel (boxes, forms) | Modern modal popup (dark theme) |
| **Visual Style** | Bootstrap/AdminLTE | Custom dark CSS (Pterodactyl style) |

---

## End-to-End Workflow

### Complete Journey: Admin + User

```
ADMIN SETUP (One-time):
1. Admin opens /admin/extensions/cfsubdomain
2. Admin enters Cloudflare credentials (API Token, Zone ID, etc.)
3. Admin configures each node (Tunnel/DNS-Only mode)
4. Admin saves settings
   → System ready for users

USER WORKFLOW (Per server):
1. User visits server page
2. User sees FAB button (🌐 Subdomains)
3. User clicks button
4. Modal opens showing empty list or existing subdomains
5. User clicks "+ Add Subdomain"
6. User selects available port from dropdown
7. User types subdomain name (validates in real-time)
8. User sees live preview
9. User clicks "🔗 Bind"
10. System creates Cloudflare record (API call)
11. User sees success screen with connection string
12. User copies connection string
13. User shares with players
    → Ready to connect!

ONGOING MANAGEMENT (Admin):
- Admin visits /admin/extensions/cfsubdomain
- Admin sees all active subdomains
- Admin can delete any binding if needed
- Admin monitors usage patterns
```

---

## Error Scenarios

### User-Facing Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "No available ports" | All ports bound | Add more ports in Pterodactyl |
| "Subdomain already in use" | User tries duplicate | Choose different name |
| "Node not configured" | Admin didn't set up node | Wait for admin configuration |
| "Cloudflare API error" | Bad API credentials | Admin needs to fix credentials |
| "Network error" | Connection issue | Retry or refresh |

### Admin-Facing Errors

| Error | Cause | Solution |
|-------|-------|----------|
| Settings won't save | Invalid Zone ID | Check Zone ID format |
| Tunnel mode not working | Tunnel ID invalid | Verify in Cloudflare Zero Trust |
| DNS records not created | API key lacks permissions | Use API token with DNS edit permissions |

---

## Security & Privacy

### Data Protection
- **API key:** Stored encrypted, never shown in plain text
- **User subdomains:** Private to their server (ownership checked)
- **Admin subdomains:** Can view all (admin privilege)

### Authorization Checks
- Users can only manage their own server's subdomains
- Users cannot see other servers' subdomains
- Admins have full access
- All API calls validated on backend

---

## Performance Notes

### Admin Panel
- Table loads all nodes and subdomains (lazy loaded if 1000+ items)
- Modal opens instantly
- Save operations take 1-2 seconds

### User Dashboard
- FAB button loads on page load
- Modal lazy-loads subdomain list on click
- Port dropdown populates after modal opens
- Form submission takes 2-5 seconds (Cloudflare API call)

---

## Conclusion

The extension provides two distinct, optimized experiences:

**Admin:** Full control and monitoring from a dedicated panel  
**User:** Simple, fast modal popup for managing their subdomains

Both work together seamlessly to provide a complete subdomain management system.
