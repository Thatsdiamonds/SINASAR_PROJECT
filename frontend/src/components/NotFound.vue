<template>
    <div style="text-align: center;">
        <h1>ERROR 404</h1>
        <h3>Mw ngapain di <span style="color: red;">{{ this.$route.fullPath }}</span> ?</h3>
        <p><a href="/">Halaman non login, bisa lihat denah pasar dll</a></p>
        <p><a href="/auth?auth-type=sign-in">Halaman untuk login</a></p>
        <p><a href="/register">Halaman untuk register</a></p>
        <p><a href="/admin">Halaman utama setelah login role admin</a></p>
        <p><a href="/seller">Halaman utama setelah login role seller</a></p>
        <p><a href="/logout">Sistem logout</a></p>
    </div>
</template>

<script>
</script>

<style lang="scss" scoped>
</style>