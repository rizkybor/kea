<template>
  <div class="flex min-h-screen flex-col">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Lewati ke konten</a>
    <AppNavbar />
    <div id="konten" class="flex-1">
      <slot />
    </div>
    <AppFooter />
  </div>
</template>
