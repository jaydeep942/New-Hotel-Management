    </main>
</div>

<!-- Toast UI Container -->
<div id="toast-container" class="fixed bottom-8 right-8 z-[100] space-y-4"></div>

<script>
    // System-wide Toast Notification
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        const colors = {
            success: 'from-emerald-400 to-green-500',
            error: 'from-rose-400 to-red-500',
            warning: 'from-amber-400 to-orange-500',
            info: 'from-primary to-secondary'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.className = `flex items-center space-x-4 p-5 rounded-3xl bg-white shadow-2xl border border-white/50 animate-slide-up transform transition-all duration-500`;
        toast.innerHTML = `
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr ${colors[type]} flex items-center justify-center text-white shadow-lg shadow-${type}/10 group-hover:scale-110 transition-transform">
                <i class="fas ${icons[type]} text-xl"></i>
            </div>
            <div class="pr-8">
                <h4 class="text-sm font-black uppercase tracking-widest text-gray-800">${type}</h4>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-gray-300 hover:text-gray-500 transition-colors">
                <i class="fas fa-times text-xs"></i>
            </button>
        `;

        container.appendChild(toast);

        // Auto remove after 5s
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(100%) scale(0.9)';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }
</script>

</body>
</html>
