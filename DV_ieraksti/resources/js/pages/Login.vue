<script setup lang="js">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Sun, Moon, Home } from 'lucide-vue-next';
import '../../css/auth.css';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};

const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('isDarkMode', isDarkMode.value);
};
</script>

<template>
  <div :class="{ 'dark-mode': isDarkMode }">
    <Head title="Login" />

    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-left">
        <h1>Admin Login</h1>
      </div>
      <div class="navbar-right">
        <a href="/" class="nav-button">
          <Home class="w-5 h-5 mr-2 text-white" />
          Main Page
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
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <button @click="toggleDarkMode" class="theme-toggle">
        <Sun v-if="isDarkMode" class="w-6 h-6" />
        <Moon v-else class="w-6 h-6" />
      </button>
      <div>
        Contact us at: <a href="mailto:info@example.com">info@example.com</a>
      </div>
    </footer>
  </div>
</template>