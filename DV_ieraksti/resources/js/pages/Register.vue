<script setup lang="js">
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Sun, Moon, Home } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import '../../css/auth.css';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  admin_code: '',
});

const submit = () => {
  form.post(route('register'));
};

const isDarkMode = ref(localStorage.getItem('isDarkMode') === 'true');

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
    <Head title="Register" />

    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-left">
        <h1>Admin Registration</h1>
      </div>
      <div class="navbar-right">
        <a href="/login" class="nav-button">
          <Home class="w-5 h-5 mr-2 text-white" />
          Login Page
        </a>
      </div>
    </nav>

    <!-- Content -->
    <div class="content">
      <div class="container">
        <h2>Create a new admin</h2>
        <form @submit.prevent="submit">
          <div class="grid gap-3">
            <div class="grid gap-2">
              <Label for="name">Name</Label>
              <Input
                id="name"
                type="text"
                required
                autofocus
                v-model="form.name"
                placeholder="Full Name"
              />
              <InputError class="input-error" :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
              <Label for="email">Email address</Label>
              <Input
                id="email"
                type="email"
                required
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
                v-model="form.password"
                placeholder="Password"
              />
              <InputError class="input-error" :message="form.errors.password" />
            </div>
            <div class="grid gap-2">
              <Label for="password_confirmation">Confirm Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                required
                v-model="form.password_confirmation"
                placeholder="Confirm Password"
              />
              <InputError class="input-error" :message="form.errors.password_confirmation" />
            </div>
            <div class="grid gap-2">
              <Label for="admin_code">Admin Registration Code</Label>
              <Input
                id="admin_code"
                type="password"
                required
                v-model="form.admin_code"
                placeholder="Enter admin code"
              />
              <InputError class="input-error" :message="form.errors.admin_code" />
            </div>
            <Button type="submit" class="mt-2" :disabled="form.processing">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2 loader-circle" />
              Register
            </Button>
          </div>
        </form>
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
        Contact us at:  <a href="mailto:info@rvt.lv" class="login-icon">info@rvt.lv</a>
      </div>
    </footer>
  </div>
</template>
