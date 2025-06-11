<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onSuccess: () => {
      window.location.href = route('home'); // Force redirect to home
    }
    });
};
</script>

<template>
        <AppLayout>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
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
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
  background-color: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

input[type="text"],
input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--input-border);
  border-radius: 8px;
  background-color: var(--input-bg);
  transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2);
  outline: none;
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