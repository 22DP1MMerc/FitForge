<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';


defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
    onSuccess: () => {
      window.location.href = route('home'); // Force redirect to home
    }
  });
};

</script>

<template>

    <AppLayout>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
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
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

<div class="flex items-center space-x-2">
  <input
    id="remember"
    type="checkbox"
    v-model="form.remember"
    class="peer h-4 w-4 accent-orange-600 focus:ring-orange-500 border-gray-300 rounded"
  />
  <label for="remember" class="text-sm font-medium text-black">
    Remember me
  </label>
</div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
            </div>
        </form>
    </AuthBase>
    </AppLayout>
</template>

<style>
:root {
  --primary: #ff6b00;
  --primary-hover: #e65c00;
  --text-main: #111827;
  --text-muted: #6b7280;
  --background-main: #f9fafb;
  --input-border: #d1d5db;
  --input-bg: #fff;
  --error-color: #dc2626;
}

body {
  background-color: var(--background-main);
  font-family: 'Inter', sans-serif;
  color: var(--text-main);
  margin: 0;
  padding: 0;
}

form {
  max-width: 380px;
  margin: 0 auto;
  padding: 2rem;
  background-color: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--input-border);
  border-radius: 8px;
  background-color: var(--input-bg);
  transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="email"]:focus,
input[type="password"]:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2);
  outline: none;
}

input[type="checkbox"] {
  accent-color: var(--primary);
}

button {
  background-color: var(--primary);
  color: white;
  padding: 0.75rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  transition: background-color 0.2s;
}

button:hover {
  background-color: var(--primary-hover);
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

a {
  color: var(--primary);
  text-decoration: none;
  transition: color 0.2s;
}

a:hover {
  text-decoration: underline;
}

label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-main);
}

.text-muted-foreground {
  color: var(--text-muted);
}

.text-sm {
  font-size: 0.875rem;
}

.text-center {
  text-align: center;
}

input + span,
textarea + span {
  font-size: 0.75rem;
  color: var(--error-color);
  margin-top: 0.25rem;
  display: block;
}

</style>