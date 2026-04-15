<script setup>
import { 
  User, 
  Mail, 
  FileText, 
  Search, 
  Plus, 
  Loader2,
  CheckCircle2,
  AlertCircle,
  LayoutGrid,
  List
} from 'lucide-vue-next'

const { data: usuarios, pending } = await useFetch(
  'http://localhost:8000/wp-json/wp/v2/usuarios'
)

const searchQuery = ref('')
const statusFilter = ref('todos')
const viewMode = ref('grid')

const filteredUsuarios = computed(() => {
  if (!usuarios.value) return []
  if (!searchQuery.value) return usuarios.value
  
  const query = searchQuery.value.toLowerCase()
  return usuarios.value.filter(u => {
    const nome = u.acf?.nome?.toLowerCase() || ''
    const email = u.acf?.email?.toLowerCase() || ''
    const cpf = u.acf?.cpf?.toLowerCase() || ''
    return nome.includes(query) || email.includes(query) || cpf.includes(query)
  })
})
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-100 via-white to-slate-50 p-6 md:p-12 font-sans selection:bg-indigo-100 selection:text-indigo-900 text-slate-800">
    <div class="max-w-7xl mx-auto">
      
      <!-- Sessão 1: Identificação -->
      <section class="mb-12 text-center flex flex-col items-center">
        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-slate-900">
          Dashboard de <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">Usuários</span>
        </h1>
        <p class="text-slate-500 mt-4 text-lg max-w-xl leading-relaxed">
          Gerencie e visualize membros cadastrados com filtros inteligentes e interface de alta performance.
        </p>
      </section>

      <!-- Sessão 2: Ações e Filtros -->
      <section class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 mb-10 bg-white p-5 rounded-[2rem] border-2 border-slate-100 shadow-md shadow-slate-200/50">
        
        <!-- Grupo de Filtros -->
        <div class="flex flex-col md:flex-row gap-4 flex-1">
          <!-- Input Busca -->
          <div class="relative group flex-1 max-w-md">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <Search class="w-5 h-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Pesquisar por nome, email, CPF ou ID..."
              class="block w-full pl-11 pr-4 py-3.5 bg-white border border-slate-200 rounded-2xl leading-5 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm shadow-sm"
            >
            <div v-if="searchQuery" class="absolute inset-y-0 right-3 flex items-center">
              <button @click="searchQuery = ''" class="p-1 hover:bg-slate-100 rounded-lg transition-colors text-slate-400">
                <Plus class="w-4 h-4 rotate-45" />
              </button>
            </div>
          </div>

          <!-- Select Status -->
          <div class="relative">
            <select 
              v-model="statusFilter"
              class="px-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-600 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm appearance-none cursor-pointer w-full md:w-40 pr-10"
            >
              <option value="todos">Todos Status</option>
              <option value="ativo">Ativos</option>
              <option value="inativo">Inativos</option>
            </select>
            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
          </div>
        </div>

        <!-- Grupo de Ações e View Mode -->
        <div class="flex items-center gap-4 justify-between lg:justify-end">
          
          <!-- View Toggle -->
          <div class="flex items-center bg-white border border-slate-200 rounded-2xl p-1.5 shadow-sm">
            <button 
              @click="viewMode = 'grid'"
              :class="['p-2 rounded-xl transition-all', viewMode === 'grid' ? 'bg-slate-100 text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
            >
              <LayoutGrid class="w-5 h-5" />
            </button>
            <button 
              @click="viewMode = 'list'"
              :class="['p-2 rounded-xl transition-all', viewMode === 'list' ? 'bg-slate-100 text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
            >
              <List class="w-5 h-5" />
            </button>
          </div>

          <!-- Botão Adicionar -->
          <button class="flex shrink-0 items-center justify-center gap-2 px-6 py-3.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl font-bold transition-all duration-300 shadow-lg shadow-slate-200 hover:shadow-indigo-200 hover:-translate-y-0.5 active:scale-95 group text-sm border border-slate-800 hover:border-indigo-500">
            <Plus class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" />
            Novo Usuário
          </button>

        </div>
      </section>

      <!-- Sessão 3: Dados / Grid -->

      <!-- Loading State (Skeleton) -->
      <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="i in 6" :key="i" class="bg-white/60 backdrop-blur-sm border border-slate-200/60 rounded-3xl p-6 shadow-sm animate-pulse">
          <div class="flex items-center gap-5 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-slate-200"></div>
            <div class="space-y-3 flex-1">
              <div class="h-4 bg-slate-200 rounded-md w-3/4"></div>
              <div class="h-3 bg-slate-200 rounded-md w-1/2"></div>
            </div>
          </div>
          <div class="space-y-4">
            <div class="h-3 bg-slate-200 rounded-md w-full"></div>
            <div class="h-3 bg-slate-200 rounded-md w-5/6"></div>
          </div>
        </div>
      </div>

      <!-- Grid with Transition -->
      <div v-else-if="filteredUsuarios.length > 0">
        <TransitionGroup 
          tag="div" 
          name="list"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
        >
          <div
            v-for="user in filteredUsuarios"
            :key="user.id"
            class="group relative bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-[2rem] p-6 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-2 transition-all duration-500 overflow-hidden"
          >
            <!-- Animation Backdrop -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>

            <!-- Avatar + Status Section -->
            <div class="flex items-start justify-between mb-6">
              <div class="relative">
                <div class="absolute inset-0 bg-indigo-500 rounded-2xl blur opacity-0 group-hover:opacity-20 transition-opacity"></div>
                <img
                  v-if="user.acf?.imagem"
                  :src="user.acf.imagem.url"
                  class="relative w-16 h-16 rounded-2xl object-cover shadow-inner ring-1 ring-black/5"
                />
                <div v-else class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 shadow-inner ring-1 ring-black/5">
                  <User class="w-8 h-8 opacity-40" />
                </div>
              </div>
              <div class="flex flex-col items-end">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold uppercase tracking-wider">
                  <CheckCircle2 class="w-3.5 h-3.5" />
                  Ativo
                </span>
              </div>
            </div>

            <!-- Content Area -->
            <div class="mb-8">
              <h2 class="text-2xl font-bold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-1">
                {{ user.acf?.nome || 'Sem nome' }}
              </h2>
              <p class="text-sm font-medium text-slate-400 mt-1">
                ID Membro: #{{ user.id }}
              </p>
            </div>

            <!-- Info Grid -->
            <div class="space-y-4 pt-6 border-t border-slate-100/80">
              <div class="flex items-center justify-between group/item">
                <div class="flex items-center gap-2.5 text-slate-400 group-hover/item:text-indigo-500 transition-colors">
                  <Mail class="w-4 h-4" />
                  <span class="text-sm font-medium">Email</span>
                </div>
                <span class="text-sm font-semibold text-slate-700 truncate max-w-[160px]">{{ user.acf?.email || 'N/A' }}</span>
              </div>

              <div class="flex items-center justify-between group/item">
                <div class="flex items-center gap-2.5 text-slate-400 group-hover/item:text-indigo-500 transition-colors">
                  <FileText class="w-4 h-4" />
                  <span class="text-sm font-medium">CPF</span>
                </div>
                <span class="text-sm font-semibold text-slate-700">{{ user.acf?.cpf || 'N/A' }}</span>
              </div>
            </div>

            <!-- Action button on hover -->
            <div class="mt-6 pt-2 overflow-hidden h-0 group-hover:h-12 transition-all duration-300 ease-out">
              <button class="w-full py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold rounded-xl text-sm transition-colors border border-slate-200">
                Ver Perfil Completo
              </button>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Empty/No result state -->
      <div v-else class="flex flex-col items-center justify-center p-20 bg-white/40 backdrop-blur-md border border-slate-200/50 rounded-[3rem] border-dashed">
        <div class="w-20 h-20 bg-slate-100 rounded-3xl flex items-center justify-center mb-6 text-slate-300">
           <AlertCircle class="w-10 h-10" />
        </div>
        <h3 class="text-2xl font-bold text-slate-800">
          {{ searchQuery ? 'Nenhum resultado para "' + searchQuery + '"' : 'Nenhum usuário encontrado' }}
        </h3>
        <p class="text-slate-500 mt-3 text-center max-w-sm text-lg">
          {{ searchQuery ? 'Tente buscar por termos diferentes ou verifique se há erros de digitação.' : 'Parece que ainda não há membros cadastrados no sistema.' }}
        </p>
        <button v-if="searchQuery" @click="searchQuery = ''" class="mt-8 text-indigo-600 font-bold hover:text-indigo-700 underline underline-offset-4">
          Limpar busca e ver todos
        </button>
      </div>

    </div>
  </div>
</template>

<style>
body {
  font-family: 'Outfit', sans-serif;
}

/* Transitions */
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}
.list-move {
  transition: transform 0.4s ease;
}
</style>