<!-- resources/js/Layouts/Navbar.vue -->
<template>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="brand">FitForge</div>
            <button class="hamburger" @click="isOpen = !isOpen">
                ☰
            </button>

            <div :class="['nav-links-container', { open: isOpen }]">
                <ul class="nav-links-center">
                    <li><Link href="/" class="nav-link">Sākums</Link></li>
                    <li><Link href="/routines" class="nav-link">Rutīnas</Link></li>
                    <li><Link href="/exercises" class="nav-link">Vingrinājumi</Link></li>
                    <!-- Show workout logs link only when user is logged in -->
                    <li v-if="user">
                        <Link :href="route('workout-logs.index')" class="nav-link">
                        Treniņu vēsture
                        </Link>
                    </li>
                </ul>

                <div class="nav-links-right" v-if="user">
                    <!-- Profile link -->
                    <Link :href="route('profile.edit')" class="nav-link">
                    Profils
                    </Link>
                    <button @click="$inertia.post(route('logout'))" class="nav-link logout-button">
                        Iziet
                    </button>
                </div>

                <ul class="nav-links-right" v-else>
                    <li><Link href="/login" class="nav-link">Pieteikties</Link></li>
                    <li><Link href="/register" class="nav-link">Reģistrēties</Link></li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
    import { ref, computed } from 'vue'
    import { Link, usePage } from '@inertiajs/vue3'

    const isOpen = ref(false)
    const user = computed(() => usePage().props.auth.user)
</script>

<style scoped>
    /* ATGRIEZTIE STILI - sākotnējā versija */
    .navbar {
        background-color: black;
        padding: 14px 30px;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .navbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        position: relative;
    }

    .brand {
        color: white;
        font-size: 20px;
        font-weight: bold;
    }

    .hamburger {
        display: none;
        font-size: 28px;
        background: none;
        color: white;
        border: none;
        cursor: pointer;
    }

    .nav-links-container {
        display: flex;
        flex-grow: 1;
        justify-content: center;
    }

    .nav-links-center {
        list-style: none;
        display: flex;
        gap: 20px;
        margin: 0;
        padding: 0;
        align-items: center;
    }

    .nav-links-right {
        list-style: none;
        display: flex;
        gap: 20px;
        margin: 0;
        padding: 0;
        align-items: center;
        position: absolute;
        right: 0;
    }

    .nav-link,
    .logout-button {
        color: white;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 15px;
        border-radius: 5px;
        transition: background 0.3s ease;
        display: inline-block;
        font-family: inherit;
        font-size: inherit;
        line-height: normal;
    }

        .nav-link:hover,
        .logout-button:hover {
            background-color: #333;
        }

    .logout-button {
        background: none;
        color: white;
        border: none;
        cursor: pointer;
        margin: 0;
        padding: 10px 15px;
    }

    @media (max-width: 768px) {
        .hamburger {
            display: block;
        }

        .nav-links-container {
            display: none;
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
            gap: 10px;
        }

            .nav-links-container.open {
                display: flex;
            }

        .nav-links-center,
        .nav-links-right {
            position: static;
            flex-direction: column;
            width: 100%;
            gap: 10px;
        }

            .nav-links-center li,
            .nav-links-right li {
                text-align: center;
                width: 100%;
            }
    }
</style>
