<x-filament-panels::page>
    <form wire:submit.prevent="generateReport">
        {{ $this->form }}
    </form>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950">Stock Aging Report</h2>
                <p class="text-sm text-gray-500">Luxury Diamonds - Calculated up to {{ $data['as_of_date'] }}</p>
            </div>
            <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()" outline>
                Print PDF
            </x-filament::button>
        </div>

        <table class="w-full text-left border-collapse" style="font-size: 0.65rem;">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-2 border-r font-bold text-gray-700" rowspan="2">Department</th>
                    @foreach(['0-3 Months', '3-6 Months', '6-9 Months', '9-12 Months', '12-18 Months', '18-24 Months', '24+ Months'] as $header)
                        <th class="p-2 border-r text-center font-bold text-gray-700" colspan="3">{{ $header }}</th>
                    @endforeach
                </tr>
                <tr class="bg-gray-50 border-b">
                    @for($i=0; $i<7; $i++)
                        <th class="p-1 border-r text-center font-semibold text-gray-600">Qty</th>
                        <th class="p-1 border-r text-center font-semibold text-gray-600">Cost ($)</th>
                        <th class="p-1 border-r text-center font-semibold text-gray-600">Retail ($)</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @php $keys = ['0_3', '3_6', '6_9', '9_12', '12_18', '18_24', '24_plus']; @endphp
                @foreach($reportData as $row)
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="p-2 font-bold border-r bg-gray-50 text-gray-900">{{ $row->department ?? 'General' }}</td>
                        @foreach($keys as $key)
                            <td class="p-1 border-r text-center text-gray-600">{{ $row->{"qty_$key"} ?: '-' }}</td>
                            <td class="p-1 border-r text-right text-gray-600">{{ $row->{"cost_$key"} > 0 ? number_format($row->{"cost_$key"}, 2) : '-' }}</td>
                            <td class="p-1 border-r text-right font-medium text-gray-950">{{ $row->{"retail_$key"} > 0 ? number_format($row->{"retail_$key"}, 2) : '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .fi-form-actions, form, .fi-sidebar-nav { display: none !important; }
            .fi-main-ctn { padding: 0 !important; margin: 0 !important; }
            .bg-white { box-shadow: none !important; border: none !important; }
            table { width: 100% !important; border: 1px solid #000 !important; }
            th, td { border: 1px solid #ddd !important; padding: 2px !important; }
        }
    </style>
</x-filament-panels::page>