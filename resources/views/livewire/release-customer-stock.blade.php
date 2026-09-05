{{-- Khung giao diện xuất kho sẽ được hoàn thiện theo thiết kế riêng. --}}
<div class="min-h-40">


    <!-- =========================================================
     MODAL XUẤT KHO CHO KHÁCH HÀNG
     Tailwind CSS 4
========================================================= -->
            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <header class="shrink-0 border-b border-slate-200 px-6 py-4">

                <div class="flex items-center justify-between">

                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                        >
                            <svg
                                class="size-6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="M12 3v12"/>
                                <path d="m7 10 5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>
                        </div>

                        <div>

                            <h2 class="text-xl font-bold tracking-tight text-slate-900">
                                Xuất kho cho khách hàng
                            </h2>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Chọn đơn hàng và nhập số lượng sản phẩm cần xuất cho khách hàng.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    >
                        <svg
                            class="size-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path d="m6 6 12 12"/>
                            <path d="M18 6 6 18"/>
                        </svg>
                    </button>

                </div>

            </header>


            <!-- =====================================================
                 MODE
            ====================================================== -->

            <div class="shrink-0 px-5 pt-4">

                <div class="grid grid-cols-2 gap-3">

                    <!-- ACTIVE -->

                    <button
                        type="button"
                        class="flex items-center gap-4 rounded-xl border border-blue-500 bg-blue-50/40 px-5 py-3.5 text-left ring-1 ring-blue-500/10"
                    >

                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"
                        >
                            <svg
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="M4 4h16v16H4z"/>
                                <path d="M8 8h8M8 12h5M8 16h3"/>
                            </svg>
                        </div>

                        <div>

                            <p class="text-sm font-bold text-blue-700">
                                Theo đơn hàng
                            </p>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Chọn đơn hàng đã hoàn thành, xuất toàn bộ hoặc một phần.
                            </p>

                        </div>

                    </button>


                    <!-- MANUAL -->

                    <button
                        type="button"
                        class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-left transition hover:border-blue-200 hover:bg-slate-50"
                    >

                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500"
                        >
                            <svg
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <circle cx="12" cy="12" r="8"/>
                                <path d="M12 8v8M8 12h8"/>
                            </svg>
                        </div>

                        <div>

                            <p class="text-sm font-bold text-slate-700">
                                Nhập sản phẩm thủ công
                            </p>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Thêm sản phẩm từ kho để xuất hàng.
                            </p>

                        </div>

                    </button>

                </div>

            </div>


            <!-- =====================================================
                 BODY
            ====================================================== -->

            <div class="min-h-0 flex-1 overflow-hidden p-5">

                <div class="grid h-full min-h-0 grid-cols-[minmax(0,1fr)_340px] gap-4">


                    <!-- =================================================
                         LEFT — ORDERS
                    ================================================== -->


                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[900px] text-left">

                            <thead class="border-b border-slate-100 bg-slate-50/70">

                            <tr class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">

                                <th class="w-12 px-4 py-3">
                                    #
                                </th>

                                <th class="px-3 py-3">
                                    Sản phẩm
                                </th>

                                <th class="px-3 py-3">
                                    SKU
                                </th>

                                <th class="px-3 py-3">
                                    Quy cách
                                </th>

                                <th class="px-3 py-3">
                                    Tồn kho
                                </th>

                                <th class="px-3 py-3">
                                    Số lượng xuất
                                </th>

                                <th class="px-3 py-3">
                                    ĐVT
                                </th>

                                <th class="px-3 py-3">
                                    Ghi chú
                                </th>

                                <th class="w-12 px-3 py-3"></th>

                            </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">


                            <!-- PRODUCT 1 -->

                            <tr class="group hover:bg-slate-50/60">

                                <td class="px-4 py-3 text-xs text-slate-400">
                                    1
                                </td>


                                <td class="px-3 py-3">

                                    <div class="flex items-center gap-2.5">

                                        <div class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">

                                            <img
                                                src="https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=100&q=80"
                                                class="size-full object-cover"
                                            >

                                        </div>

                                        <div class="min-w-0">

                                            <p class="text-xs font-semibold text-slate-800">
                                                Card visit
                                            </p>

                                            <p class="mt-0.5 text-[10px] text-slate-400">
                                                Giấy C300, cán mờ 2 mặt
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <td class="px-3 py-3">

                                            <span class="text-xs font-semibold text-slate-600">
                                                DT-001
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                            <span class="text-xs text-slate-600">
                                                9 × 5.4 cm
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600">
                                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                1.500
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="number"
                                        value="1000"
                                        class="h-9 w-24 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-700 outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button
                                        class="flex h-9 min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-600"
                                    >
                                        tờ

                                        <svg class="size-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>

                                    </button>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="text"
                                        placeholder="Ghi chú..."
                                        class="h-9 w-full min-w-[120px] rounded-lg border border-slate-200 px-3 text-xs outline-none placeholder:text-slate-400 focus:border-blue-500"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button
                                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500"
                                    >

                                        <svg
                                            class="size-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M4 7h16"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M6 7l1 14h10l1-14"/>
                                            <path d="M9 7V4h6v3"/>
                                        </svg>

                                    </button>

                                </td>

                            </tr>


                            <!-- PRODUCT 2 -->

                            <tr class="group hover:bg-slate-50/60">

                                <td class="px-4 py-3 text-xs text-slate-400">
                                    2
                                </td>


                                <td class="px-3 py-3">

                                    <div class="flex items-center gap-2.5">

                                        <div class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">

                                            <img
                                                src="https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=100&q=80"
                                                class="size-full object-cover"
                                            >

                                        </div>

                                        <div>

                                            <p class="text-xs font-semibold text-slate-800">
                                                Tờ rơi A5
                                            </p>

                                            <p class="mt-0.5 text-[10px] text-slate-400">
                                                Giấy C150, không cán
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs font-semibold text-slate-600">
                                                TR-001
                                            </span>
                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs text-slate-600">
                                                14.8 × 21 cm
                                            </span>
                                </td>


                                <td class="px-3 py-3">

                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600">
                                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                2.800
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="number"
                                        value="2000"
                                        class="h-9 w-24 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-700 outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button
                                        class="flex h-9 min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-600"
                                    >
                                        tờ
                                        <svg class="size-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="text"
                                        placeholder="Ghi chú..."
                                        class="h-9 w-full min-w-[120px] rounded-lg border border-slate-200 px-3 text-xs outline-none placeholder:text-slate-400 focus:border-blue-500"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500">

                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path d="M4 7h16"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M6 7l1 14h10l1-14"/>
                                            <path d="M9 7V4h6v3"/>
                                        </svg>

                                    </button>

                                </td>

                            </tr>


                            <!-- PRODUCT 3 -->

                            <tr class="group hover:bg-slate-50/60">

                                <td class="px-4 py-3 text-xs text-slate-400">
                                    3
                                </td>


                                <td class="px-3 py-3">

                                    <div class="flex items-center gap-2.5">

                                        <div class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">

                                            <img
                                                src="https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=100&q=80"
                                                class="size-full object-cover"
                                            >

                                        </div>

                                        <div>

                                            <p class="text-xs font-semibold text-slate-800">
                                                Poster A2
                                            </p>

                                            <p class="mt-0.5 text-[10px] text-slate-400">
                                                Giấy C200, cán mờ
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs font-semibold text-slate-600">
                                                PO-001
                                            </span>
                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs text-slate-600">
                                                42 × 59.4 cm
                                            </span>
                                </td>


                                <td class="px-3 py-3">

                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600">
                                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                600
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="number"
                                        value="500"
                                        class="h-9 w-24 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-700 outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button
                                        class="flex h-9 min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-600"
                                    >
                                        tờ

                                        <svg class="size-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>

                                    </button>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="text"
                                        placeholder="Ghi chú..."
                                        class="h-9 w-full min-w-[120px] rounded-lg border border-slate-200 px-3 text-xs outline-none placeholder:text-slate-400 focus:border-blue-500"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500">

                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path d="M4 7h16"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M6 7l1 14h10l1-14"/>
                                            <path d="M9 7V4h6v3"/>
                                        </svg>

                                    </button>

                                </td>

                            </tr>


                            <!-- PRODUCT 4 -->

                            <tr class="group hover:bg-slate-50/60">

                                <td class="px-4 py-3 text-xs text-slate-400">
                                    4
                                </td>


                                <td class="px-3 py-3">

                                    <div class="flex items-center gap-2.5">

                                        <div class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">

                                            <img
                                                src="https://images.unsplash.com/photo-1561070791-2526d30994b5?w=100&q=80"
                                                class="size-full object-cover"
                                            >

                                        </div>

                                        <div>

                                            <p class="text-xs font-semibold text-slate-800">
                                                Standee 0.6×1.6m
                                            </p>

                                            <p class="mt-0.5 text-[10px] text-slate-400">
                                                Bạt Hiflex
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs font-semibold text-slate-600">
                                                ST-001
                                            </span>
                                </td>


                                <td class="px-3 py-3">
                                            <span class="text-xs text-slate-600">
                                                60 × 160 cm
                                            </span>
                                </td>


                                <td class="px-3 py-3">

                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-600">
                                                <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                25
                                            </span>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="number"
                                        value="10"
                                        class="h-9 w-24 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-700 outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button
                                        class="flex h-9 min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-600"
                                    >
                                        cái

                                        <svg class="size-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>

                                    </button>

                                </td>


                                <td class="px-3 py-3">

                                    <input
                                        type="text"
                                        value="Giao đủ 10 cái"
                                        class="h-9 w-full min-w-[120px] rounded-lg border border-slate-200 px-3 text-xs outline-none focus:border-blue-500"
                                    >

                                </td>


                                <td class="px-3 py-3">

                                    <button class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500">

                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path d="M4 7h16"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M6 7l1 14h10l1-14"/>
                                            <path d="M9 7V4h6v3"/>
                                        </svg>

                                    </button>

                                </td>

                            </tr>

                            </tbody>

                        </table>

                    </div>

                    <!-- =================================================
                         RIGHT SIDEBAR
                    ================================================== -->

                    <aside class="min-h-0 space-y-4 overflow-y-auto">


                        <!-- =================================================
                             CUSTOMER
                        ================================================== -->

                        <div class="rounded-xl border border-slate-200 bg-white p-4">

                            <div class="mb-4 flex items-center justify-between">

                                <div class="flex items-center gap-2">

                                    <span class="h-5 w-0.5 rounded-full bg-blue-600"></span>

                                    <h3 class="text-sm font-bold text-slate-900">
                                        Thông tin khách hàng
                                    </h3>

                                </div>


                                <button
                                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-medium text-slate-600 hover:bg-slate-50"
                                >
                                    Chỉnh sửa
                                </button>

                            </div>


                            <div class="flex items-center gap-3">

                                <div
                                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600"
                                >
                                    NB
                                </div>

                                <div>

                                    <p class="text-sm font-bold text-slate-900">
                                        Nguyễn Văn B
                                    </p>

                                    <span
                                        class="mt-1 inline-flex rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600"
                                    >
                                    Khách hàng thân thiết
                                </span>

                                </div>

                            </div>


                            <div class="mt-4 space-y-3">

                                <div class="flex items-center gap-3 text-xs text-slate-600">

                                    <svg class="size-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.07 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L9 10.73a16 16 0 0 0 4.27 4.27l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/>
                                    </svg>

                                    0901 234 567

                                </div>


                                <div class="flex items-center gap-3 text-xs text-slate-600">

                                    <svg class="size-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <path d="m3 7 9 6 9-6"/>
                                    </svg>

                                    vanb@xyz.com

                                </div>


                                <div class="flex items-start gap-3 text-xs leading-5 text-slate-600">

                                    <svg class="mt-0.5 size-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>

                                    <span>
                                    123 Lê Lợi, TP. Quy Nhơn, Bình Định
                                </span>

                                </div>


                                <div class="flex items-center gap-3 text-xs text-slate-600">

                                    <svg class="size-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                                        <path d="M8 7h8M8 11h8M8 15h5"/>
                                    </svg>

                                    Công ty XYZ

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             SUMMARY
                        ================================================== -->

                        <div class="rounded-xl border border-slate-200 bg-white p-4">

                            <div class="flex items-center gap-2">

                                <span class="h-5 w-0.5 rounded-full bg-blue-600"></span>

                                <h3 class="text-sm font-bold text-slate-900">
                                    Tóm tắt xuất kho
                                </h3>

                            </div>


                            <div class="mt-4 space-y-3">

                                <div class="flex items-center justify-between">

                                <span class="text-xs text-slate-500">
                                    Số đơn hàng đã chọn
                                </span>

                                    <span class="text-xs font-bold text-slate-800">
                                    1
                                </span>

                                </div>


                                <div class="flex items-center justify-between">

                                <span class="text-xs text-slate-500">
                                    Số loại sản phẩm
                                </span>

                                    <span class="text-xs font-bold text-slate-800">
                                    3
                                </span>

                                </div>


                                <div class="flex items-center justify-between">

                                <span class="text-xs text-slate-500">
                                    Tổng số lượng xuất
                                </span>

                                    <span class="text-xs font-bold text-slate-800">
                                    3.500
                                </span>

                                </div>


                                <div class="flex items-center justify-between border-t border-slate-100 pt-3">

                                <span class="text-xs text-slate-500">
                                    Giá trị vốn (ước tính)
                                </span>

                                    <span class="text-xs font-bold text-slate-900">
                                    27.850.000đ
                                </span>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             STOCK AFTER
                        ================================================== -->

                        <div class="rounded-xl border border-slate-200 bg-white p-4">

                            <div class="flex items-center justify-between">

                                <div class="flex items-center gap-2">

                                    <span class="h-5 w-0.5 rounded-full bg-blue-600"></span>

                                    <h3 class="text-sm font-bold text-slate-900">
                                        Tồn kho sau xuất
                                        <span class="font-normal text-slate-400">
                                        (dự kiến)
                                    </span>
                                    </h3>

                                </div>


                                <button
                                    class="flex size-6 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100"
                                >
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 11v5"/>
                                        <path d="M12 8h.01"/>
                                    </svg>
                                </button>

                            </div>


                            <div class="mt-4 space-y-4">


                                <!-- AVAILABLE -->

                                <div class="flex items-center justify-between">

                                    <div class="flex items-center gap-2.5">

                                    <span class="flex size-7 items-center justify-center rounded-full bg-emerald-50">

                                        <svg
                                            class="size-4 text-emerald-600"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="m5 12 4 4L19 6"/>
                                        </svg>

                                    </span>

                                        <div>

                                            <p class="text-xs font-medium text-slate-700">
                                                Còn hàng
                                            </p>

                                            <p class="text-[10px] text-slate-400">
                                                Đủ số lượng để xuất
                                            </p>

                                        </div>

                                    </div>

                                    <span class="text-sm font-bold text-slate-800">
                                    3
                                </span>

                                </div>


                                <!-- LOW -->

                                <div class="flex items-center justify-between">

                                    <div class="flex items-center gap-2.5">

                                    <span class="flex size-7 items-center justify-center rounded-full bg-amber-50">

                                        <svg
                                            class="size-4 text-amber-500"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle cx="12" cy="12" r="8"/>
                                            <path d="M12 8v4l2.5 1.5"/>
                                        </svg>

                                    </span>

                                        <div>

                                            <p class="text-xs font-medium text-slate-700">
                                                Sắp hết
                                            </p>

                                            <p class="text-[10px] text-slate-400">
                                                Tồn kho ≤ 10
                                            </p>

                                        </div>

                                    </div>

                                    <span class="text-sm font-bold text-slate-800">
                                    0
                                </span>

                                </div>


                                <!-- OUT -->

                                <div class="flex items-center justify-between">

                                    <div class="flex items-center gap-2.5">

                                    <span class="flex size-7 items-center justify-center rounded-full bg-red-50">

                                        <svg
                                            class="size-4 text-red-500"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="m8 8 8 8M16 8l-8 8"/>
                                        </svg>

                                    </span>

                                        <div>

                                            <p class="text-xs font-medium text-slate-700">
                                                Hết hàng
                                            </p>

                                            <p class="text-[10px] text-slate-400">
                                                Không đủ số lượng
                                            </p>

                                        </div>

                                    </div>

                                    <span class="text-sm font-bold text-slate-800">
                                    0
                                </span>

                                </div>

                            </div>

                        </div>

                    </aside>

                </div>

            </div>


            <!-- =====================================================
                 FOOTER
            ====================================================== -->

            <footer
                class="flex shrink-0 items-center justify-between border-t border-slate-200 bg-white px-5 py-3.5"
            >

                <button
                    type="button"
                    class="h-10 rounded-lg border border-slate-200 bg-white px-5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Hủy
                </button>


                <div class="flex items-center gap-2">

                    <!-- PRINT -->

                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >

                        <svg
                            class="size-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path d="M6 9V3h12v6"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <path d="M6 14h12v7H6z"/>
                        </svg>

                        In phiếu

                    </button>


                    <!-- EXPORT -->

                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-6 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 active:bg-blue-800"
                    >

                        <svg
                            class="size-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path d="M12 3v12"/>
                            <path d="m7 10 5 5 5-5"/>
                            <path d="M5 21h14"/>
                        </svg>

                        Xuất kho

                    </button>

                </div>

            </footer>

        </div>
