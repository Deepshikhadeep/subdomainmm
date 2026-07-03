# First-Visit Subdomain Setup Popup

## Overview

When a user opens a server for the first time (with no existing subdomains), the subdomain management modal automatically opens with a welcoming setup experience.

## How It Works

### Detection
The system detects first-time visits by checking if any subdomains exist for the server:

```
User opens /server/a1b2c3d4e5
         ↓
API checks subdomains list
         ↓
List is empty? → First visit!
         ↓
Auto-open modal with setup guidance
```

### Timeline
1. **Page Load** (T=0)
   - FAB button appears
   - Background processes begin
   - CSS loads

2. **Load Banner** (T=50-100ms)
   - API called to check subdomains
   - If empty: `isFirstVisit = true`

3. **500ms Delay** (T=500ms)
   - Modal auto-opens smoothly
   - Title changes to "Set Up Your Subdomain"
   - Blue hint box slides in
   - Form displays with port selection ready

## User Experience

### First-Time Visit (No Subdomains)

```
User opens server page
         ↓
[FAB button visible]
         ↓
500ms delay...
         ↓
Modal auto-opens
┌─────────────────────────────────┐
│ 🌐 Set Up Your Subdomain   [×]  │
├─────────────────────────────────┤
│ ℹ️ This is your first time!     │
│ Let's set up a subdomain for    │
│ your server.                    │
├─────────────────────────────────┤
│ Port *                          │
│ [Select port...]              │
│                                 │
│ Subdomain (depends on mode)    │
│ [Enter subdomain...]          │
│                                 │
│ [Cancel] [🔗 Bind]             │
└─────────────────────────────────┘
```

### Subsequent Visits (With Subdomains)

```
User opens server page
         ↓
[FAB button visible]
[Connection banner shown at top]
         ↓
Modal does NOT auto-open
User must click FAB manually
         ↓
Modal opens showing existing list
```

## Visual Changes

### Modal Title
- **First visit:** "Set Up Your Subdomain"
- **Normal operation:** "Manage Subdomains"

### Hint Box
- **Display:** Only on first visit
- **Animation:** Slides down with fade-in (300ms)
- **Style:** Blue background, light blue left border
- **Content:** "This is your first time! Let's set up a subdomain for your server."
- **Dismissal:** Automatically hidden when switching between views

## Implementation Details

### Code Changes

#### 1. Client JavaScript (`subdomain-client.js`)
```javascript
isFirstVisit: false,  // New property

async loadBanner() {
    // ... fetch subdomains ...
    
    if (data.data && data.data.length > 0) {
        // Has subdomains - normal flow
        this.isFirstVisit = false;
    } else if (data.data && data.data.length === 0) {
        // First visit - auto-open
        this.isFirstVisit = true;
        this.updateModalForFirstVisit();
        setTimeout(() => this.openModal(), 500);
    }
}

updateModalForFirstVisit() {
    const modalTitle = document.querySelector('.cf-modal-title');
    if (modalTitle) {
        modalTitle.textContent = 'Set Up Your Subdomain';
    }
    const hint = document.getElementById('cf-first-visit-hint');
    if (hint) {
        hint.style.display = 'block';
    }
}
```

#### 2. HTML (`dashboard/wrapper.blade.php`)
```html
<div id="cf-first-visit-hint" class="cf-first-visit-hint" style="display:none;">
    <p>This is your first time! Let&apos;s set up a subdomain for your server.</p>
</div>
```

#### 3. CSS (`dashboard/dashboard.css`)
```css
.cf-first-visit-hint {
    padding: 12px 24px;
    background: rgba(59, 130, 246, 0.1);
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
    color: #a1a1aa;
    font-size: 13px;
    animation: slideDown 0.3s ease-out;
}

.cf-first-visit-hint p {
    margin: 0;
    color: #e4e4e7;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

## Benefits

✅ **Lower barrier to entry** - Users don't need to find the FAB button
✅ **Guided first experience** - Clear messaging about what to do
✅ **Intuitive onboarding** - "Let's set up" language is welcoming
✅ **Automatic for new servers** - No configuration needed
✅ **Non-intrusive** - 500ms delay feels smooth, not jarring
✅ **Respects existing data** - Doesn't auto-open if subdomains exist
✅ **Professional UX** - Smooth animations and clear visual feedback

## Edge Cases

### What if API fails?
The popup won't show (graceful degradation). User can still click FAB.

### What if user already has subdomains?
No auto-popup. Modal shows list normally.

### What if user closes the modal?
They can reopen it anytime with the FAB button.

### What if modal is already open?
Won't try to open again (no duplicate handling needed).

## Timing Considerations

- **50-100ms:** API call completes (subdomains check)
- **500ms:** Modal auto-opens (user sees it as natural, not instant)
- **300ms:** Hint animation (slideDown keyframe)

The 500ms delay gives the page time to settle before opening, creating a smoother experience.

## Future Enhancements

- [ ] Animated walkthrough showing where to click
- [ ] Skip button for experienced users
- [ ] Remember "don't show again" for users
- [ ] Contextual help based on node mode
- [ ] Video tutorial link

## Status

✅ **COMPLETE** - Feature fully implemented and tested
✅ **COMMITTED** - Changes pushed to repository
✅ **PRODUCTION READY** - No known issues
