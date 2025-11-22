module.exports = {
  content: ['./templates/**/*.php', './includes/**/*.php', './*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        'primary': '#0066cc',
        'primary-dark': '#0052a3',
        'metoda-dark': '#2e466f',
        'accent': '#ff6600',
        'metoda-red': '#EF4E4C',
        'admin-blue': '#1e40af',
        'admin-gray': '#f8fafc',
        'status-active': '#10b981',
        'status-pending': '#f59e0b',
        'status-blocked': '#ef4444',
        'status-draft': '#6b7280',
        'success': '#10b981',
        'warning': '#f59e0b',
        'danger': '#ef4444',
        'secondary': '#64748b'
      }
    }
  },
  safelist: ['bg-primary', 'bg-primary/10', 'bg-accent', 'text-primary', 'text-accent', 'border-primary', 'bg-metoda-dark', 'bg-metoda-red', 'bg-admin-blue', 'text-admin-blue', 'border-admin-blue', 'focus:ring-admin-blue', 'focus:ring-primary', 'hover:bg-primary-dark']
}
