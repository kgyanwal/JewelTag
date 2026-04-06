@php
$displayFormat = $getDisplayFormat();
$statePath = $getStatePath();
$id = $getId();
$isDisabled = $isDisabled();

// Safely normalize the placeholder
$baseFormat = preg_replace(['/[mM]+/', '/[dD]+/', '/[yY]+/'], ['m', 'd', 'y'], $displayFormat);
$defaultPlaceholder = strtr($baseFormat, ['m' => 'mm', 'd' => 'dd', 'y' => 'yyyy']);
$placeholder = $getPlaceholder() ?? strtolower($defaultPlaceholder);
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

            <input x-ref="input" id="{{ $id }}" type="text" inputmode="numeric" placeholder="{{ $placeholder }}"
                autocomplete="off" spellcheck="false" maxlength="10" {{ $isDisabled ? 'disabled' : '' }}
                @keydown="handleKeydown($event)"
                @focus="isFocused = true; $nextTick(() => $refs.input.setSelectionRange(typedLen, typedLen))"
                @blur="isFocused = false; validateAndCommit()"
                @click="$refs.input.setSelectionRange(typedLen, typedLen)" @paste.prevent @input.prevent
                :value="maskedValue" class="fi-input block w-full border-none bg-transparent px-3 py-1.5
                       text-base text-gray-950 placeholder:text-gray-400
                       dark:text-white dark:placeholder:text-gray-500
                       sm:text-sm sm:leading-6 focus:outline-none focus:ring-0
                       disabled:cursor-not-allowed " style="caret-color: rgb(var(--primary-600));" />

            <button type="button" tabindex="-1" @click="toggleCalendar()" {{ $isDisabled ? 'disabled' : '' }}
                class="flex items-center pe-3 ps-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </button>
        </div>

        <div x-show="showCalendar" x-cloak x-transition @click.outside="showCalendar = false"
            class="absolute z-30 mt-2 w-72 rounded-xl bg-white p-4 shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">

            <div class="flex items-center justify-between mb-4 gap-2">
                <button type="button" @click="prevMonth()"
                    class="p-1 hover:bg-gray-100 dark:hover:bg-white/10 rounded-md text-gray-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div class="flex items-center gap-1 flex-1 justify-center">
                    {{-- 🚀 FIXED: Added :selected binding to force correct initialization --}}
                    <select x-model.number="calendarMonth"
                        class="border-none bg-transparent py-0 ps-1 pe-6 text-sm font-bold text-gray-900 dark:text-white focus:ring-0 focus:outline-none focus:border-transparent focus:shadow-none cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 rounded">
                        <template x-for="(monthName, index) in monthNames" :key="index">
                            <option :value="index" x-text="monthName" :selected="index === calendarMonth"
                                class="dark:bg-gray-900"></option>
                        </template>
                    </select>

                    <select x-model.number="calendarYear"
                        class="border-none bg-transparent py-0 ps-1 pe-6 text-sm font-bold text-gray-900 dark:text-white focus:ring-0 focus:outline-none focus:border-transparent focus:shadow-none cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 rounded">
                        <template x-for="y in yearRange" :key="y">
                            <option :value="y" x-text="y" :selected="y === calendarYear" class="dark:bg-gray-900">
                            </option>
                        </template>
                    </select>
                </div>

                <button type="button" @click="nextMonth()"
                    class="p-1 hover:bg-gray-100 dark:hover:bg-white/10 rounded-md text-gray-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
        displayFormat,
        digits: '',
        showCalendar: false,
        calendarYear: new Date().getFullYear(),
        calendarMonth: new Date().getMonth(),
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
            'September', 'October', 'November', 'December'
        ],

        init() {
            if (this.state) {
                this.updateDigitsFromState();
            } else {
                const now = new Date();
                this.calendarYear = now.getFullYear();
                this.calendarMonth = now.getMonth();
            }

            this.$watch('state', val => {
                if (val) {
                    const cleanState = String(val).split(' ')[0].split('T')[0];
                    const incomingDate = new Date(cleanState + 'T00:00:00');
                    const currentDate = this.getDateFromDigits();

                    if (!currentDate || incomingDate.getTime() !== currentDate.getTime()) {
                        this.updateDigitsFromState();
                    }
                } else {
                    this.digits = '';
                    // 🚀 FIXED: When state clears, reset calendar view back to today
                    const now = new Date();
                    this.calendarYear = now.getFullYear();
                    this.calendarMonth = now.getMonth();
                }
            });
        },

        get baseFormat() {
            return this.displayFormat
                .replace(/[mM]+/g, 'm')
                .replace(/[dD]+/g, 'd')
                .replace(/[yY]+/g, 'y');
        },

        get yearRange() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let i = currentYear - 50; i <= currentYear + 50; i++) {
                years.push(i);
            }
            return years;
        },

        updateDigitsFromState() {
            if (!this.state) return;

            const cleanState = String(this.state).split(' ')[0].split('T')[0];
            const date = new Date(cleanState + 'T00:00:00');
            if (isNaN(date)) return;

            this.calendarYear = date.getFullYear();
            this.calendarMonth = date.getMonth();

            const y = String(date.getFullYear());
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');

            let newDigits = '';
            const cleanFormat = this.baseFormat.replace(/[^mdy]/g, '');
            for (let char of cleanFormat) {
                if (char === 'm') newDigits += m;
                if (char === 'd') newDigits += d;
                if (char === 'y') newDigits += y;
            }
            this.digits = newDigits;
        },

        parseDigitsToDateParts() {
            const cleanFormat = this.baseFormat.replace(/[^mdy]/g, '');
            let offset = 0;
            let m = '',
                d = '',
                y = '';
            for (let char of cleanFormat) {
                if (char === 'm') {
                    m = this.digits.slice(offset, offset + 2);
                    offset += 2;
                }
                if (char === 'd') {
                    d = this.digits.slice(offset, offset + 2);
                    offset += 2;
                }
                if (char === 'y') {
                    y = this.digits.slice(offset, offset + 4);
                    offset += 4;
                }
            }
            return {
                m,
                d,
                y
            };
        },

        getDateFromDigits() {
            if (this.digits.length < 8) return null;
            const parts = this.parseDigitsToDateParts();
            return new Date(parseInt(parts.y), parseInt(parts.m) - 1, parseInt(parts.d));
        },

        get maskedValue() {
            if (this.digits.length === 0) return '';

            let pattern = '';
            for (let char of this.baseFormat) {
                if (char === 'm') pattern += 'mm';
                else if (char === 'd') pattern += 'dd';
                else if (char === 'y') pattern += 'yyyy';
                else pattern += char;
            }

            let res = '';
            let dIdx = 0;
            for (let char of pattern) {
                if (['m', 'd', 'y'].includes(char)) {
                    res += this.digits[dIdx] || char;
                    dIdx++;
                } else {
                    res += char;
                }
            }
            return res;
        },

        get typedLen() {
            if (this.digits.length === 0) return 0;

            let pattern = '';
            for (let char of this.baseFormat) {
                if (char === 'm') pattern += 'mm';
                else if (char === 'd') pattern += 'dd';
                else if (char === 'y') pattern += 'yyyy';
                else pattern += char;
            }

            let dCount = 0;
            let i = 0;
            while (dCount < this.digits.length && i < pattern.length) {
                if (['m', 'd', 'y'].includes(pattern[i])) dCount++;
                i++;
            }
            return i;
        },

        handleKeydown(e) {
            if (e.key === 'Backspace') {
                e.preventDefault();
                this.digits = this.digits.slice(0, -1);
                this.validateAndCommit();
            } else if (e.key >= '0' && e.key <= '9') {
                e.preventDefault();
                if (this.digits.length < 8) {
                    this.digits += e.key;
                    this.validateAndCommit();
                }
            }
            this.$nextTick(() => this.$refs.input.setSelectionRange(this.typedLen, this.typedLen));
        },

        validateAndCommit() {
            if (this.digits.length !== 8) {
                if (this.digits.length === 0) this.state = null;
                return;
            }

            const parts = this.parseDigitsToDateParts();
            const parsedM = parseInt(parts.m);
            const parsedD = parseInt(parts.d);
            const parsedY = parseInt(parts.y);

            if (parsedM >= 1 && parsedM <= 12 && parsedD >= 1 && parsedD <= 31) {
                this.state = `${parsedY}-${parts.m}-${parts.d}`;
                this.calendarYear = parsedY;
                this.calendarMonth = parsedM - 1;
            }
        },

        toggleCalendar() {
            this.showCalendar = !this.showCalendar;
        },

        prevMonth() {
            let m = parseInt(this.calendarMonth);
            let y = parseInt(this.calendarYear);
            if (m === 0) {
                this.calendarMonth = 11;
                this.calendarYear = y - 1;
            } else {
                this.calendarMonth = m - 1;
            }
        },

        nextMonth() {
            let m = parseInt(this.calendarMonth);
            let y = parseInt(this.calendarYear);
            if (m === 11) {
                this.calendarMonth = 0;
                this.calendarYear = y + 1;
            } else {
                this.calendarMonth = m + 1;
            }
        },

        get calendarDays() {
            const days = [];
            const year = parseInt(this.calendarYear);
            const month = parseInt(this.calendarMonth);

            const first = new Date(year, month, 1).getDay();
            const last = new Date(year, month + 1, 0).getDate();
            const prevLast = new Date(year, month, 0).getDate();
            const today = new Date().toISOString().split('T')[0];

            for (let i = first; i > 0; i--) days.push({
                date: prevLast - i + 1,
                currentMonth: false
            });
            for (let i = 1; i <= last; i++) {
                const dStr =
                    `${year}-${String(month + 1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
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
            const m = String(parseInt(this.calendarMonth) + 1).padStart(2, '0');
            const d = String(day.date).padStart(2, '0');

            this.state = `${y}-${m}-${d}`;
            this.updateDigitsFromState();
            this.showCalendar = false;
        }
    }));
});
</script>