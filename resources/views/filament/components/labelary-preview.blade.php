<div x-data="{
    stock_x: @entangle('data.stock_no_x'),
    stock_y: @entangle('data.stock_no_y'),
    price_x: @entangle('data.price_x'),
    price_y: @entangle('data.price_y'),
    desc_x:  @entangle('data.desc_x'),
    desc_y:  @entangle('data.desc_y'),
}" class="flex flex-col items-center bg-gray-800 p-12 rounded-xl min-h-[600px]">
    
    <div class="mb-4 text-white text-sm font-bold uppercase tracking-widest">Master Label Designer</div>

    <div id="label-canvas" class="relative bg-white shadow-2xl overflow-hidden" 
         style="width: 450px; height: 250px;">
        
        <div class="draggable absolute cursor-move select-none p-1 border border-transparent hover:border-blue-500 hover:bg-blue-50/30"
             :style="`left: ${stock_x}px; top: ${stock_y}px; font-size: 28px; font-weight: 800; font-family: sans-serif;`"
             data-target="stock">
            G13532
        </div>

        <div class="draggable absolute cursor-move select-none p-1 border border-transparent hover:border-blue-500 hover:bg-blue-50/30"
             :style="`left: ${desc_x}px; top: ${desc_y}px; font-size: 18px; font-weight: 600; width: 300px; line-height: 1.2;`"
             data-target="desc">
            10K Gold 1 CTW Diamond Jesus Medallion
        </div>

        <div class="draggable absolute cursor-move select-none p-1 border border-transparent hover:border-blue-500 hover:bg-blue-50/30"
             :style="`left: ${price_x}px; top: ${price_y}px; font-size: 24px; font-weight: 700; color: #16a34a;`"
             data-target="price">
            $11,909.99
        </div>

    </div>

    <div class="mt-8 grid grid-cols-3 gap-4 text-gray-400 text-[10px] font-mono">
        <div>STOCK: <span x-text="stock_x"></span>, <span x-text="stock_y"></span></div>
        <div>DESC: <span x-text="desc_x"></span>, <span x-text="desc_y"></span></div>
        <div>PRICE: <span x-text="price_x"></span>, <span x-text="price_y"></span></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
    document.addEventListener('livewire:navigated', () => {
        interact('.draggable').draggable({
            listeners: {
                move (event) {
                    const target = event.target;
                    const type = target.getAttribute('data-target');
                    
                    let x = (parseFloat(target.style.left) || 0) + event.dx;
                    let y = (parseFloat(target.style.top) || 0) + event.dy;

                    target.style.left = x + 'px';
                    target.style.top = y + 'px';

                    if(type === 'stock') {
                        @this.set('data.stock_no_x', Math.round(x), false);
                        @this.set('data.stock_no_y', Math.round(y), false);
                    } else if(type === 'price') {
                        @this.set('data.price_x', Math.round(x), false);
                        @this.set('data.price_y', Math.round(y), false);
                    } else if(type === 'desc') {
                        @this.set('data.desc_x', Math.round(x), false);
                        @this.set('data.desc_y', Math.round(y), false);
                    }
                }
            }
        });
    });
</script>