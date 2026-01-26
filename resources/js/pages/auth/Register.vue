<script setup lang="ts">
    import InputError from '@/components/InputError.vue';
    import TextLink from '@/components/TextLink.vue';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AuthBase from '@/layouts/AuthLayout.vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ref } from 'vue';

    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    // Add refs for password visibility
    const showPassword = ref(false);
    const showConfirmPassword = ref(false);

    const submit = () => {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
            onSuccess: () => {
                window.location.href = route('home'); // Force redirect to home
            }
        });
    };

    // Toggle password visibility
    const togglePasswordVisibility = () => {
        showPassword.value = !showPassword.value;
    };

    // Toggle confirm password visibility
    const toggleConfirmPasswordVisibility = () => {
        showConfirmPassword.value = !showConfirmPassword.value;
    };
</script>

<template>
    <AppLayout>
        <AuthBase title="Izveidot kontu" description="Ievadi savus datus, lai izveidotu kontu">
            <Head title="Reģistrācija" />

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name">Vārds</Label>
                        <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Pilns vārds" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">E-pasta adrese</Label>
                        <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="epasts@piemers.lv" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Parole</Label>
                        <div class="relative">
                            <Input id="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   required
                                   :tabindex="3"
                                   autocomplete="new-password"
                                   v-model="form.password"
                                   placeholder="Parole"
                                   class="pr-10" />
                            <button type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-black hover:text-gray-800 focus:outline-none bg-transparent"
                                    :tabindex="4"
                                    aria-label="Rādīt/slēpt paroli">
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Apstiprināt paroli</Label>
                        <div class="relative">
                            <Input id="password_confirmation"
                                   :type="showConfirmPassword ? 'text' : 'password'"
                                   required
                                   :tabindex="5"
                                   autocomplete="new-password"
                                   v-model="form.password_confirmation"
                                   placeholder="Apstiprināt paroli"
                                   class="pr-10" />
                            <button type="button"
                                    @click="toggleConfirmPasswordVisibility"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-black hover:text-gray-800 focus:outline-none bg-transparent"
                                    :tabindex="6"
                                    aria-label="Rādīt/slēpt apstiprinājuma paroli">
                                <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" class="mt-2 w-full" tabindex="7" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Izveidot kontu
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    Jau ir konts?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="8">Pieteikties</TextLink>
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

    /* Input container styling */
    .relative {
        position: relative;
    }

    /* Input field styling */
    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 0.75rem 1rem;
        padding-right: 2.5rem !important; /* Make room for the eye icon */
        border: 1px solid var(--input-border);
        border-radius: 8px;
        background-color: var(--input-bg);
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 1rem;
    }

    input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2);
        outline: none;
    }

    /* Eye button styling - INSIDE the input box */
    .relative button {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        padding: 0.25rem;
        cursor: pointer;
        color: #000000; /* BLACK color */
        transition: color 0.2s;
        z-index: 10;
    }

        .relative button:hover {
            color: #333333; /* Darker black on hover */
            background: transparent;
        }

        .relative button:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Ensure the eye icon is visible */
        .relative button svg {
            color: inherit;
            stroke: currentColor;
        }

    button[type="submit"] {
        background-color: var(--primary);
        color: white;
        padding: 0.75rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        transition: background-color 0.2s;
    }

        button[type="submit"]:hover {
            background-color: var(--primary-hover);
        }

        button[type="submit"]:disabled {
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
