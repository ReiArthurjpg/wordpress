<script setup>
const { data: usuarios } = await useFetch('http://localhost:8000/wp-json/wp/v2/usuarios')
</script>

<template>
  <div style="padding: 20px">
    <h1>Dashboard de Usuários 🚀</h1>

    <div v-if="usuarios">
      <div 
        v-for="user in usuarios" 
        :key="user.id"
        style="border:1px solid #ccc; padding:15px; margin-bottom:10px; border-radius:8px;"
      >
        <h2>{{ user.acf.nome }}</h2>
        <p>Email: {{ user.acf.email }}</p>
        <p>CPF: {{ user.acf.cpf }}</p>

        <img 
          v-if="user.acf.imagem" 
          :src="user.acf.imagem.url" 
          width="120"
          style="border-radius:8px;"
        />
      </div>
    </div>
  </div>
</template>