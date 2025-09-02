<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página No Encontrada | SIM</title>
  <link rel="stylesheet" href="/css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="m-0 font-['Merriweather_Sans'] bg-gray-100 text-blue-900">
  <header class="bg-blue-900 text-white h-[70px] min-h-[70px] flex items-center justify-between px-8 box-border">
    <div class="flex items-center gap-2.5">
      <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center font-bold text-blue-900 text-lg border-2 border-blue-900 box-border">
        SIM
      </div>
      <div class="text-lg font-bold leading-tight tracking-tight">
        Scuola<br>Italiana di<br>Montevideo
      </div>
    </div>
    <div class="text-3xl font-bold flex-1 text-center tracking-wide">Sistema de Horarios SIM</div>
    <div class="w-11 h-11 flex flex-col justify-center gap-2 cursor-pointer items-end" style="visibility:hidden;">
      <span class="block h-1 w-full bg-white rounded-sm"></span>
      <span class="block h-1 w-full bg-white rounded-sm"></span>
      <span class="block h-1 w-full  bg-white rounded-sm"></span>
    </div>
  </header>
  
  <div class="min-h-[calc(100vh-70px)] bg-gray-800 flex items-center justify-center p-4">
    <section class="bg-gray-200 rounded-[32px] p-12 pb-8 w-full max-w-[520px] shadow-lg flex flex-col items-center text-center">
      <!-- 404 Number with animated styling -->
      <div class="text-8xl md:text-9xl font-bold text-blue-900 mb-4 opacity-20 select-none">
        404
      </div>
      
      <!-- Main heading -->
      <h1 class="text-blue-900 text-3xl md:text-4xl font-bold mb-4 leading-tight">
        ¡Oops! Página No Encontrada
      </h1>
      
      <!-- Subtitle -->
      <p class="text-blue-900 text-lg mb-8 max-w-md leading-relaxed">
        La página que estás buscando no existe o ha sido movida a otra ubicación.
      </p>
      
      <!-- Illustration/Icon -->
      <div class="w-32 h-32 bg-blue-100 rounded-full flex items-center justify-center mb-8 border-4 border-blue-200">
        <svg class="w-16 h-16 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-3-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      
      <!-- Action buttons -->
      <div class="flex flex-col sm:flex-row gap-4 w-full max-w-sm">
        <button onclick="window.history.back()" 
                class="flex-1 bg-blue-900 text-white border-none rounded-lg py-3 px-6 text-base font-bold cursor-pointer transition-all duration-200 hover:bg-blue-800 hover:transform hover:scale-105 active:scale-95">
          <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Volver Atrás
        </button>
        
        <a href="/" 
           class="flex-1 bg-gray-600 text-white border-none rounded-lg py-3 px-6 text-base font-bold cursor-pointer transition-all duration-200 hover:bg-gray-700 hover:transform hover:scale-105 active:scale-95 text-center no-underline flex items-center justify-center">
          <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          Inicio
        </a>
      </div>
      
      <!-- Additional help text -->
      <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200 w-full">
        <p class="text-blue-800 text-sm mb-2 font-medium">¿Necesitas ayuda?</p>
        <p class="text-blue-700 text-sm leading-relaxed">
          Si crees que esto es un error, por favor contacta al administrador del sistema o verifica que la URL esté escrita correctamente.
        </p>
      </div>
    </section>
  </div>

  <script>
    // Add some interactive animations
    document.addEventListener('DOMContentLoaded', function() {
      // Animate the 404 number on page load
      const number404 = document.querySelector('section div:first-child');
      if (number404) {
        number404.style.animation = 'pulse 2s infinite';
      }
      
      // Add hover effects to buttons
      const buttons = document.querySelectorAll('button, a');
      buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
          this.style.boxShadow = '0 8px 25px rgba(59, 130, 246, 0.3)';
        });
        
        button.addEventListener('mouseleave', function() {
          this.style.boxShadow = 'none';
        });
      });
    });
    
    // Add CSS animations via JavaScript
    const style = document.createElement('style');
    style.textContent = `
      @keyframes pulse {
        0%, 100% { opacity: 0.2; }
        50% { opacity: 0.1; }
      }
      
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      
      section {
        animation: fadeIn 0.6s ease-out;
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>
