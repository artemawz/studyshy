<script setup lang="ts"></script>

<template>
  <RouterLink to="/">
    <div>
      <img class="desktop" src="/studyshy-logo.svg" alt="studyshy logo" />
      <!-- <img class="mobile" src="../../public/studyshy-logo-notext.svg" alt="studyshy logo" />   -->
    </div>
  </RouterLink>
</template>

<style scoped>
img {
  height: 2rem;
  width: auto;
  margin-right: 1rem;
  margin-left: 0.5rem;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}

div {
  display: flex;
  align-items: center;
}

h1 {
  font-family: 'Lato';
  font-style: none;
}

RouterLink {
  text-decoration: none !important;
}
</style>
