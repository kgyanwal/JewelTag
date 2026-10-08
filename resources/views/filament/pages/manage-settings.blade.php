<x-filament-panels::page>
<style>
/* ── SETTINGS TABS — one brand family ──────────────────────────────
   Deep emerald + mint surface + champagne gold. Tabs differ by state and
   a small gold accent, not by six unrelated colours.
   Normal tab : mint background, emerald text, thin emerald top line
   Hover      : slightly deeper mint
   Active     : solid deep emerald, white text, gold top line
   Features   : always carries a gold accent (premium / opt-in area)      */

.fi-fo-tabs {
    --brand:      #0B3D3C;
    --brand-dark: #07292A;
    --gold:       #C9A24B;
    --gold-dark:  #8a6a22;
    --surface:    #F4F8F7;
    --surface-2:  #E8F1EF;
    --line:       rgba(11, 61, 60, .16);
}

/* the bar: one connected strip */
.fi-fo-tabs > nav,
.fi-fo-tabs > .fi-tabs,
.fi-fo-tabs [role="tablist"] {
    display: flex !important;
    gap: 0 !important;
    padding: 0 !important;
    margin-bottom: 0 !important;
    background: var(--surface) !important;
    border: 1px solid var(--line) !important;
    border-radius: 14px 14px 0 0 !important;
    box-shadow: 0 4px 16px rgba(11, 61, 60, .07) !important;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    scrollbar-width: none;
    position: relative;
}
.fi-fo-tabs > nav::-webkit-scrollbar,
.fi-fo-tabs [role="tablist"]::-webkit-scrollbar { display: none; }

/* thin rail under the bar */
.fi-fo-tabs > nav::after,
.fi-fo-tabs > .fi-tabs::after,
.fi-fo-tabs [role="tablist"]::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 3px;
    background: var(--brand);
}

/* every tab */
.fi-fo-tabs .fi-tabs-item,
.fi-fo-tabs [role="tab"] {
    flex: 1 1 0;
    min-width: max-content;
    position: relative;
    justify-content: center !important;
    padding: 15px 22px 17px !important;
    border-radius: 0 !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: .015em;
    white-space: nowrap;
    color: var(--brand) !important;
    background: var(--surface) !important;
    border: none !important;
    border-top: 3px solid var(--brand) !important;
    transition: background .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease !important;
}
.fi-fo-tabs .fi-tabs-item *,
.fi-fo-tabs [role="tab"] * { color: inherit !important; }

/* the "|" divider */
.fi-fo-tabs .fi-tabs-item:not(:last-child)::before,
.fi-fo-tabs [role="tab"]:not(:last-child)::before {
    content: '';
    position: absolute;
    right: 0; top: 22%; bottom: 22%;
    width: 1px;
    background: var(--line);
}

/* hover */
.fi-fo-tabs .fi-tabs-item:hover,
.fi-fo-tabs [role="tab"]:hover { background: var(--surface-2) !important; }

/* Features tab (5th): gold accent even when not selected */
.fi-fo-tabs .fi-tabs-item:nth-child(5),
.fi-fo-tabs [role="tab"]:nth-child(5) {
    border-top-color: var(--gold) !important;
    color: var(--gold-dark) !important;
}
/* Security tab (6th): darkest emerald */
.fi-fo-tabs .fi-tabs-item:nth-child(6),
.fi-fo-tabs [role="tab"]:nth-child(6) { border-top-color: var(--brand-dark) !important; }

/* ACTIVE: solid deep emerald, white text, gold line */
.fi-fo-tabs .fi-tabs-item.fi-active,
.fi-fo-tabs .fi-tabs-item[aria-selected="true"],
.fi-fo-tabs [role="tab"][aria-selected="true"] {
    background: var(--brand) !important;
    color: #ffffff !important;
    border-top-color: var(--gold) !important;
    box-shadow: 0 8px 18px rgba(11, 61, 60, .20) !important;
    transform: translateY(-1px);
    z-index: 2;
}
.fi-fo-tabs .fi-tabs-item.fi-active::before,
.fi-fo-tabs .fi-tabs-item[aria-selected="true"]::before,
.fi-fo-tabs [role="tab"][aria-selected="true"]::before { display: none; }

/* small pointer under the active tab */
.fi-fo-tabs .fi-tabs-item.fi-active::after,
.fi-fo-tabs .fi-tabs-item[aria-selected="true"]::after,
.fi-fo-tabs [role="tab"][aria-selected="true"]::after {
    content: '';
    position: absolute;
    left: 50%; bottom: -1px;
    transform: translateX(-50%);
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid #ffffff;
}

/* rounded outer corners */
.fi-fo-tabs .fi-tabs-item:first-child, .fi-fo-tabs [role="tab"]:first-child { border-top-left-radius: 13px !important; }
.fi-fo-tabs .fi-tabs-item:last-child,  .fi-fo-tabs [role="tab"]:last-child  { border-top-right-radius: 13px !important; }

/* the panel under the bar */
.fi-fo-tabs [role="tabpanel"],
.fi-fo-tabs .fi-fo-tabs-tab {
    background: rgba(255, 255, 255, .6);
    border: 1px solid rgba(11, 61, 60, .08);
    border-top: none;
    border-radius: 0 0 16px 16px;
    padding: 22px !important;
}

/* section cards: emerald left edge (gold on the Features tab) */
.fi-fo-tabs [role="tabpanel"] section.fi-section,
.fi-fo-tabs .fi-fo-tabs-tab section.fi-section {
    margin-bottom: 18px;
    border-left: 3px solid var(--brand) !important;
}
.fi-fo-tabs:has([role="tab"]:nth-child(5)[aria-selected="true"]) section.fi-section,
.fi-fo-tabs:has(.fi-tabs-item:nth-child(5).fi-active) section.fi-section {
    border-left-color: var(--gold) !important;
}
.fi-fo-tabs [role="tabpanel"] section.fi-section:last-child { margin-bottom: 0; }

/* footer action strip */
.fi-fo-tabs section.fi-section .fi-section-footer {
    border-top: 1px dashed rgba(11, 61, 60, .15) !important;
    background: rgba(244, 248, 247, .8);
    border-radius: 0 0 14px 14px;
}

.fi-fo-tabs .fi-fo-field-wrp-label span { font-weight: 700; color: #1A2E2D; }
.fi-fo-tabs .fi-fo-field-wrp-hint, .fi-fo-tabs .fi-fo-field-wrp-helper-text { font-size: 11.5px; }

@media (max-width: 900px) {
    .fi-fo-tabs .fi-tabs-item, .fi-fo-tabs [role="tab"] { flex: 0 0 auto; padding: 12px 14px 14px !important; font-size: 12px !important; }
}
</style>

    <x-filament-panels::form wire:submit="save">
        {{-- This renders the fields you defined in your form() method --}}
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page>