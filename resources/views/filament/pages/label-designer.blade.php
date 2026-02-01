<div class="label-designer-main-wrapper">
    <div
        x-data="{
            active: @entangle('activeField'),
            fields: @entangle('data'),
            scale: 20,
        }"
        class="flex flex-row gap-0 w-full min-h-[1100px] bg-[#1a1c1e] rounded-3xl overflow-hidden border border-gray-800 shadow-2xl"
    >
        {{-- LEFT: DESIGN CANVAS --}}
        <div class="flex-1 flex flex-col items-center justify-start p-12 bg-[#282a2d] relative overflow-auto">
            <div
                id="label-canvas"
                class="relative bg-white shadow-2xl rounded-lg overflow-hidden border border-gray-400"
                style="width: 450px; height: 1000px; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px;"
            >
                <template x-for="type in ['stock_no', 'barcode', 'desc', 'price', 'dwmtmk', 'deptcat', 'rfid']" :key="type">
                    <div @click="active = type" 
                         class="draggable absolute cursor-move select-none px-2 py-1 rounded transition-all"
                         :class="active === type ? 'ring-2 ring-blue-500 bg-blue-50/50 z-50' : 'hover:bg-gray-100/50 z-10'"
                         :style="`left: ${fields[type + '_x']}px; top: ${fields[type + '_y'] * scale}px;`" 
                         :data-target="type"
                    >
                        <span x-show="type === 'stock_no'" class="text-[20px] font-black text-black">G13532</span>
                        <span x-show="type === 'desc'" class="text-[12px] font-bold text-gray-800 block w-[200px]">14K GOLD PENDANT</span>
                        <span x-show="type === 'price'" class="text-[18px] font-black text-green-600" x-text="fields.price_value"></span>
                        <template x-if="type === 'barcode'">
                            <div class="flex flex-col items-center">
                                <img src="https://barcode.tec-it.com/barcode.ashx?data=G13532&code=Code128" class="h-10 w-auto"/>
                                <span class="text-[8px] font-mono">G13532</span>
                            </div>
                        </template>
                        <div x-show="type === 'dwmtmk'" class="text-[11px] font-bold text-blue-700 bg-blue-50 px-1 border border-blue-200 rounded">C1: WEIGHT/KARAT</div>
                        <div x-show="type === 'deptcat'" class="text-[11px] font-bold text-purple-700 bg-purple-50 px-1 border border-purple-200 rounded">C2: DEPT/CAT</div>
                        <div x-show="type === 'rfid'" class="text-[10px] font-mono text-gray-600 bg-gray-50 px-1 border border-gray-200 rounded">C3: RFID TAG</div>
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT: PROPERTIES --}}
        <div class="w-[400px] bg-[#1a1c1e] border-l border-gray-800 flex flex-col">
            <div class="p-6 border-b border-gray-800 bg-[#212327]">
                <h3 class="text-white text-xs font-black uppercase tracking-widest">Properties Inspector</h3>
            </div>

            <div class="p-8 space-y-10 overflow-y-auto flex-1">
                <div>
                    <label class="text-[10px] text-gray-500 font-black uppercase tracking-widest block mb-2">Selected Object</label>
                    <select x-model="active" class="w-full bg-[#212327] rounded-xl p-4 border border-gray-800 text-blue-400 font-mono text-sm uppercase">
                        <option value="stock_no">Stock Number</option>
                        <option value="barcode">Barcode</option>
                        <option value="desc">Description</option>
                        <option value="price">Price</option>
                        <option value="dwmtmk">C1: Weight & Karat</option>
                        <option value="deptcat">C2: Dept & Category</option>
                        <option value="rfid">C3: RFID EPC</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <span class="text-[9px] text-gray-600 font-bold uppercase">X-Coordinate</span>
                        <input type="number" x-model.number="fields[active + '_x']" class="w-full rounded-xl py-3 px-4 bg-[#282a2d] text-white border-gray-700" style="color: blue;" />
                    </div>
                    <div class="space-y-2">
                        <span class="text-[9px] text-gray-600 font-bold uppercase">Y-Coordinate</span>
                        <input type="number" x-model.number="fields[active + '_y']" min="5" max="50" class="w-full rounded-xl py-3 px-4 bg-[#282a2d] text-white border-gray-700" style="color: blue;"  />
                    </div>
                </div>
            </div>

            <div class="p-8 bg-[#212327] border-t border-gray-800 space-y-4">
                {{-- RESET BUTTON --}}
                <button 
                    wire:click="resetToDefault" 
                    wire:confirm="Are you sure you want to reset the layout to factory defaults?"
                    class="w-full bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs font-black py-4 rounded-2xl transition-all uppercase tracking-widest"
                >
                    Reset to Default
                </button>

                {{-- SAVE BUTTON --}}
                <button 
                    wire:click="saveMasterLayout" 
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-black py-5 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest"
                >
                    Save Master Layout
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script>
        function setupDesigner() {
            const scale = 20;
            interact('.draggable').draggable({
                inertia: true,
                listeners: {
                    start(event) {
                        @this.set('activeField', event.target.getAttribute('data-target'));
                    },
                    move(event) {
                        const target = event.target;
                        const type = target.getAttribute('data-target');
                        let x = (parseFloat(target.style.left) || 0) + event.dx;
                        let visualY = (parseFloat(target.style.top) || 0) + event.dy;
                        @this.set(`data.${type}_x`, Math.round(x), false);
                        @this.set(`data.${type}_y`, Math.round(visualY / scale), false);
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', setupDesigner);
        document.addEventListener('livewire:navigated', setupDesigner);
    </script>
</div>