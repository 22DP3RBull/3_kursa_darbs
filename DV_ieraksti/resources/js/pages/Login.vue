<script setup lang="js">
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Sun, Moon, Home } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import '../../css/auth.css';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
    onSuccess: () => window.location.reload()
  });
};

const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');

// Set dark mode class on mount
onMounted(() => {
  if (isDarkMode.value) {
    document.body.classList.add('dark-mode');
  } else {
    document.body.classList.remove('dark-mode');
  }
});

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('isDarkMode', isDarkMode.value);
  if (isDarkMode.value) {
    document.body.classList.add('dark-mode');
  } else {
    document.body.classList.remove('dark-mode');
  }
};
</script>

<template>
  <div>
    <Head title="Login" />

    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-left">
        <h1>Admin Login</h1>
      </div>
      <div class="navbar-right">
        <a href="/" class="nav-icon-link">
          <span class="icon-wrap">
            <Home class="icon" />
            <span class="btn-text">Main Page</span>
          </span>
        </a>
      </div>
    </nav>

    <!-- Content -->
    <div class="content">
      <div class="container">
        <h2>Welcome back admin!</h2>
        <form @submit.prevent="submit">
          <div class="grid gap-3">
            <div class="grid gap-2">
              <Label for="email">Email address</Label>
              <Input
                id="email"
                type="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                v-model="form.email"
                placeholder="email@example.com"
              />
              <InputError class="input-error" :message="form.errors.email" />
            </div>
            <div class="grid gap-2">
              <Label for="password">Password</Label>
              <Input
                id="password"
                type="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                v-model="form.password"
                placeholder="Password"
              />
              <InputError class="input-error" :message="form.errors.password" />
            </div>
            <Button type="submit" class="mt-2" :disabled="form.processing">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2 loader-circle" />
              Log in
            </Button>
          </div>
        </form>
        <div style="margin-top: 16px;">
          <a href="/register" class="text-link">Create a new admin</a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-left">
        <button @click="toggleDarkMode" class="theme-toggle">
          <Sun v-if="isDarkMode" class="w-6 h-6" />
          <Moon v-else class="w-6 h-6" />
        </button>
      </div>
      <div class="footer-center">
        Contact us at:  <a href="mailto:info@rvt.lv
      " class="login-icon">info@rvt.lv</a>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.navbar-right {
  display: flex;
  gap: 10px;
}

.nav-icon-link {
  background: var(--button-background);
  color: var(--button-text);
  border: none;
  border-radius: 6px;
  padding: 8px 15px;
  cursor: pointer;
  display: flex;
  align-items: center;
  text-decoration: none;
  min-width: 44px;
  min-height: 44px;
  transition: background 0.3s;
  overflow: hidden;
}

.nav-icon-link:hover {
  background: var(--button-hover-background);
}

.icon-wrap {
  display: flex;
  align-items: center;
  position: relative;
}

.icon {
  width: 24px;
  height: 24px;
  color: var(--button-text);
  transition: opacity 0.2s;
  opacity: 1;
}

.btn-text {
  margin-left: 8px;
  font-size: 1rem;
  color: var(--button-text);
  opacity: 0;
  transition: opacity 0.2s;
  white-space: nowrap;
  pointer-events: none;
}

.nav-icon-link:hover .icon {
  opacity: 0;
}

.nav-icon-link:hover .btn-text {
  opacity: 1;
  pointer-events: auto;
}
</style>