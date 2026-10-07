<style>
.jt-wrap{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:16px;align-items:start;width:100%}
@media (max-width:1000px){.jt-wrap{grid-template-columns:1fr}}
.jt-card{background:#12343b;border:1px solid rgba(255,255,255,.12);border-radius:16px;overflow:hidden}
.jt-table{width:100%;border-collapse:collapse;table-layout:fixed}
.jt-table th{background:#0a1f24;color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:12px 8px;text-align:center}
.jt-table th:first-child,.jt-table td:first-child{text-align:left;padding-left:16px}
.jt-table td{color:#fff;padding:8px;text-align:center;border-top:1px solid rgba(255,255,255,.08);font-weight:700;font-size:13px}
.jt-table tr.jt-on td{background:rgba(59,130,246,.35)}
.jt-table tbody tr{cursor:pointer}
.jt-table tbody tr:hover td{background:rgba(255,255,255,.08)}
.jt-in{width:68px;background:#06151a;color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:6px;padding:5px 4px;text-align:center;font-weight:700;font-size:13px}
.jt-chk{width:18px;height:18px;accent-color:#10b981}
.jt-prev{background:#fff;border:6px solid #12343b;border-radius:16px;padding:10px}
.jt-prev h4{margin:0 0 8px;text-align:center;font-size:11px;font-weight:800;letter-spacing:.2em;color:#64748b;text-transform:uppercase}
.jt-prev img{width:100%;height:auto;display:block;border:1px solid #e2e8f0;border-radius:6px}
</style>

<div class="w-full space-y-6" x-data="{
    active: @entangle('activeField'),
    fields: @entangle('data'),
    {{-- Order here = order of the table rows --}}
    labels: {
        stock_no: 'Stock No',
        desc: 'Description',
        barcode: 'Barcode',
        price: 'Price Tag',
        dwmtmk: 'Metal/Stone',
        deptcat: 'Category',
        rfid: 'RFID Hex'
    },
    get labelPreview() {
        {{-- Same coordinates the printer receives. Preview is 40 dots taller than the
             real 300-dot label so nothing below y=300 gets chopped in the picture. --}}
        let zpl = '^XA^CI28^MD30^PW900^LL340^LS0^PR2';
        const txt = ['stock_no','desc','price','dwmtmk','deptcat','rfid'];
        txt.forEach(id => {
            let h = parseInt(this.fields[id+'_font']) || 10;
            let w = this.fields[id+'_is_bold'] ? Math.max(2, Math.round(h * 0.9)) : Math.max(2, Math.round(h * 0.7));
            zpl += `^FO${this.fields[id+'_x']},${this.fields[id+'_y']}^A0N,${h},${w}^FD${this.fields[id+'_val']}^FS`;
        });
        let bw = parseFloat(this.fields.barcode_width) || 1;
        let bW = bw > 1 ? 2 : Math.max(1, bw);
        zpl += `^FO${this.fields.barcode_x},${this.fields.barcode_y}^BY${bW},2.0^BCN,${parseInt(this.fields.barcode_height) || 40},N,N,N,N^FD${this.fields.barcode_val}^FS^XZ`;
        return 'https://api.labelary.com/v1/printers/12dpmm/labels/3x1.14/0/' + encodeURIComponent(zpl);
    }
}"
x-init="(() => {
    const norm = () => {
        Object.keys(labels).forEach(id => {
            if (id === 'barcode') return;
            const v = fields[id+'_is_bold'];
            fields[id+'_is_bold'] = (v === true || v === 1 || v === '1' || v === 'true');
        });
    };
    norm(); $nextTick(norm);
})();
$nextTick(() => {
    document.querySelectorAll('div,span,p,h1,h2,h3,h4').forEach(el => {
        if (el.children.length === 0 && /Jeweltag Master Engine|Precision dot-mapped/.test(el.textContent)) {
            el.style.setProperty('color', '#fff', 'important');
            Array.from(el.parentElement.children).forEach(c => c.style.setProperty('color', '#fff', 'important'));
        }
    });
    if (!(parseFloat(fields.barcode_width) >= 1)) fields.barcode_width = 1;
    if (typeof interact === 'undefined') return;
    interact('.draggable-item').draggable({
        listeners: { move(event) {
            const type = event.target.getAttribute('data-type');
            const x = Math.max(0, Math.round((parseFloat(event.target.style.left) || 0) + event.dx));
            const y = Math.max(0, Math.round((parseFloat(event.target.style.top) || 0) + event.dy));
            event.target.style.left = x + 'px';
            event.target.style.top = y + 'px';
            @this.set('data.' + type + '_x', x, false);
            @this.set('data.' + type + '_y', y, false);
        }}
    });
})">

    {{-- TOP: Layout canvas (real dot size 900 x 340, scrolls instead of clipping) --}}
    <div style="background:#1e4a52;border-radius:16px;border:1px solid rgba(255,255,255,.15);">
        <div style="padding:12px 24px;background:rgba(0,0,0,.25);border-radius:16px 16px 0 0;display:flex;flex-wrap:wrap;gap:8px 20px;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-weight:800;font-size:11px;letter-spacing:.2em;text-transform:uppercase;">Tag Layout &mdash; 1 dot = 1 pixel</span>
            <span style="color:#fff;font-size:11px;display:flex;flex-wrap:wrap;gap:6px 16px;align-items:center;">
                <span><i style="display:inline-block;width:12px;height:12px;background:#10b981;border-radius:2px;vertical-align:middle;margin-right:5px;"></i>Tag you keep</span>
                <span><i style="display:inline-block;width:12px;height:12px;background:#94a3b8;border-radius:2px;vertical-align:middle;margin-right:5px;"></i>Backing you throw away</span>
                <span><i style="display:inline-block;width:12px;height:0;border-top:2px dashed #f59e0b;vertical-align:middle;margin-right:5px;"></i>Fold line</span>
                <span><i style="display:inline-block;width:12px;height:0;border-top:2px dashed #ef4444;vertical-align:middle;margin-right:5px;"></i>End of label</span>
            </span>
        </div>
        <div style="padding:24px;overflow-x:auto;">
            <div id="label-canvas" style="position:relative;width:900px;height:360px;margin:0 auto;flex-shrink:0;background:#fff;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.35);background-image:linear-gradient(#eef2f6 1px, transparent 1px);background-size:100% 10px;">
                {{-- discarded backing + bridge --}}
                <div style="position:absolute;left:0;top:0;width:550px;height:335px;background:rgba(148,163,184,.35);pointer-events:none;"></div>
                {{-- keeper square --}}
                <div style="position:absolute;left:550px;top:0;width:350px;height:335px;background:rgba(16,185,129,.18);border:2px solid #10b981;box-sizing:border-box;pointer-events:none;"></div>
                {{-- fold line --}}
                <div style="position:absolute;left:550px;top:190px;width:350px;border-top:2px dashed #f59e0b;pointer-events:none;"></div>
                {{-- end of label --}}
                <div style="position:absolute;left:0;right:0;top:335px;border-top:2px dashed #ef4444;pointer-events:none;"></div>

                <template x-for="id in Object.keys(labels)" :key="id">
                    <div x-on:mousedown="active = id" class="draggable-item absolute cursor-move select-none"
                         :style="`position:absolute; cursor:move; left: ${parseInt(fields[id+'_x']) || 0}px; top: ${parseInt(fields[id+'_y']) || 0}px; z-index: ${active === id ? 100 : 10}`"
                         :data-type="id">
                        <div :class="active === id ? 'ring-2 ring-blue-500 bg-blue-50/80 rounded' : ''" class="transition-all duration-100">
                            <template x-if="id !== 'barcode'">
                                <span class="block text-slate-900 whitespace-nowrap leading-none"
                                      :style="`font-size: ${parseInt(fields[id+'_font']) || 10}px; font-weight: ${fields[id+'_is_bold'] ? 800 : 500};`"
                                      x-text="fields[id+'_val']"></span>
                            </template>
                            <template x-if="id === 'barcode'">
                                <div class="rounded-sm"
                                     :style="`height: ${parseInt(fields.barcode_height) || 20}px; width: 120px; background: repeating-linear-gradient(90deg,#000 0 2px,#fff 2px 4px);`"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- BOTTOM: editor table + ZPL rendering, side by side (plain CSS, no Tailwind needed) --}}
    <div class="jt-wrap">
        <div class="jt-card">
            <table class="jt-table">
                <thead>
                    <tr>
                        <th style="width:26%">Field</th>
                        <th style="width:18%">Size / Bar Height</th>
                        <th style="width:18%">Bold / Bar Width</th>
                        <th style="width:19%">X</th>
                        <th style="width:19%">Y</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="id in Object.keys(labels)" :key="id">
                        <tr :class="active === id ? 'jt-on' : ''" x-on:click="active = id">
                            <td x-text="labels[id]"></td>
                            <td>
                                <template x-if="id !== 'barcode'"><input type="number" min="1" x-model.number="fields[id+'_font']" class="jt-in"></template>
                                <template x-if="id === 'barcode'"><input type="number" min="1" x-model.number="fields.barcode_height" class="jt-in"></template>
                            </td>
                            <td>
                                <template x-if="id !== 'barcode'"><input type="checkbox" x-model="fields[id+'_is_bold']" class="jt-chk"></template>
                                <template x-if="id === 'barcode'"><input type="number" min="1" max="3" step="1" x-model.number="fields.barcode_width" class="jt-in"></template>
                            </td>
                            <td><input type="number" x-model.number="fields[id+'_x']" class="jt-in"></td>
                            <td><input type="number" x-model.number="fields[id+'_y']" class="jt-in"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="jt-prev">
            <h4>ZPL Rendering</h4>
            <img :src="labelPreview" alt="ZPL Output">
        </div>
    </div>
</div>