Got it — let’s refine the plan so the **extension never clashes with Pterodactyl’s default behavior**. Pterodactyl will continue to handle **port creation** (since that’s its core function), and the extension will only step in **after the port is created** to manage subdomain binding.  

---

## 🛠️ Refined Master Extension Plan (No Clash with Pterodactyl)

### 1. **Roles & Permissions**
- **Admins**
  - Create servers (Pterodactyl handles port allocation).
  - Configure Cloudflare Tunnel ID per node.
  - Enter global Cloudflare API key (hidden after entry).
  - Mark nodes as **Tunneled** or **DNS‑Only**.
  - Manage subdomains for any server.
  - Optionally set **user port limits** (how many extra ports users can add).
- **Users**
  - Cannot create servers.
  - Can manage subdomains for their own server’s ports.
  - If allowed by admin, can add ports (Pterodactyl allocates them automatically).
  - After port creation, extension prompts them to set up a subdomain:
    - **Tunneled Node** → subdomain required.  
    - **DNS‑Only Node** → subdomain optional (default domain auto‑assigned if blank).  

---

### 2. **Cloudflare Setup**
- **Global API Key**
  - Stored securely, masked in UI, editable only.
- **Per‑Node Tunnel IDs**
  - Required only if node is marked **Tunneled**.
- **Node Mode**
  - **Tunneled Node** → subdomains routed through Cloudflare Tunnel.  
  - **DNS‑Only Node** → subdomains routed via DNS records pointing directly to node IP/port.  
  - **Default Domain** → auto‑assigned if user skips subdomain on DNS‑Only node.

---

### 3. **Server & Port Workflow**
1. **Server Creation**  
   - Admin creates server.  
   - Pterodactyl allocates ports (auto/manual).  
   - Extension intercepts event → prompts for subdomain(s).  
   - Logic depends on node mode (Tunnel vs DNS‑Only).  

2. **Port Creation (Admin or User)**  
   - Pterodactyl allocates port automatically.  
   - Extension intercepts event → shows **popup for subdomain setup**:  
     - Port displayed (read‑only).  
     - Subdomain field required (Tunnel) or optional (DNS‑Only).  
     - If blank on DNS‑Only → default domain assigned.  
   - Extension binds subdomain to port via Cloudflare API.  

---

### 4. **Subdomain Management Workflow**
- **Admins or Users** open “Manage Subdomains” tab.  
- Options:
  - **Add Subdomain** → enter name → port auto‑filled → Cloudflare update.  
  - **Delete Subdomain** → Cloudflare cleanup + DB update.  
- Ports are always auto‑filled from system allocation (never typed manually).

---

### 5. **Extension Components**
- **Global Settings Page** → configure Cloudflare API key (hidden after entry).  
- **Node Settings Page** → configure Tunnel ID + mode (Tunneled or DNS‑Only).  
- **Server Event Listener** → listens for port creation/deletion events from Pterodactyl.  
- **Subdomain Popup** → triggered immediately after port creation.  
- **Subdomain Management UI** → for admins/users to add/delete subdomains.  
- **Cloudflare Service Layer** → applies Tunnel or DNS logic based on node mode.  
- **Database Tables** →  
  - `node_cloudflare_settings` (per‑node tunnel ID + mode).  
  - `server_access_points` (server → port → subdomain mappings).

---

### 6. **Security**
- Global API key encrypted, masked in UI.  
- Tunnel IDs stored per node, visible/editable.  
- Node mode clearly marked.  
- User port limits enforced strictly.  
- Default domains prevent misconfiguration on DNS‑Only nodes.  

---

### 7. **Scalability**
- Hybrid setup supported: tunneled nodes and DNS‑only nodes.  
- Pterodactyl handles ports, extension handles subdomains.  
- Admins can decide per server whether users can add ports, and how many.  
- Clean separation: no clash between panel and extension.  

---

✅ With this refinement:  
- **Pterodactyl stays in charge of ports.**  
- **Extension only steps in after port creation** to handle subdomain binding.  
- **Popup ensures subdomain setup** immediately, with defaults for DNS‑Only nodes.  
- No overlap or conflict between panel and extension responsibilities.  

---

Would you like me to now **sketch the exact popup flow** (fields, required/optional states, default domain logic) so you can visualize how the user experience will look in practice?