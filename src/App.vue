<script setup lang="ts">
import NavBar from './components/NavBar.vue'
import AppFooter from './components/AppFooter.vue'
import ToastContainer from './components/ToastContainer.vue'
</script>

<template>
  <header>
    <NavBar />
  </header>

  <main>
    <RouterView />
  </main>

  <AppFooter />
  <ToastContainer />
</template>

<style>
@import './assets/styles/shared.css';

* {
  font-family: 'Lato', sans-serif;
}
</style>
