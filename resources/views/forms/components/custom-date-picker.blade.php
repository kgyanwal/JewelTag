@php
$displayFormat = $getDisplayFormat();
$statePath = $getStatePath();
$id = $getId();
$isDisabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="customDatePicker({
            state: $wire.entangle('{{ $statePath }}').live,
            displayFormat: '{{ $displayFormat }}',
        })" class="relative">

        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75
                    bg-white dark:bg-white/5
                    ring-gray-950/10 dark:ring-white/20
                    focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500
                    {{ $isDisabled ? 'opacity-70' : '' }}">

            <input x-ref="input" id="{{ $id }}" type="text" inputmode="numeric" autocomplete="off" spellcheck="false"
                maxlength="10" {{ $isDisabled ? 'disabled' : '' }} @keydown="handleKeydown($event)"
                @focus="isFocused = true; $nextTick(() => $refs.input.setSelectionRange(typedLen, typedLen))"
                @blur="isFocused = false; validateAndCommit()"
                @click="$refs.input.setSelectionRange(typedLen, typedLen)" @paste.prevent @input.prevent
                :value="maskedValue" class="fi-input block w-full border-none bg-transparent px-3 py-1.5
                       text-base text-gray-950 placeholder:text-gray-400
                       dark:text-white dark:placeholder:text-gray-500
                       sm:text-sm sm:leading-6 focus:outline-none focus:ring-0
                       disabled:cursor-not-allowed font-mono tracking-tight"
                style="caret-color: rgb(var(--primary-600));" />

            <button type="button" tabindex="-1" @click="toggleCalendar()" {{ $isDisabled ? 'disabled' : '' }}
                class="flex items-center pe-3 ps-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </button>
        </div>

        {{-- Dropdown remains the same --}}
        <div x-show="showCalendar" x-cloak x-transition @click.outside="showCalendar = false"
            class="absolute z-30 mt-2 w-72 rounded-xl bg-white p-4 shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="prevMonth()"
                    class="p-1 hover:bg-gray-100 dark:hover:bg-white/10 rounded-md text-gray-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="monthYearLabel"></span>
                <button type="button" @click="nextMonth()"
                    class="p-1 hover:bg-gray-100 dark:hover:bg-white/10 rounded-md text-gray-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center">
                <template x-for="dayName in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                    <div class="text-[10px] font-bold text-gray-400 uppercase" x-text="dayName"></div>
                </template>
                <template x-for="(day, i) in calendarDays" :key="i">
                    <button type="button" @click="selectDay(day)" :disabled="!day.currentMonth" :class="{
                            'bg-primary-600 text-white font-bold': day.selected,
                            'hover:bg-gray-100 dark:hover:bg-white/10 text-gray-900 dark:text-white': !day.selected && day.currentMonth,
                            'text-gray-300 dark:text-gray-600': !day.currentMonth,
                            'ring-1 ring-inset ring-primary-600': day.today && !day.selected
                        }" class="h-8 w-8 rounded-full text-xs flex items-center justify-center transition"
                        x-text="day.date"></button>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('customDatePicker', ({
        state,
        displayFormat
    }) => ({
        state,
        digits: '',
        showCalendar: false,
        calendarYear: new Date().getFullYear(),
        calendarMonth: new Date().getMonth(),

        init() {
            if (this.state) this.updateDigitsFromState();
            this.$watch('state', val => {
                if (val) this.updateDigitsFromState();
                else this.digits = '';
            });
        },

        updateDigitsFromState() {
            const date = new Date(this.state + 'T00:00:00');
            if (isNaN(date)) return;
            this.digits = String(date.getMonth() + 1).padStart(2, '0') +
                String(date.getDate()).padStart(2, '0') +
                String(date.getFullYear());
            this.calendarYear = date.getFullYear();
            this.calendarMonth = date.getMonth();
        },

        // 🚀 FIXED: The logic that builds the mm/dd/yyyy visual string
        get maskedValue() {
            const pattern = 'mm/dd/yyyy';
            let res = '';
            let dIdx = 0;
            for (let i = 0; i < pattern.length; i++) {
                if (pattern[i] === '/') {
                    res += '/';
                } else {
                    res += this.digits[dIdx] || pattern[i];
                    dIdx++;
                }
            }
            return res;
        },

        // Calculates caret position based on typed digits
        get typedLen() {
            const pattern = 'mm/dd/yyyy';
            let dCount = 0;
            let i = 0;
            while (dCount < this.digits.length && i < pattern.length) {
                if (pattern[i] !== '/') dCount++;
                i++;
            }
            return i;
        },

        handleKeydown(e) {
            if (e.key === 'Backspace') {
                e.preventDefault();
                this.digits = this.digits.slice(0, -1);
            } else if (e.key >= '0' && e.key <= '9') {
                e.preventDefault();
                if (this.digits.length < 8) {
                    this.digits += e.key;
                    if (this.digits.length === 8) this.validateAndCommit();
                }
            }
            this.$nextTick(() => this.$refs.input.setSelectionRange(this.typedLen, this.typedLen));
        },

        validateAndCommit() {
            if (this.digits.length !== 8) return;
            const m = this.digits.slice(0, 2);
            const d = this.digits.slice(2, 4);
            const y = this.digits.slice(4, 8);
            if (parseInt(m) >= 1 && parseInt(m) <= 12 && parseInt(d) >= 1 && parseInt(d) <= 31) {
                this.state = `${y}-${m}-${d}`;
                this.showCalendar = false;
            }
        },

        toggleCalendar() {
            this.showCalendar = !this.showCalendar;
        },
        get monthYearLabel() {
            return new Date(this.calendarYear, this.calendarMonth).toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric'
            });
        },
        prevMonth() {
            if (this.calendarMonth-- === 0) {
                this.calendarMonth = 11;
                this.calendarYear--;
            }
        },
        nextMonth() {
            if (this.calendarMonth++ === 11) {
                this.calendarMonth = 0;
                this.calendarYear++;
            }
        },

        get calendarDays() {
            const days = [];
            const first = new Date(this.calendarYear, this.calendarMonth, 1).getDay();
            const last = new Date(this.calendarYear, this.calendarMonth + 1, 0).getDate();
            const prevLast = new Date(this.calendarYear, this.calendarMonth, 0).getDate();
            const today = new Date().toISOString().split('T')[0];
            for (let i = first; i > 0; i--) days.push({
                date: prevLast - i + 1,
                currentMonth: false
            });
            for (let i = 1; i <= last; i++) {
                const dStr =
                    `${this.calendarYear}-${String(this.calendarMonth + 1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
                days.push({
                    date: i,
                    currentMonth: true,
                    today: dStr === today,
                    selected: this.state === dStr
                });
            }
            while (days.length < 42) days.push({
                date: days.length - last - first + 1,
                currentMonth: false
            });
            return days;
        },

        selectDay(day) {
            if (!day.currentMonth) return;
            const y = String(this.calendarYear);
            const m = String(this.calendarMonth + 1).padStart(2, '0');
            const d = String(day.date).padStart(2, '0');
            this.digits = m + d + y;
            this.state = `${y}-${m}-${d}`;
            this.showCalendar = false;
        }
    }));
});
</script>