@php
    $showPoliciesLink = Auth::user()->hasRole('agency-admin') || Auth::user()->can('policies.view');
    $showPoliciesLinkUser = Auth::check() && Auth::user()->agency_id !== null && !Auth::user()->hasRole('agency-admin');
@endphp 


<nav class="w-full flex items-center justify-between px-6 shadow-sm rounded-t-2xl nav-gradient"
    style="padding-top: 8px; padding-bottom: 8px; min-height:48px;">

<!-- شعار الوكالة واسمها -->
<x-navbar.brand.agency-brand />

<!-- أزرار التنقل -->
<x-navbar.nav.agency-nav-links />

<!-- أدوات التحكم (الثيم، اللغة، المستخدم) في أقصى الشريط فقط -->
<x-navbar.nav.agency-topbar-controls />
</nav>

{{-- Toast Notification --}}
<div id="livewire-toast"
     style="display:none; position: fixed; top: 32px; left: 32px; z-index: 9999; min-width: 280px; max-width: 90vw;
            background: rgb(var(--primary-100)); color: rgb(var(--primary-700));
            border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 18px 28px; font-size: 1.05rem; font-weight: 600; align-items: center; gap: 16px; border: 2px solid rgb(var(--primary-500));">
    <span style="margin-left: 12px;">
        <i class="fas fa-bell" style="color: rgb(var(--primary-500)); font-size: 1.4em;"></i>
    </span>
    <span id="livewire-toast-message"></span>
</div>
<script>
    window.addEventListener('new-notification-toast', function(e) {
        var toast = document.getElementById('livewire-toast');
        var msg = document.getElementById('livewire-toast-message');
        msg.textContent = e.detail.message && e.detail.message.trim() !== '' ? e.detail.message : 'لديك إشعار جديد';
        toast.style.display = 'flex';
        toast.style.opacity = 1;
        // إخفاء بعد 5 ثواني
        setTimeout(function() {
            toast.style.opacity = 0;
            setTimeout(function() { toast.style.display = 'none'; }, 400);
        }, 5000);
    });
    window.addEventListener('redirect-to-url', function(e) {
        if (e.detail.url) {
            window.location.href = e.detail.url;
        }
    });
    // كود وقائي لمنع توقف الصفحة بسبب childNodes
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('childNodes')) {
            e.preventDefault();
            return false;
        }
    });
</script>
