<div x-data="{
    active: @entangle('activeField'),
    fields: @entangle('data'),
    scale: 20,
}" class="flex flex-row gap-0 w-full min-h-[1100px] bg-[#1a1c1e] rounded-3xl overflow-hidden border border-gray-800 shadow-2xl">

    <div class="flex-1 flex flex-col items-center justify-start p-12 bg-[#282a2d] relative overflow-auto">
        <div class="absolute top-6 left-8 flex items-center gap-2 opacity-40">
            <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest italic">JewelTag Studio / Visual Workspace</span>
        </div>
        
        <div id="label-canvas" 
             class="relative bg-white shadow-[0_0_50px_rgba(0,0,0,0.5)] rounded-lg overflow-hidden border-[1px] border-gray-400" 
             style="width: 450px; height: 1000px; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px;">
            
           <template x-for="type in ['stock_no', 'barcode', 'desc', 'price', 'custom1', 'custom2', 'custom3']" :key="type">
    <div @click="active = type" 
         class="draggable absolute cursor-move select-none px-2 py-1 rounded transition-all duration-75"
         :class="active === type ? 'ring-2 ring-blue-500 bg-blue-50/50 shadow-xl z-50' : 'hover:bg-gray-100/50 z-10'"
         :style="`left: ${fields[type + '_x']}px; top: ${fields[type + '_y'] * scale}px;`" 
         :data-target="type">

        <!-- Stock Number -->
        <span x-show="type === 'stock_no'" class="text-[20px] font-black leading-none text-black uppercase">G13532</span>

        <!-- Description -->
        <span x-show="type === 'desc'" class="text-[12px] font-bold leading-tight text-gray-800 block w-[200px]">10K GOLD DIAMOND JESUS</span>

        <!-- Price -->
        <span x-show="type === 'price'" class="text-[18px] font-black text-green-600 leading-none">$11,909.99</span>

        <!-- Barcode -->
        <template x-if="type === 'barcode'">
            <img src="https://barcode.tec-it.com/barcode.ashx?data=123456&code=Code128" alt="Barcode" class="h-12 w-auto"/>
        </template>

        <!-- Custom Fields -->
        <span x-show="type.startsWith('custom')" class="text-[12px] font-bold text-purple-600 border border-purple-200 bg-purple-50 px-1 rounded uppercase" x-text="'FIELD: ' + type.toUpperCase()"></span>

    </div>
</template>


            <div class="absolute inset-x-0 top-0 h-10 border-b border-red-500/20 pointer-events-none px-4 flex items-center">
                <span class="text-[9px] text-red-500 font-bold uppercase">Safe Start (5)</span>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-10 border-t border-red-500/20 pointer-events-none px-4 flex items-center">
                <span class="text-[9px] text-red-500 font-bold uppercase">Safe End (50)</span>
            </div>
        </div>
    </div>

    <div class="w-[400px] bg-[#1a1c1e] border-l border-gray-800 flex flex-col">
        <div class="p-6 border-b border-gray-800 bg-[#212327]">
            <h3 class="text-white text-xs font-black uppercase tracking-widest">Properties Inspector</h3>
        </div>

        <div class="p-8 space-y-10 overflow-y-auto flex-1">
            <div>
                <label class="text-[10px] text-gray-500 font-black uppercase tracking-widest block mb-2">Selected Object</label>
                <div class="bg-[#212327] rounded-xl p-4 border border-gray-800 text-blue-400 font-mono text-sm uppercase" x-text="active.replace('_', ' ')"></div>
            </div>

            <div class="space-y-6">
                <label class="text-[10px] text-gray-500 font-black uppercase tracking-widest block">Position (ZPL Dots)</label>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <span class="text-[9px] text-gray-600 font-bold">X-COORDINATE</span>
                        <input
    type="number"
    x-model.number="fields[active + '_x']"
    class="w-full rounded-xl py-3 px-4"
/>

                    </div>
                    <div class="space-y-2">
                        <span class="text-[9px] text-gray-600 font-bold">Y-COORDINATE</span>
                       <input
    type="number"
    x-model.number="fields[active + '_y']"
    min="5" max="50"
    class="w-full rounded-xl py-3 px-4"
/>

                    </div>
                </div>
            </div>

            <div class="bg-blue-500/5 border border-blue-500/10 rounded-2xl p-6">
                <p class="text-[11px] text-blue-300/60 leading-relaxed italic">
                    All coordinates are saved in ZPL printer dots. The workspace uses 20x scaling for visual precision.
                </p>
            </div>
        </div>

        <div class="p-8 bg-[#212327] border-t border-gray-800 shadow-[0_-10px_30px_rgba(0,0,0,0.3)]">
            <button wire:click="saveMasterLayout" 
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-black py-5 rounded-2xl transition-all shadow-lg active:scale-95 uppercase tracking-widest">
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
                    const type = event.target.getAttribute('data-target');
                    @this.set('activeField', type);
                },
                move(event) {
                    const target = event.target;
                    const type = target.getAttribute('data-target');
                    
                    let x = (parseFloat(target.style.left) || 0) + event.dx;
                    let visualY = (parseFloat(target.style.top) || 0) + event.dy;
                    
                    let dotY = Math.round(visualY / scale);
                    let dotX = Math.round(x);
                    
                    if (dotY < 5) dotY = 5; 
                    if (dotY > 50) dotY = 50; 

                    @this.set(`data.${type}_x`, dotX, false);
                    @this.set(`data.${type}_y`, dotY, false);
                }
            }
        });
    }
    document.addEventListener('livewire:navigated', setupDesigner);
    document.addEventListener('DOMContentLoaded', setupDesigner);
</script>