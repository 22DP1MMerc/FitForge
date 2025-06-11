<script setup lang="ts">
import Navbar from '@/Components/Navbar.vue';
import Foot from '@/Components/Foot.vue';


</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Navbar />
    <div class="flex flex-1">

      <main class="flex-1 p-4">
        <slot v-bind="$attrs" />
      </main>
    </div>
    <Foot />
  </div>
</template>