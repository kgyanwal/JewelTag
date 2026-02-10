<div class="w-full">
    <div class="label-designer-main-wrapper font-sans" 
        x-data="{ 
            active: @entangle('activeField'), 
            fields: @entangle('data'),
            get labelPreview() {
                {{-- 💎 PRECISION ZPL: 675x225 dots for ZD621R --}}
                let zpl = '^XA^CI28^MD15^PW675^LL225^LS0^PR2';
                let s2 = 337; 
                const textFields = [
                    {id: 'stock_no', side: 1}, {id: 'desc', side: 1},
                    {id: 'price', side: 2}, {id: 'dwmtmk', side: 2},
                    {id: 'deptcat', side: 2}, {id: 'rfid', side: 2}
                ];
                textFields.forEach(t => {
                    let xPos = (t.side === 2 ? s2 : 0) + parseInt(this.fields[t.id + '_x']);
                    {{-- 🚀 FIX: Map stored 1-5 to exact tiny dots 10, 12, 14, 16, 18. NO OVERLAP --}}
                    let h = 8 + (parseInt(this.fields[t.id + '_font']) * 2); 
                    let w = this.fields[t.id + '_is_bold'] ? (h + 1) : h;
                    zpl += `^FO${xPos},${this.fields[t.id + '_y']}^A0N,${h},${w}^FD${this.fields[t.id + '_val']}^FS`;
                });
                zpl += '^BY' + (this.fields.barcode_width || 1) + ',2.0^FO' + this.fields.barcode_x + ',' + this.fields.barcode_y;
                zpl += '^BCN,' + (this.fields.barcode_height || 16) + ',N,N,N,A^FD' + this.fields.barcode_val + '^FS^XZ';
                return 'https://api.labelary.com/v1/printers/12dpmm/labels/2.25x0.75/0/' + encodeURIComponent(zpl);
            }
        }"
        x-init="$nextTick(() => {
            interact('.draggable-item').draggable({
                listeners: {
                    move(event) {
                        const type = event.target.getAttribute('data-type');
                        {{-- 8.0x Vertical Spacing Ratio for Designer --}}
                        let x = (parseFloat(event.target.style.left) || 0) + event.dx;
                        let y = (parseFloat(event.target.style.top) || 0) + (event.dy / 8.0);
                        let finalY = Math.max(0, Math.min(35, Math.round(y)));
                        event.target.style.left = x + 'px';
                        event.target.style.top = (finalY * 8.0) + 'px';
                        let finalX = x;
                        if (['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type)) { finalX = x - 337; }
                        @this.set('data.' + type + '_x', Math.round(finalX), false);
                        @this.set('data.' + type + '_y', finalY, false);
                    }
                }
            });
        })"
    >
        <div class="flex flex-col gap-6 w-full bg-slate-950 p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden">
            {{-- Designer Workspace --}}
            <div class="flex flex-col items-center">
                <div id="label-canvas" class="relative bg-white border-[10px] border-slate-900 shadow-2xl rounded-2xl overflow-hidden"
                    style="width: 750px; height: 350px; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;">
                    <div class="absolute left-[375px] h-full border-l-2 border-dashed border-red-500/20 pointer-events-none z-0"></div>
                    <template x-for="type in ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid']" :key="type">
                        <div x-on:mousedown="active = type" class="draggable-item absolute cursor-move select-none"
                             :style="`left: ${(['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type) ? 375 : 0) + parseInt(fields[type + '_x'])}px; top: ${fields[type + '_y'] * 8.0}px; z-index: ${active === type ? 100 : 10}`" :data-type="type">
                            <div :class="active === type ? 'ring-4 ring-emerald-500 bg-emerald-50 scale-105' : 'bg-white shadow-sm border border-slate-100'" 
                                 class="px-3 py-1 rounded-xl flex items-center gap-3 transition-all duration-200">
                                <span class="text-[11px] font-black" :class="active === type ? 'text-emerald-700' : 'text-slate-800'" x-text="fields[type + '_val']"></span>
                                <div class="flex flex-col items-end border-l pl-2 border-slate-100">
                                    <span class="text-[8px] font-black text-emerald-500" x-text="`Y${fields[type + '_y']}`"></span>
                                    <span class="text-[8px] font-bold text-slate-300" x-text="`S${fields[type + '_font']}`"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 🚀 THE HORIZONTAL COMPACT COMMAND STRIP --}}
            <div class="bg-slate-900/60 backdrop-blur-xl p-5 rounded-3xl border border-white/5 shadow-xl">
                <div class="flex items-center gap-8 overflow-x-auto no-scrollbar">
                    <div class="flex-shrink-0 bg-slate-950/50 px-6 py-3 rounded-2xl border border-emerald-500/20">
                        <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest block mb-0.5">Active</span>
                        <span class="text-sm font-black text-emerald-400 uppercase italic" x-text="active.replace('_', ' ')"></span>
                    </div>

                    <div class="flex items-center gap-4 bg-slate-950/50 p-3 rounded-2xl border border-white/5">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 font-black">X</span>
                            <input type="number" x-model.number="fields[active + '_x']" class="w-16 bg-transparent text-emerald-400 font-black text-xl outline-none"/>
                        </div>
                        <div class="h-8 w-px bg-slate-800"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 font-black">Y</span>
                            <input type="number" x-model.number="fields[active + '_y']" class="w-16 bg-transparent text-emerald-400 font-black text-xl outline-none"/>
                        </div>
                    </div>

                    <template x-if="active !== 'barcode'">
                        <div class="flex items-center gap-8">
                            <div class="flex items-center gap-4 bg-slate-950/50 p-3 rounded-2xl border border-white/5">
                                <span class="text-[10px] text-amber-500 font-black">FONT</span>
                                <input type="number" x-model.number="fields[active + '_font']" class="w-12 bg-transparent text-amber-400 font-black text-xl outline-none"/>
                            </div>
                            <label class="flex items-center gap-4 bg-slate-950/50 p-3 px-6 rounded-2xl border border-white/5 cursor-pointer hover:bg-slate-900 transition-all">
                                <span class="text-[10px] text-slate-300 font-black uppercase tracking-widest">Bold Face</span>
                                <input type="checkbox" x-model="fields[active + '_is_bold']" class="w-8 h-8 rounded-xl border-none bg-slate-800 text-emerald-500 focus:ring-0 shadow-lg cursor-pointer">
                            </label>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 🚀 OFFICIAL SIMULATION (Fixed Overlap) --}}
            <div class="bg-white p-6 rounded-[3rem] flex flex-col items-center justify-center border-[10px] border-slate-900 shadow-2xl relative min-h-[160px]">
                <img :src="labelPreview" class="max-w-full h-auto rounded shadow-sm" alt="ZPL Output">
                <span class="mt-4 text-[9px] font-black text-slate-300 uppercase tracking-[0.4em]">ZD621R Precision Simulation</span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
</div>