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
  List,
  Image as ImageIcon,
  Upload,
  Camera,
  X
} from 'lucide-vue-next'

const { data: usuarios, pending, refresh } = await useFetch(
  'http://localhost:8000/wp-json/wp/v2/usuarios'
)

const isModalOpen = ref(false)
const isSubmitting = ref(false)
const newUsuario = ref({
  nome: '',
  email: '',
  senha: '',
  confirmarSenha: '',
  cpf: '',
  dataNascimento: '',
  imagem: '',
  status: 'ativo'
})

const fileInput = ref(null)
const selectedFile = ref(null)
const imagePreview = ref(null)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  selectedFile.value = file
  
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const passwordMismatch = computed(() => {
  return newUsuario.value.senha !== newUsuario.value.confirmarSenha && newUsuario.value.confirmarSenha !== ''
})

const isFormValid = computed(() => {
  const u = newUsuario.value
  return u.nome && u.email && u.senha && u.senha === u.confirmarSenha && u.cpf
})

const handleSubmit = async () => {
  if (!isFormValid.value || isSubmitting.value) return
  isSubmitting.value = true
  
  try {
    const authHeader = 'Basic ' + btoa('Arthur:GOMhOPKqrNfzTPKuCPyFln67')
    let imageId = null
    
    // 1. Upload da imagem se houver
    if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('title', `Avatar ${newUsuario.value.nome}`)
      formData.append('status', 'publish')
      
      const mediaResponse = await $fetch('http://localhost:8000/wp-json/wp/v2/media', {
        method: 'POST',
        headers: {
          'Authorization': authHeader
        },
        body: formData
      })
      imageId = mediaResponse.id
    }

    // Formatação da data para o formato esperado pelo ACF (YYYYMMDD)
    const formattedDate = newUsuario.value.dataNascimento 
      ? newUsuario.value.dataNascimento.replace(/-/g, '') 
      : ''

    // 2. Criar usuário com o id da imagem
    await $fetch('http://localhost:8000/wp-json/wp/v2/usuarios', {
      method: 'POST',
      headers: {
        'Authorization': authHeader
      },
      body: {
        title: newUsuario.value.nome,
        status: 'publish',
        acf: {
          nome: newUsuario.value.nome,
          email: newUsuario.value.email,
          cpf: newUsuario.value.cpf,
          data_de_nascimento: formattedDate,
          senha: newUsuario.value.senha,
          imagem: imageId
        }
      }
    })
    
    // Sucesso
    isModalOpen.value = false
    newUsuario.value = { 
      nome: '', 
      email: '', 
      senha: '', 
      confirmarSenha: '', 
      cpf: '', 
      dataNascimento: '', 
      imagem: '', 
      status: 'ativo' 
    }
    selectedFile.value = null
    imagePreview.value = null
    await refresh()
  } catch (error) {
    console.error('Erro ao criar usuário:', error)
    alert('Erro ao criar usuário. Verifique o console para mais detalhes.')
  } finally {
    isSubmitting.value = false
  }
}

const searchQuery = ref('')
const statusFilter = ref('todos')
const viewMode = ref('grid')

const filteredUsuarios = computed(() => {
  if (!usuarios.value) return []
  
  const query = searchQuery.value.toLowerCase()
  const status = statusFilter.value
  
  return usuarios.value.filter(u => {
    // Lógica de Busca
    const nome = u.acf?.nome?.toLowerCase() || ''
    const email = u.acf?.email?.toLowerCase() || ''
    const cpf = u.acf?.cpf?.toLowerCase() || ''
    const id = String(u.id)
    
    const matchesSearch = nome.includes(query) || email.includes(query) || cpf.includes(query) || id.includes(query)
    
    // Lógica de Status (Placeholder - assumindo que a API pode retornar um status futuramente)
    // Para efeito de demonstração, todos são considerados 'ativos' se a API não retornar status
    const matchesStatus = status === 'todos' || status === 'ativo' 
    
    return matchesSearch && matchesStatus
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

      <!-- Sessão 2: Ações e Filtros (Versão Compacta Premium) -->
      <section class="mb-8 bg-white rounded-3xl border border-slate-200/60 shadow-xl shadow-slate-200/40 relative overflow-hidden">
        <!-- Detalhe de luz no topo -->
        <div class="absolute top-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-indigo-500/10 to-transparent"></div>
        
        <!-- Área de Conteúdo -->
        <div class="px-5 py-4 md:px-7 md:py-5">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            
            <!-- Grupo de Filtros -->
            <div class="flex flex-col md:flex-row gap-4 flex-1">
              <!-- Input Busca -->
              <div class="relative group flex-1 max-w-lg">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <Search class="w-4 h-4 text-slate-300 group-focus-within:text-indigo-500 transition-colors" />
                </div>
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Pesquisar por nome, email, CPF ou ID..."
                  class="block w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-100 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                >
              </div>

              <!-- Select Status -->
              <div class="relative">
                <select 
                  v-model="statusFilter"
                  class="px-4 py-2.5 bg-slate-50/50 border border-slate-100 rounded-xl text-slate-600 text-sm font-bold focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all shadow-inner appearance-none cursor-pointer w-full md:w-44 pr-10"
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

            <!-- Grupo de Ações -->
            <div class="flex items-center gap-4 justify-between lg:justify-end">
              
              <!-- View Toggle -->
              <div class="flex items-center bg-slate-50/80 p-1 rounded-xl border border-slate-100">
                <button 
                  @click="viewMode = 'grid'"
                  :class="['p-1.5 rounded-lg transition-all duration-300', viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-500/10' : 'text-slate-400 hover:text-slate-600']"
                >
                  <LayoutGrid class="w-4 h-4" />
                </button>
                <button 
                  @click="viewMode = 'list'"
                  :class="['p-1.5 rounded-lg transition-all duration-300', viewMode === 'list' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-500/10' : 'text-slate-400 hover:text-slate-600']"
                >
                  <List class="w-4 h-4" />
                </button>
              </div>

              <!-- Botão Adicionar -->
              <button 
                @click="isModalOpen = true"
                class="relative flex shrink-0 items-center justify-center gap-2 px-6 py-2.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl font-bold transition-all duration-500 shadow-lg shadow-slate-900/10 group text-sm overflow-hidden"
              >
                <Plus class="w-4 h-4 group-hover:rotate-90 transition-transform duration-500" />
                Novo Usuário
              </button>

            </div>
          </div>
        </div>

        <!-- Rodapé do Card: Contador -->
        <div class="px-5 py-1.5 md:px-7 bg-slate-50/50 flex items-center justify-between">
          <div class="text-[12px] text-slate-400 font-medium">
            Mostrando <span class="text-indigo-600 font-black">{{ filteredUsuarios.length }}</span> de <span class="text-slate-900 font-black">12</span> membros encontrados.
          </div>
          <div v-if="searchQuery" class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">
            Filtro Ativo
          </div>
        </div>
      </section>

      <!-- Sessão 3: Dados / Grid-List -->

      <!-- Loading State -->
      <div v-if="pending" :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8' : 'flex flex-col gap-4'">
        <div v-for="i in 6" :key="i" class="bg-white/60 rounded-3xl p-6 shadow-sm animate-pulse flex items-center gap-5">
           <div class="w-16 h-16 rounded-2xl bg-slate-200 flex-shrink-0"></div>
           <div class="flex-1 space-y-3">
             <div class="h-4 bg-slate-200 rounded w-1/2"></div>
             <div class="h-3 bg-slate-200 rounded w-3/4"></div>
           </div>
        </div>
      </div>

      <!-- Content Area with Transition -->
      <div v-else-if="filteredUsuarios.length > 0">
        <TransitionGroup 
          tag="div" 
          name="list"
          :class="[
            viewMode === 'grid' 
              ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8' 
              : 'flex flex-col gap-4'
          ]"
        >
          <div
            v-for="user in filteredUsuarios"
            :key="user.id"
            :class="[
              'group relative bg-white/95 border border-slate-200/80 transition-all duration-500 overflow-hidden shadow-sm hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1',
              viewMode === 'grid' 
                ? 'rounded-[2rem] p-8 flex flex-col items-center text-center' 
                : 'rounded-2xl p-5 flex flex-col md:flex-row md:items-center gap-6'
            ]"
          >
            <!-- Animation Backdrop (Grid Only) -->
            <div v-if="viewMode === 'grid'" class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>

            <!-- Avatar -->
            <div :class="['relative shrink-0', viewMode === 'grid' ? 'mb-6' : '']">
              <div class="absolute inset-0 bg-indigo-500 rounded-2xl blur opacity-0 group-hover:opacity-20 transition-opacity"></div>
              <img
                v-if="user.acf?.imagem"
                :src="user.acf.imagem.url"
                class="relative w-20 h-20 rounded-2xl object-cover shadow-inner ring-1 ring-black/5"
              />
              <div v-else class="relative w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 shadow-inner ring-1 ring-black/5">
                <User class="w-10 h-10 opacity-30" />
              </div>
            </div>

            <!-- Content -->
            <div :class="['flex-1 min-w-0', viewMode === 'grid' ? 'w-full mb-8' : '']">
              <div class="flex items-center gap-3 mb-1" :class="viewMode === 'grid' ? 'justify-center' : ''">
                <h2 class="text-2xl font-bold text-slate-800 group-hover:text-indigo-600 transition-colors truncate">
                  {{ user.acf?.nome || 'Sem nome' }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider">
                  Ativo
                </span>
              </div>
              <p class="text-sm font-medium text-slate-400">
                ID Membro: #{{ user.id }}
              </p>

              <!-- Additional info for List mode hidden in Grid -->
              <div v-if="viewMode === 'list'" class="flex flex-wrap gap-6 mt-3">
                <div class="flex items-center gap-2 text-slate-500 text-sm">
                  <Mail class="w-4 h-4 text-slate-400" />
                  {{ user.acf?.email || 'N/A' }}
                </div>
                <div class="flex items-center gap-2 text-slate-500 text-sm">
                  <FileText class="w-4 h-4 text-slate-400" />
                  {{ user.acf?.cpf || 'N/A' }}
                </div>
              </div>
            </div>

            <!-- Info Grid (Grid mode only) -->
            <div v-if="viewMode === 'grid'" class="w-full space-y-4 pt-6 border-t border-slate-100/80">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 font-medium">Email</span>
                <span class="text-slate-700 font-semibold truncate max-w-[140px]">{{ user.acf?.email || 'N/A' }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 font-medium">CPF</span>
                <span class="text-slate-700 font-semibold">{{ user.acf?.cpf || 'N/A' }}</span>
              </div>
            </div>

            <!-- Action Button -->
            <div :class="['shrink-0', viewMode === 'grid' ? 'mt-8 w-full' : 'ml-auto']">
              <button class="px-5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold rounded-xl text-sm transition-colors border border-slate-200">
                {{ viewMode === 'grid' ? 'Ver Perfil' : 'Detalhes' }}
              </button>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center p-20 bg-white/40 border-2 border-slate-200 border-dashed rounded-[3rem]">
        <AlertCircle class="w-12 h-12 text-slate-300 mb-4" />
        <h3 class="text-2xl font-bold text-slate-800 text-center">Nenhum usuário encontrado</h3>
        <p class="text-slate-400 mt-2 text-center max-w-sm">Tente ajustar seus filtros ou termos de busca.</p>
        <button v-if="searchQuery || statusFilter !== 'todos'" @click="searchQuery = ''; statusFilter = 'todos'" class="mt-6 text-indigo-600 font-bold hover:underline">
          Limpar todos os filtros
        </button>
      </div>

    </div>

    <!-- Modal de Criação (Premium) -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
          <!-- Backdrop -->
          <div @click="isModalOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity"></div>
          
          <!-- Modal Content -->
          <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden">
            <!-- Header -->
            <div class="px-8 pt-8 pb-4 flex items-center justify-between">
              <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Novo <span class="text-indigo-600">Usuário</span></h2>
                <p class="text-slate-400 text-sm font-medium mt-1">Configure o perfil e credenciais do novo membro.</p>
              </div>
              <button @click="isModalOpen = false" class="p-3 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 rounded-2xl transition-all">
                <Plus class="w-6 h-6 rotate-45" />
              </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit" class="p-8 pt-4 max-h-[70vh] overflow-y-auto space-y-6 custom-scrollbar">
              
              <!-- Seção: Informações Básicas -->
              <div class="space-y-4">
                <h3 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Informações Básicas</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <!-- Nome -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Nome Completo</label>
                    <div class="relative group">
                      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <User class="w-4 h-4 text-slate-300 group-focus-within:text-indigo-500" />
                      </div>
                      <input 
                        v-model="newUsuario.nome"
                        type="text" 
                        required
                        placeholder="Ex: João Silva"
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                      />
                    </div>
                  </div>

                  <!-- Email -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">E-mail</label>
                    <div class="relative group">
                      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <Mail class="w-4 h-4 text-slate-300 group-focus-within:text-indigo-500" />
                      </div>
                      <input 
                        v-model="newUsuario.email"
                        type="email" 
                        required
                        placeholder="joao@exemplo.com"
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                      />
                    </div>
                  </div>

                  <!-- CPF -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">CPF</label>
                    <div class="relative group">
                      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <FileText class="w-4 h-4 text-slate-300 group-focus-within:text-indigo-500" />
                      </div>
                      <input 
                        v-model="newUsuario.cpf"
                        type="text" 
                        required
                        placeholder="000.000.000-00"
                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                      />
                    </div>
                  </div>

                  <!-- Data Nascimento -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Data de Nascimento</label>
                    <input 
                      v-model="newUsuario.dataNascimento"
                      type="date" 
                      class="block w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                    />
                  </div>
                </div>
              </div>

              <!-- Seção: Credenciais -->
              <div class="space-y-4 pt-4">
                <h3 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Credenciais de Acesso</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <!-- Senha -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Senha</label>
                    <input 
                      v-model="newUsuario.senha"
                      type="password" 
                      required
                      placeholder="••••••••"
                      class="block w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-medium shadow-inner"
                    />
                  </div>

                  <!-- Confirmar Senha -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Confirmar Senha</label>
                    <input 
                      v-model="newUsuario.confirmarSenha"
                      type="password" 
                      required
                      placeholder="••••••••"
                      :class="['block w-full px-4 py-3 bg-slate-50 border rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:bg-white transition-all text-sm font-medium shadow-inner', passwordMismatch ? 'border-red-300 ring-red-500/10' : 'border-slate-100 focus:border-indigo-500']"
                    />
                    <p v-if="passwordMismatch" class="text-[10px] font-bold text-red-500 mt-1 pl-1">As senhas não coincidem.</p>
                  </div>
                </div>
              </div>

              <!-- Seção: Perfil e Status -->
              <div class="space-y-4 pt-4">
                <h3 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Perfil e Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <!-- Upload Imagem -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Foto de Perfil</label>
                    <input 
                      ref="fileInput"
                      type="file" 
                      accept="image/*"
                      class="hidden"
                      @change="onFileChange"
                    />
                    <div 
                      @click="triggerFileInput"
                      class="relative h-[110px] bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all group overflow-hidden flex flex-col items-center justify-center gap-2"
                    >
                      <div v-if="imagePreview" class="absolute inset-0">
                        <img :src="imagePreview" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                          <Camera class="w-6 h-6 text-white" />
                        </div>
                      </div>
                      <template v-else>
                        <div class="p-2 bg-white rounded-xl shadow-sm border border-slate-100 group-hover:scale-110 transition-transform">
                          <ImageIcon class="w-5 h-5 text-indigo-500" />
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Clique para carregar</span>
                      </template>
                    </div>
                  </div>

                  <!-- Status -->
                  <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1">Status Inicial</label>
                    <div class="h-[110px] flex flex-col justify-end">
                      <select 
                        v-model="newUsuario.status"
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 focus:bg-white transition-all text-sm font-bold shadow-inner cursor-pointer appearance-none"
                      >
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="flex items-center gap-4 pt-6 mt-6 border-t border-slate-50">
                <button 
                  type="button"
                  @click="isModalOpen = false"
                  class="flex-1 px-6 py-4 bg-slate-50 hover:bg-slate-100 text-slate-500 font-bold rounded-2xl transition-all active:scale-95"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  :disabled="isSubmitting || !isFormValid"
                  :class="['flex-[2] px-6 py-4 font-bold rounded-2xl shadow-xl transition-all active:scale-95 flex items-center justify-center gap-3', !isFormValid ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' : 'bg-slate-900 border border-slate-800 hover:bg-indigo-600 hover:border-indigo-500 text-white shadow-slate-900/10']"
                >
                  <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin text-white/50" />
                  {{ isSubmitting ? 'Cadastrando...' : 'Confirmar Cadastro' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
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

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9) translateY(20px);
  filter: blur(10px);
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>