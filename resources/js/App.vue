<template>
  <router-view />
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
/* Import AdminLTE styles */
@import 'admin-lte/dist/css/adminlte.min.css';

/* Global styles */
body {
  font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Custom authentication page styles */
.login-page,
.register-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* AdminLTE card customization */
.card {
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
  border: none;
}

.card-primary.card-outline {
  border-top: 3px solid #007bff;
}

/* Form enhancements */
.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #004085;
}

/* Utility classes */
.text-sm {
  font-size: 0.875rem;
}

.text-xs {
  font-size: 0.75rem;
}
</style>