@livewireScripts
<script>
    function updateSystemTheme(theme) {
        fetch('{{ route('admin.system.update-theme') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ theme_color: theme })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.reload();
            else alert(data.message || 'فشل تغيير الثيم');
        })
        .catch(error => {
            console.error(error);
            alert('حدث خطأ أثناء تغيير اللون');
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.group-theme-selector')) {
            document.querySelector('.theme-selector-menu')?.classList.add('hidden');
        }
    });

    document.querySelector('.group-theme-selector button')?.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.theme-selector-menu')?.classList.toggle('hidden');
    });
</script>
