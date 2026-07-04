<div class="bg-white rounded-3xl overflow-hidden shadow-xl shadow-slate-200/50 border border-slate-200/80 sticky top-28">
    <div class="h-1.5 w-full bg-linear-to-r from-[#0a4f2d] to-[#12663c]"></div>
    <div class="p-6">
        <h3 class="text-lg font-black text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-4 mb-4">
            <span class="material-icons text-[20px] text-[#0a4f2d]">receipt_long</span>
            Booking Summary
        </h3>

        <div id="summaryInvoice" class="space-y-4 mb-6 text-sm font-medium text-slate-600">
            <div class="text-center py-8">
                <span class="material-icons text-slate-300 text-4xl mb-2 block">receipt_long</span>
                <p>Select dates and rooms to view summary.</p>
            </div>
        </div>

        <button type="submit" id="btnSubmitBooking" class="w-full py-4 rounded-xl text-sm font-black bg-linear-to-r from-[#0a4f2d] to-[#12663c] text-white shadow-[0_4px_14px_rgba(10,79,45,0.3)] hover:shadow-[0_6px_20px_rgba(10,79,45,0.4)] hover:-translate-y-0.5 transition-all cursor-pointer flex items-center justify-center gap-2">
            <span class="material-icons text-[18px]">calendar_month</span>
            Confirm Booking
        </button>
        <p class="text-center mt-4 flex items-center justify-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <span class="material-icons text-[13px] text-slate-300">lock</span>
            Payment collected later &middot; Secure booking
        </p>
    </div>
</div>