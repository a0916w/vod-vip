<template>
  <div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="mb-6 text-7xl font-black text-gray-800">404</div>
    <h1 class="mb-2 text-2xl font-bold">页面未找到</h1>
    <p class="mb-8 text-gray-500">你访问的页面不存在或已被移除</p>
    <RouterLink
      to="/"
      class="rounded-lg bg-amber-500 px-6 py-2.5 text-sm font-medium text-black transition hover:bg-amber-400"
    >
      返回仪表盘
    </RouterLink>
  </div>
</template>
