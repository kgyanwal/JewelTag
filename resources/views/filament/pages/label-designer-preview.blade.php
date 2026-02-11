<div class="w-full space-y-6" x-data="{ 
    active: @entangle('activeField'), 
    fields: @entangle('data'),
    labels: {
        stock_no: 'Stock No',
        barcode: 'Barcode',
        desc: 'Description',
        price: 'Price Tag',
        dwmtmk: 'Metal/Stone',
        deptcat: 'Category',
        rfid: 'RFID Hex'
    },
    get labelPreview() {
        {{-- 💎 ZD621R 300 DPI Precision Rendering --}}
        let zpl = '^XA^CI28^MD15^PW675^LL225^LS0^PR2';
        let s2 = 337; 
        const txt = ['stock_no','desc','price','dwmtmk','deptcat','rfid'];
        txt.forEach(id => {
            let x = (['price','dwmtmk','deptcat','rfid'].includes(id) ? s2 : 0) + parseInt(this.fields[id+'_x']);
            {{-- 🚀 BOUTIQUE SCALE: Reduced multiplier to keep text elegant and small --}}
            let h = 6 + (parseInt(this.fields[id+'_font']) * 0.8); 
            let w = this.fields[id+'_is_bold'] ? (h + 1) : h;
            zpl += `^FO${x},${this.fields[id+'_y'] * 6.4}^A0N,${Math.round(h)},${Math.round(w)}^FD${this.fields[id+'_val']}^FS`;
        });
        {{-- Barcode point-mapped to jewelry wing --}}
        zpl += `^FO${this.fields.barcode_x},${this.fields.barcode_y * 6.4}^BY${this.fields.barcode_width},2.0^BCN,${this.fields.barcode_height * 4},N,N,N,A^FD${this.fields.barcode_val}^FS^XZ`;
        return 'https://api.labelary.com/v1/printers/12dpmm/labels/2.25x0.75/0/' + encodeURIComponent(zpl);
    }
}"
x-init="$nextTick(() => {
    interact('.draggable-item').draggable({
        listeners: { move(event) {
            const type = event.target.getAttribute('data-type');
            let x = (parseFloat(event.target.style.left) || 0) + event.dx;
            let y = (parseFloat(event.target.style.top) || 0) + (event.dy / 7.5);
            let finalY = Math.max(0, Math.min(35, Math.round(y)));
            event.target.style.left = x + 'px';
            event.target.style.top = (finalY * 7.5) + 'px';
            let finalX = x;
            if (['price', 'dwmtmk', 'deptcat', 'rfid'].includes(type)) { finalX = x - 337; }
            @this.set('data.' + type + '_x', Math.round(finalX), false);
            @this.set('data.' + type + — 'y', finalY, false);
        }}
    });
})">
    {{-- TOP: Layout Grid --}}
    <div class="bg-[#336b81] rounded-2xl border border-slate-400 overflow-hidden shadow-2xl">
        <div class="px-6 py-3 bg-black/10 text-white font-bold text-[10px] uppercase tracking-[0.3em]">Layout Grid (1-30)</div>
        <div class="p-8 flex justify-center items-center">
            <div id="label-canvas" class="relative bg-white border-[4px] border-white shadow-2xl rounded-xl overflow-hidden"
                style="width: 675px; height: 320px; background-image: linear-gradient(#f8fafc 1px, transparent 1px); background-size: 100% 7.5px;">
                <div class="absolute left-[337px] h-full border-l-2 border-dashed border-red-500/20 pointer-events-none"></div>
                <template x-for="id in Object.keys(labels)" :key="id">
                    <div x-on:mousedown="active = id" class="draggable-item absolute cursor-move"
                         :style="`left: ${(['price','dwmtmk','deptcat', 'rfid'].includes(id) ? 337 : 0) + parseInt(fields[id+'_x'])}px; top: ${fields[id+'_y'] * 7.5}px; z-index: ${active === id ? 100 : 10}`" :data-type="id">
                        <div :class="active === id ? 'ring-2 ring-blue-500 bg-blue-50 px-2 py-0.5 rounded shadow-sm scale-105' : ''" class="flex items-center transition-all duration-150">
                             <template x-if="id !== 'barcode'"><span class="text-[9px] font-bold text-slate-800" x-text="fields[id+'_val']"></span></template>
                             <template x-if="id === 'barcode'"><div class="bg-black my-0.5 rounded-sm" style="height: 10px; width: 45px;"></div></template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- BOTTOM: SIDE-BY-SIDE STUDIO --}}
    <div class="flex flex-col lg:flex-row gap-4 items-stretch w-full">
        
        {{-- LEFT PANEL: Systematic Selection Table --}}
        <div class="w-full lg:w-3/5 bg-[#1e293b] rounded-[1.5rem] border border-white/10 overflow-hidden shadow-2xl flex flex-col">
            <table class="w-full text-left border-collapse table-fixed h-full">
                <thead class="bg-black/60 text-[9px] font-black text-slate-300 uppercase tracking-widest">
                    <tr>
                        <th class="px-4 py-4 w-[24%] border-r border-white/5">Field Name</th>
                        <th class="px-2 py-4 w-[20%] text-center border-r border-white/5">Font Size / H</th>
                        <th class="px-2 py-4 w-[12%] text-center border-r border-white/5">Bold / W</th>
                        <th class="px-2 py-4 w-[22%] text-center border-r border-white/5">X Pos</th>
                        <th class="px-2 py-4 w-[22%] text-center">Y Pos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <template x-for="(id, index) in Object.keys(labels)" :key="id">
                        <tr :class="active === id ? '!bg-blue-600/40' : (index % 2 === 0 ? 'bg-slate-800' : 'bg-slate-900')" 
                            class="hover:bg-white/10 transition-colors cursor-pointer" x-on:click="active = id">
                            <td class="px-4 py-3 font-black text-[10px] truncate border-r border-white/5" :class="active === id ? 'text-blue-200' : 'text-slate-400'" x-text="labels[id]"></td>
                            
                            <td class="px-2 py-2 text-center border-r border-white/5">
                                <template x-if="id !== 'barcode'">
                                    <input type="number" x-model.number="fields[id+'_font']" class="w-16 bg-black/60 border border-white/10 rounded px-1 py-1 text-amber-400 font-black text-[12px] outline-none mx-auto block text-center shadow-inner">
                                </template>
                                <template x-if="id === 'barcode'">
                                    <input type="number" x-model.number="fields.barcode_height" class="w-16 bg-black/60 border border-white/10 rounded px-1 py-1 text-amber-400 font-black text-[12px] outline-none mx-auto block text-center shadow-inner">
                                </template>
                            </td>

                            <td class="px-2 py-2 text-center border-r border-white/5">
                                <template x-if="id !== 'barcode'">
                                    <input type="checkbox" x-model="fields[id+'_is_bold']" :checked="fields[id+'_is_bold']" class="w-4 h-4 rounded border-none bg-slate-700 text-blue-500 focus:ring-0 mx-auto block">
                                </template>
                                <template x-if="id === 'barcode'">
                                    <input type="number" step="0.1" x-model.number="fields.barcode_width" class="w-16 bg-black/60 border border-white/10 rounded px-1 py-1 text-amber-400 font-black text-[12px] outline-none mx-auto block text-center shadow-inner">
                                </template>
                            </td>

                            <td class="px-2 py-2 text-center border-r border-white/5">
                                <input type="number" x-model.number="fields[id+'_x']" class="w-16 bg-black/60 border border-white/10 rounded px-1 py-1 text-emerald-400 font-black text-[12px] outline-none mx-auto block text-center shadow-inner">
                            </td>

                            <td class="px-2 py-2 text-center">
                                <input type="number" x-model.number="fields[id+'_y']" class="w-16 bg-black/60 border border-white/10 rounded px-1 py-1 text-emerald-400 font-black text-[12px] outline-none mx-auto block text-center shadow-inner">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- RIGHT PANEL: Official Rendering --}}
        <div class="w-full lg:w-2/5 bg-white rounded-[1.5rem] flex flex-col items-center justify-center border-[10px] border-[#1e293b] shadow-2xl relative min-h-full">
            <span class="text-[9px] font-black text-slate-300 uppercase tracking-[0.3em] py-4">Official ZPL Rendering</span>
            <div class="flex-grow flex items-center justify-center p-8">
                <img :src="labelPreview" class="max-w-full h-auto rounded shadow-lg border border-slate-50 transition-all duration-300" alt="ZPL Output">
            </div>
            <span class="py-2 text-[8px] font-black text-slate-400 italic tracking-widest uppercase">Jewelry Boutique Standard</span>
        </div>
    </div>
</div>