<script setup>
import { Zap, Loader2 } from 'lucide-vue-next'

defineProps({
  active: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <Transition name="fade">
    <div v-if="active" class="fixed inset-0 z-[9999] bg-[#0f172a] flex flex-col items-center justify-center overflow-hidden font-sans">
      
      <!-- EFEITOS DE FUNDO (Glows dinâmicos) -->
      <div class="absolute inset-0">
        <!-- Círculo de luz superior direita -->
        <div class="absolute top-[-10%] right-[-10%] w-[600px] h-[600px] bg-blue-600/15 rounded-full blur-[120px] animate-pulse"></div>
        
        <!-- Círculo de luz inferior esquerda -->
        <div class="absolute bottom-[-10%] left-[-10%] w-[600px] h-[600px] bg-indigo-600/15 rounded-full blur-[120px]"></div>
        
        <!-- Malha de pontos sutil -->
        <div 
          class="absolute inset-0 opacity-[0.05]" 
          style="background-image: radial-gradient(#ffffff 0.5px, transparent 0.5px); background-size: 32px 32px"
        ></div>
      </div>

      <!-- CONTEÚDO CENTRAL -->
      <div class="relative z-10 flex flex-col items-center">
        
        <!-- CONTAINER DO LOGO COM ANIMAÇÃO DE QUICAR E SOMBRA -->
        <div class="relative mb-8 group">
          <!-- Brilho externo do logo -->
          <div class="absolute inset-0 bg-blue-500 rounded-[28px] blur-2xl opacity-20 animate-pulse"></div>
          
          <div class="w-24 h-24 bg-blue-600 rounded-[32px] flex items-center justify-center relative shadow-2xl shadow-blue-900/50 animate-bounce transition-all duration-1000">
            <Zap class="text-white w-12 h-12 fill-current drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]" />
          </div>
        </div>

        <!-- ÁREA DE TEXTO E CARREGAMENTO -->
        <div class="text-center space-y-6 max-w-xs">
          
          <!-- BARRA DE PROGRESSO ESTILIZADA -->
          <div class="relative w-56 h-1 bg-slate-800/50 rounded-full overflow-hidden backdrop-blur-sm border border-white/5 mx-auto">
            <!-- O "Glow" que corre na barra -->
            <div class="h-full bg-gradient-to-r from-transparent via-blue-400 to-transparent w-full absolute animate-progress-glow"></div>
            
            <!-- O preenchimento da barra -->
            <div class="h-full bg-blue-500 rounded-full animate-progress-fill shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>
          </div>
          
          <!-- INDICADOR DE STATUS SIMPLIFICADO -->
          <div class="flex items-center justify-center gap-2 text-slate-400 text-xs font-medium tracking-wide">
            <Loader2 class="w-3.5 h-3.5 animate-spin text-blue-500" />
            <span class="opacity-80 uppercase tracking-widest">Carregando</span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@keyframes progress-fill {
  0% { width: 0%; }
  30% { width: 45%; }
  60% { width: 75%; }
  100% { width: 100%; }
}

@keyframes progress-glow {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.animate-progress-fill {
  animation: progress-fill 3s infinite ease-in-out;
}

.animate-progress-glow {
  animation: progress-glow 1.5s infinite linear;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
