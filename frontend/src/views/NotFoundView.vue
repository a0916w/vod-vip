<template>
  <div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="mb-6 text-7xl font-black text-gray-800">404</div>
    <h1 class="mb-2 text-2xl font-bold">页面未找到</h1>
    <p class="mb-8 text-gray-500">你访问的页面不存在或已被移除</p>
    <RouterLink
      to="/"
      class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-8 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25"
    >
      返回首页
    </RouterLink>
  </div>
</template>
