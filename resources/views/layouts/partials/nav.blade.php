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
