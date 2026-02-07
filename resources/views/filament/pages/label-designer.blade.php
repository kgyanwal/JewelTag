<div class="w-full">
    <div class="label-designer-main-wrapper" x-data="{ 
        active: @entangle('activeField'), 
        fields: @entangle('data'),
        get labelPreview() {
            let zpl = '^XA^CI28^MD30^PW900^LL150';
            let s2 = 450; 

            // --- TEXT FIELDS (Identity Left / Values Right) ---
            const textFields = [
                {id: 'stock_no', side: 1}, {id: 'desc', side: 1},
                {id: 'price', side: 2}, {id: 'dwmtmk', side: 2}, 
                {id: 'deptcat', side: 2}, {id: 'rfid', side: 2}
            ];

            textFields.forEach(t => {
                let x = (t.side === 2 ? s2 : 0) + parseInt(this.fields[t.id + '_x']);
                let y = parseInt(this.fields[t.id + '_y']) % 150;
                let h = parseInt(this.fields[t.id + '_font']) * 10; // Multiplier for 300 DPI quality
                zpl += `^FO${x},${y}^A0N,${h},${h}^FD${this.fields[t.id + '_val']}^FS`;
            });

            // --- BARCODE ---
            zpl += '^BY' + (this.fields.barcode_width || 2) + ',2.0';
            zpl += '^FO' + this.fields.barcode_x + ',' + (this.fields.barcode_y % 150);
            zpl += '^BCN,' + (this.fields.barcode_height || 35) + ',N,N,N,A^FD' + this.fields.stock_no_val + '^FS';

            zpl += '^XZ';
            return 'http://api.labelary.com/v1/printers/12dpmm/labels/3.0x0.5/0/' + encodeURIComponent(zpl);
        }
    }" x-init="
        $nextTick(() => {
            interact('.draggable-item').draggable({
                listeners: {
                    move(event) {
                        const type = event.target.getAttribute('data-type');
                        let x = (parseFloat(event.target.style.left) || 0) + event.dx;
                        let y = (parseFloat(event.target.style.top) || 0) + event.dy;
                        event.target.style.left = x + 'px';
                        event.target.style.top = y + 'px';
                        let finalX = x;
                        if (['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type)) { finalX = x - 450; }
                        @this.set('data.' + type + '_x', Math.round(finalX), false);
                        @this.set('data.' + type + '_y', Math.round(y), false);
                    }
                }
            });
        });
    ">
        <div class="flex flex-col gap-6 w-full bg-[#1e293b] p-8 rounded-3xl border border-slate-700 shadow-2xl">
            <div class="flex flex-col items-center">
                <div class="mb-4 text-emerald-400 text-[10px] font-black uppercase tracking-widest">Butterfly Workspace: 900x150 Dots</div>
                <div id="label-canvas" class="relative bg-white border-4 border-slate-900 overflow-hidden" style="width: 900px; height: 150px;">
                    <div class="absolute left-[450px] h-full border-l-2 border-dashed border-red-400 pointer-events-none"></div>

                    <template x-for="type in ['stock_no', 'desc', 'barcode', 'price', 'dwmtmk', 'deptcat', 'rfid']" :key="type">
                        <div x-on:mousedown="active = type" 
                             class="draggable-item absolute cursor-move p-1 transition-none whitespace-nowrap"
                             :style="`left: ${(['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type) ? 450 : 0) + parseInt(fields[type + '_x'])}px; top: ${fields[type + '_y'] % 150}px;`" 
                             :data-type="type">
                            
                            <div :class="active === type ? 'ring-2 ring-blue-500 bg-blue-100/50 z-50' : 'z-10'" class="p-1 rounded text-black leading-none">
                                <template x-if="type !== 'barcode'">
                                    <div :style="`font-size: ${fields[type + '_font'] * 4}px; font-weight: 800;`" x-text="fields[type + '_val']"></div>
                                </template>
                                <template x-if="type === 'barcode'">
                                    <div class="bg-black" :style="`height: ${fields['barcode_height']}px; width: ${fields['barcode_width'] * 80}px;`"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-6 border-t border-slate-700">
                <div class="space-y-4">
                    <select x-model="active" class="w-full bg-white text-blue-600 font-bold rounded-xl p-3 outline-none">
                        <option value="stock_no">Stock Number</option><option value="desc">Description</option><option value="barcode">Barcode</option>
                        <option value="price">Price</option><option value="dwmtmk">Weight/Karat</option><option value="deptcat">Department</option><option value="rfid">RFID</option>
                    </select>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] text-slate-400 uppercase font-bold">X Pos</label><input type="number" x-model.number="fields[active + '_x']" class="w-full rounded-xl p-3 bg-white text-black font-bold"/></div>
                        <div><label class="text-[10px] text-slate-400 uppercase font-bold">Y Pos</label><input type="number" x-model.number="fields[active + '_y']" class="w-full rounded-xl p-3 bg-white text-black font-bold"/></div>
                    </div>
                    <div><label class="text-[10px] text-slate-400 uppercase font-bold">Size / Width</label><input type="number" x-model.number="fields[active + '_font']" class="w-full rounded-xl p-3 bg-white text-black font-bold"/></div>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-inner flex flex-col items-center">
                    <img :src="labelPreview" class="w-full h-auto border border-slate-200" alt="ZPL Render">
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
</div>