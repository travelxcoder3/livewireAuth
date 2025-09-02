<div wire:poll.2s class="space-y-6 p-6 bg-gray-50 min-h-screen">
    <div class="bg-white rounded-xl shadow-md p-5 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))]">طلبات الموافقة</h2>
        </div>

        @php
            use App\Tables\ApprovalRequestsTable;
            $columns = ApprovalRequestsTable::columns();

            // جهّز $rows لتتوافق مع <x-data-table> (دائمًا Paginator)
            if ($requests instanceof \Illuminate\Pagination\AbstractPaginator) {
                $rows = (clone $requests);
                $rows->setCollection(
                    $requests->getCollection()->map(function ($req) {
                        $isSale = $req->model_type === \App\Models\Sale::class;

                        if ($isSale) {
                            $sale = \App\Models\Sale::find($req->model_id);
                            $itemName = $sale
                                ? ('عملية #'.$sale->id.' | مرجع: '.($sale->reference ?? '-'))
                                : '-';
                            $typeLabel = 'تعديل عملية بيع';
                        } else {
                            $provider  = \App\Models\Provider::find($req->model_id);
                            $itemName  = $provider?->name ?? '-';
                            $typeLabel = 'مزود';
                        }

                        return (object) [
                            'id'            => $req->id,
                            'item_name'     => $itemName,
                            'type_label'    => $typeLabel,
                            'agency_branch' => $req->agency?->name ?? '-',
                            'requested_by'  => $req->requestedBy?->name ?? '-',
                            'status'        => $req->status,
                            'notes'         => $req->notes ?? '-',
                            '_wire_id'      => $req->id,
                        ];
                    })
                );
            } else {
                $mapped = $requests->map(function ($req) {
                    $isSale = $req->model_type === \App\Models\Sale::class;

                    if ($isSale) {
                        $sale = \App\Models\Sale::find($req->model_id);
                        $itemName = $sale
                            ? ('عملية #'.$sale->id.' | مرجع: '.($sale->reference ?? '-'))
                            : '-';
                        $typeLabel = 'تعديل عملية بيع';
                    } else {
                        $provider  = \App\Models\Provider::find($req->model_id);
                        $itemName  = $provider?->name ?? '-';
                        $typeLabel = 'مزود';
                    }

                    return (object) [
                        'id'            => $req->id,
                        'item_name'     => $itemName,
                        'type_label'    => $typeLabel,
                        'agency_branch' => $req->agency?->name ?? '-',
                        'requested_by'  => $req->requestedBy?->name ?? '-',
                        'status'        => $req->status,
                        'notes'         => $req->notes ?? '-',
                        '_wire_id'      => $req->id,
                    ];
                });

                $total   = $mapped->count();
                $perPage = max(1, $total);
                $rows = new \Illuminate\Pagination\LengthAwarePaginator(
                    $mapped,
                    $total,
                    $perPage,
                    1,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
            }
        @endphp

        @if ($rows->total() > 0)
            <x-data-table :rows="$rows" :columns="$columns" />
        @else
            <div class="text-center text-gray-500 py-12 text-sm">
                لا توجد طلبات موافقة معلقة حالياً.
            </div>
        @endif
    </div>
</div>
