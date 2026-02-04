<div class="w-full">
    <div class="label-designer-main-wrapper" 
        x-data="{ 
            active: @entangle('activeField'), 
            fields: @entangle('data'),
            get labelPreview() {
                let zpl = '^XA^CI28^MD30^PW900^LL150';
                let s2 = 450; 

                // --- TEXT & NUMBERS (Uses Font Multiplier) ---
                const textFields = [
                    {id: 'stock_no', x: this.fields.stock_no_x, y: this.fields.stock_no_y, val: this.fields.stock_no_val, f: this.fields.stock_no_font, b: this.fields.stock_no_is_bold, side: 1},
                    {id: 'desc', x: this.fields.desc_x, y: this.fields.desc_y, val: this.fields.desc_val, f: this.fields.desc_font, b: this.fields.desc_is_bold, side: 1},
                    {id: 'price', x: this.fields.price_x, y: this.fields.price_y, val: this.fields.price_val, f: this.fields.price_font, b: this.fields.price_is_bold, side: 2},
                    {id: 'dwmtmk', x: this.fields.dwmtmk_x, y: this.fields.dwmtmk_y, val: this.fields.dwmtmk_val, f: this.fields.dwmtmk_font, b: this.fields.dwmtmk_is_bold, side: 2},
                    {id: 'deptcat', x: this.fields.deptcat_x, y: this.fields.deptcat_y, val: this.fields.deptcat_val, f: this.fields.deptcat_font, b: this.fields.deptcat_is_bold, side: 2},
                    {id: 'rfid', x: this.fields.rfid_x, y: this.fields.rfid_y, val: this.fields.rfid_val, f: this.fields.rfid_font, b: this.fields.rfid_is_bold, side: 2}
                ];

                textFields.forEach(t => {
                    let xPos = (t.side === 2 ? s2 : 0) + parseInt(t.x);
                    let h = t.f * 10;
                    let w = t.b ? (h + 2) : h;
                    zpl += `^FO${xPos},${t.y % 150}^A0N,${h},${w}^FD${t.val}^FS`;
                });

                // --- BARCODE (Uses Bar Dimensions Only) ---
                zpl += '^BY' + (this.fields.barcode_width || 1) + ',2.0';
                zpl += '^FO' + this.fields.barcode_x + ',' + (this.fields.barcode_y % 150);
                zpl += '^BCN,' + (this.fields.barcode_height || 35) + ',N,N,N,A^FD' + this.fields.stock_no_val + '^FS';

                zpl += '^XZ';
                return 'http://api.labelary.com/v1/printers/12dpmm/labels/3.0x0.5/0/' + encodeURIComponent(zpl);
            }
        }"
        x-init="
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
        "
    >
        <div class="flex flex-col gap-6 w-full bg-[#0f172a] p-8 rounded-3xl border border-slate-800 shadow-2xl">
            <div class="flex flex-col items-center">
                <div class="mb-4 text-emerald-400 text-[10px] font-black uppercase tracking-widest text-center">Butterfly Wings: Identity (Left) | Values (Right)</div>
                <div id="label-canvas" class="relative bg-white border-4 border-slate-700 shadow-2xl overflow-hidden"
                    style="width: 900px; height: 150px; background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px); background-size: 10px 10px;">
                    <div class="absolute left-[450px] h-full border-l-2 border-dashed border-red-400 pointer-events-none"></div>

                    <template x-for="type in ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid']" :key="type">
                        <div x-on:mousedown="active = type" 
                             class="draggable-item absolute cursor-move select-none p-1 transition-none"
                             :style="`left: ${(['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type) ? 450 : 0) + parseInt(fields[type + '_x'])}px; top: ${fields[type + '_y'] % 150}px;`" 
                             :data-type="type">
                            
                            <div :class="active === type ? 'ring-2 ring-blue-500 bg-blue-100/50 z-50 shadow-xl' : 'z-10'" class="p-1 rounded leading-none text-black">
                                <template x-if="type !== 'barcode'">
                                    <div style="white-space: nowrap;" 
                                         :style="`font-size: ${fields[type + '_font'] * 3.5}px; font-weight: ${fields[type + '_is_bold'] ? '900' : '500'};`" 
                                         x-text="fields[type + '_val']"></div>
                                </template>
                                <template x-if="type === 'barcode'">
                                    <div class="bg-black" :style="`height: ${fields['barcode_height']}px; width: ${fields['barcode_width'] * 60}px;`"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 border-t border-slate-800 pt-8">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="text-[9px] text-slate-500 font-bold uppercase mb-1 block">Element</label>
                            <select x-model="active" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 border-none shadow-inner outline-none">
                                <option value="stock_no">Stock Number</option><option value="barcode">Barcode</option><option value="desc">Description</option>
                                <option value="price">Retail Price</option><option value="dwmtmk">Metal/Weight</option><option value="deptcat">Category</option><option value="rfid">RFID Number</option>
                            </select>
                        </div>
                        <div><span class="text-[9px] text-slate-500 font-bold uppercase block mb-1">X Pos</span><input type="number" x-model.number="fields[active + '_x']" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 shadow-inner outline-none"/></div>
                        <div><span class="text-[9px] text-slate-500 font-bold uppercase block mb-1">Y Pos</span><input type="number" x-model.number="fields[active + '_y']" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 shadow-inner outline-none"/></div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <template x-if="active !== 'barcode'">
                            <div class="space-y-4">
                                <div><span class="text-[9px] text-slate-500 font-bold uppercase block mb-1">Font Size (Multiplier)</span><input type="number" x-model.number="fields[active + '_font']" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 outline-none"/></div>
                                <div class="flex items-center gap-3"><input type="checkbox" x-model="fields[active + '_is_bold']" class="w-5 h-5 rounded text-blue-600"><span class="text-xs text-slate-300 font-bold uppercase">Bold Text</span></div>
                            </div>
                        </template>
                        <template x-if="active === 'barcode'">
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="text-[9px] text-slate-500 font-bold uppercase block mb-1">Bar Height</span><input type="number" x-model.number="fields['barcode_height']" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 outline-none"/></div>
                                <div><span class="text-[9px] text-slate-500 font-bold uppercase block mb-1">Bar Width</span><input type="number" x-model.number="fields['barcode_width']" class="w-full bg-white text-blue-600 font-black rounded-xl p-3 outline-none"/></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl flex flex-col items-center">
                    <img :src="labelPreview" class="w-full h-auto border border-slate-200" alt="ZPL Preview">
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
</div>