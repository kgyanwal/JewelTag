<div x-data="{
    stock_x: @entangle('data.stock_no_x'),
    stock_y: @entangle('data.stock_no_y'),
    price_x: @entangle('data.price_x'),
    price_y: @entangle('data.price_y'),
    desc_x:  @entangle('data.desc_x'),
    desc_y:  @entangle('data.desc_y'),
    scale: 16, // This expands our 25-dot height to a 400px workspace
}" class="flex flex-col items-center bg-gray-900 p-10 rounded-2xl w-full">
    
    <div class="mb-6 text-white text-sm font-bold uppercase tracking-widest opacity-70">Master Visual Designer</div>

    <div id="label-canvas" 
         class="relative bg-white shadow-2xl rounded-lg overflow-hidden border-4 border-gray-700" 
         style="width: 450px; height: 400px; background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 20px 20px;">
        
        <div class="draggable absolute cursor-move select-none px-3 py-1 rounded bg-white/80 border border-blue-400 shadow-sm hover:bg-blue-50"
             :style="`left: ${stock_x}px; top: ${stock_y * scale}px; font-size: 24px; font-weight: 900;`"
             data-target="stock">
            G13532
        </div>

        <div class="draggable absolute cursor-move select-none px-3 py-1 rounded bg-white/80 border border-blue-400 shadow-sm hover:bg-blue-50"
             :style="`left: ${desc_x}px; top: ${desc_y * scale}px; font-size: 14px; width: 300px; font-weight: 600;`"
             data-target="desc">
            10K GOLD DIAMOND JESUS MEDALLION
        </div>

        <div class="draggable absolute cursor-move select-none px-3 py-1 rounded bg-white/80 border border-blue-400 shadow-sm hover:bg-blue-50"
             :style="`left: ${price_x}px; top: ${price_y * scale}px; font-size: 20px; font-weight: 800; color: #16a34a;`"
             data-target="price">
            $11,909.99
        </div>

        <div class="absolute inset-x-0 top-0 h-8 bg-red-500/10 border-b border-red-500/30 flex items-center px-4 text-[10px] text-red-600 font-bold uppercase">Safe Zone Start (5 Dots)</div>
        <div class="absolute inset-x-0 bottom-0 h-8 bg-red-500/10 border-t border-red-500/30 flex items-center px-4 text-[10px] text-red-600 font-bold uppercase">Safe Zone End (25 Dots)</div>
    </div>

    <div class="mt-8 grid grid-cols-3 gap-8">
        <template x-for="item in [['STOCK', stock_x, stock_y], ['DESC', desc_x, desc_y], ['PRICE', price_x, price_y]]">
            <div class="bg-gray-800 px-6 py-3 rounded-xl border border-gray-700 flex flex-col items-center">
                <span class="text-gray-500 text-[10px] font-black" x-text="item[0]"></span>
                <span class="text-white font-mono text-lg"><span x-text="item[1]"></span>, <span x-text="item[2]"></span></span>
            </div>
        </template>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
    function setupInteract() {
        const scale = 16; // Must match the x-data scale

        interact('.draggable').draggable({
            listeners: {
                move (event) {
                    const target = event.target;
                    const type = target.getAttribute('data-target');
                    
                    // Current Position
                    let x = (parseFloat(target.style.left) || 0) + event.dx;
                    let currentY_px = (parseFloat(target.style.top) || 0) + event.dy;

                    // Convert visual pixels back to ZPL Dots
                    let dotY = Math.round(currentY_px / scale);
                    let dotX = Math.round(x);

                    // Clamp to Printer Hardware Limits (5 to 25)
                    if (dotY < 5) dotY = 5;
                    if (dotY > 25) dotY = 25;

                    // Update Visual Position
                    target.style.left = dotX + 'px';
                    target.style.top = (dotY * scale) + 'px';

                    // Save to Database via Livewire
                    if(type === 'stock') {
                        @this.set('data.stock_no_x', dotX, false);
                        @this.set('data.stock_no_y', dotY, false);
                    } else if(type === 'price') {
                        @this.set('data.price_x', dotX, false);
                        @this.set('data.price_y', dotY, false);
                    } else if(type === 'desc') {
                        @this.set('data.desc_x', dotX, false);
                        @this.set('data.desc_y', dotY, false);
                    }
                }
            }
        });
    }

    document.addEventListener('livewire:navigated', setupInteract);
    document.addEventListener('DOMContentLoaded', setupInteract);
</script>
