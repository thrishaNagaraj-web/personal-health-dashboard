    </div> <!-- End main-container -->
    <footer>
        <p>&copy; 2024 Personal Health Dashboard. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const HYDRATION_INTERVAL = 60 * 60 * 1000; // 1 hour in milliseconds

        function showToast(message) {
            let toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.backgroundColor = '#3498db';
            toast.style.color = 'white';
            toast.style.padding = '15px 25px';
            toast.style.borderRadius = '5px';
            toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            toast.style.zIndex = '9999';
            toast.style.fontFamily = "'Outfit', sans-serif";
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '10px';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease-in-out';
            
            toast.innerHTML = `
                <span style="font-size: 1.5rem;">💧</span>
                <div>
                    <strong style="display:block;margin-bottom:4px;">Hydration Reminder</strong>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.remove()" style="background:none;border:none;color:white;cursor:pointer;font-size:1.5rem;margin-left:10px;padding:0;">&times;</button>
            `;
            
            document.body.appendChild(toast);
            
            // Fade in
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
            });
            
            // Auto remove after 10 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 10000);
        }

        function checkHydration() {
            const lastNotified = localStorage.getItem('lastHydrationNotification');
            const now = Date.now();

            if (!lastNotified || (now - lastNotified) >= HYDRATION_INTERVAL) {
                showToast("It's been an hour! Time to drink some water.");
                localStorage.setItem('lastHydrationNotification', now);
            }
        }

        // Check immediately on page load
        checkHydration();

        // Check every minute if the user leaves the tab open
        setInterval(checkHydration, 60000);
    });
    </script>
</body>
</html>
