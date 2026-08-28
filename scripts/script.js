// ==========================
// MENU MOBILE (usado em todas as páginas)
// ==========================

function toggleMenu(){
    document.getElementById("menu").classList.toggle("show");
}

// ==========================
// CARROSSEL DE IMAGENS (só roda nas páginas que têm o slider, ex: index.html)
// ==========================

const track = document.querySelector(".slider-track");

if (track){

    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");
    const next = document.querySelector(".next");
    const prev = document.querySelector(".prev");

    const total = slides.length;
    let indice = 0;
    let intervalo;

    // Mostrar Slide

    function mostrarSlide(n){

        track.style.transform = `translateX(-${n * (100 / total)}%)`;

        dots.forEach((dot) => dot.classList.remove("active"));
        dots[n].classList.add("active");
    }

    // Próximo Slide

    function proximoSlide(){

        indice = (indice + 1) % total;
        mostrarSlide(indice);
    }

    // Slide Anterior

    function slideAnterior(){

        indice = (indice - 1 + total) % total;
        mostrarSlide(indice);
    }

    // Iniciar Carrossel

    function iniciarCarrossel(){

        intervalo = setInterval(() => {
            proximoSlide();
        }, 5000);
    }

    // Reiniciar Tempo

    function reiniciar(){

        clearInterval(intervalo);
        iniciarCarrossel();
    }

    // Botão Direita

    if (next){
        next.addEventListener("click", () => {
            proximoSlide();
            reiniciar();
        });
    }

    // Botão Esquerda

    if (prev){
        prev.addEventListener("click", () => {
            slideAnterior();
            reiniciar();
        });
    }

    // Clique nos indicadores

    dots.forEach((dot, i) => {
        dot.addEventListener("click", () => {
            indice = i;
            mostrarSlide(indice);
            reiniciar();
        });
    });

    // Inicialização

    mostrarSlide(indice);
    iniciarCarrossel();
}

// ==========================
// LIGHTBOX DA GALERIA (só roda nas páginas que têm .galeria, ex: galeria.html)
// ==========================

const galeria = document.querySelector(".galeria");

if (galeria){

    const fotos = document.querySelectorAll(".produto");
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightboxImg");
    const lightboxCaption = document.getElementById("lightboxCaption");
    const lightboxClose = document.getElementById("lightboxClose");
    const lightboxPrev = document.getElementById("lightboxPrev");
    const lightboxNext = document.getElementById("lightboxNext");

    const totalFotos = fotos.length;
    let fotoAtual = 0;

    function abrirLightbox(i){

        fotoAtual = i;

        const img = fotos[i].querySelector("img");

        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightboxCaption.textContent = img.alt;

        lightbox.classList.add("show");
        document.body.style.overflow = "hidden";
    }

    function fecharLightbox(){

        lightbox.classList.remove("show");
        document.body.style.overflow = "";
    }

    function fotoAnterior(){

        fotoAtual = (fotoAtual - 1 + totalFotos) % totalFotos;
        abrirLightbox(fotoAtual);
    }

    function proximaFoto(){

        fotoAtual = (fotoAtual + 1) % totalFotos;
        abrirLightbox(fotoAtual);
    }

    fotos.forEach((produto, i) => {
        produto.addEventListener("click", () => abrirLightbox(i));
    });

    if (lightboxClose){
        lightboxClose.addEventListener("click", fecharLightbox);
    }

    if (lightboxPrev){
        lightboxPrev.addEventListener("click", fotoAnterior);
    }

    if (lightboxNext){
        lightboxNext.addEventListener("click", proximaFoto);
    }

    // Fecha clicando fora da imagem

    lightbox.addEventListener("click", (e) => {
        if (e.target === lightbox){
            fecharLightbox();
        }
    });

    // Navegação pelo teclado

    document.addEventListener("keydown", (e) => {

        if (!lightbox.classList.contains("show")) return;

        if (e.key === "Escape") fecharLightbox();
        if (e.key === "ArrowLeft") fotoAnterior();
        if (e.key === "ArrowRight") proximaFoto();
    });
}
